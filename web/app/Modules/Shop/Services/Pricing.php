<?php

namespace App\Modules\Shop\Services;

use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Models\DiscountModel;
use Config\Database;

/**
 * Computes cart totals: discount, shipping options for the destination
 * country, tax (inclusive or exclusive), and the grand total. Every price a
 * customer is charged comes from here, server-side.
 */
class Pricing
{
    public const COUNTRIES = [
        'ZA' => 'South Africa', 'BW' => 'Botswana', 'NA' => 'Namibia', 'LS' => 'Lesotho', 'SZ' => 'Eswatini', 'ZW' => 'Zimbabwe',
        'MZ' => 'Mozambique', 'ZM' => 'Zambia', 'MW' => 'Malawi', 'KE' => 'Kenya', 'NG' => 'Nigeria', 'GH' => 'Ghana',
        'UG' => 'Uganda', 'TZ' => 'Tanzania', 'GB' => 'United Kingdom', 'US' => 'United States', 'CA' => 'Canada',
        'AU' => 'Australia', 'NZ' => 'New Zealand', 'IE' => 'Ireland', 'DE' => 'Germany', 'NL' => 'Netherlands', 'FR' => 'France',
    ];

    public static function storeCountry(): string
    {
        return strtoupper(site_setting('store_country', 'ZA'));
    }

    public static function pricesIncludeTax(): bool
    {
        return site_setting('prices_include_tax', '1') === '1';
    }

    /**
     * @return array{subtotal:int, discount:int, discount_row:?array, discount_error:?string, needs_shipping:bool,
     *   rates:list<array>, shipping_rate:?array, shipping:int, tax:int, tax_label:string, tax_included:bool, total:int,
     *   free_shipping_over:int, currency:string}
     */
    public static function quote(array $items, ?string $country = null, ?string $discountCode = null, ?int $rateId = null, ?string $region = null): array
    {
        $country  = strtoupper($country ?: self::storeCountry());
        $subtotal = 0;
        foreach ($items as $i) {
            $subtotal += (int) $i['unit_price_cents'] * (int) $i['quantity'];
        }

        $discount = null;
        $discountCents = 0;
        $discountError = null;
        if ($discountCode) {
            $model = new DiscountModel();
            $row   = $model->where('code', strtoupper(trim($discountCode)))->first();
            if (! $row) {
                $discountError = 'That code was not found.';
            } elseif ($problem = $model->problem($row, $subtotal)) {
                $discountError = $problem;
            } else {
                $discount      = $row;
                $discountCents = $model->amountCents($row, $subtotal);
            }
        }

        $needsShipping = CartModel::needsShipping($items);
        $rates         = $needsShipping ? self::ratesFor($country, $subtotal - $discountCents) : [];
        $freeOver      = (int) site_setting('free_shipping_over_cents', '0');
        $freeShipping  = ($discount && $discount['type'] === 'shipping') || ($freeOver > 0 && $subtotal - $discountCents >= $freeOver);
        $rate          = null;
        foreach ($rates as $r) {
            if ($rateId === null || (int) $r['id'] === $rateId) {
                $rate = $r;
                break;
            }
        }
        $rate ??= $rates[0] ?? null;
        $shipping = $needsShipping && $rate ? ($freeShipping ? 0 : (int) $rate['price_cents']) : 0;
        if ($freeShipping) {
            foreach ($rates as &$r) {
                $r['original_cents'] = $r['price_cents'];
                $r['price_cents']    = 0;
            }
            unset($r);
            if ($rate) {
                $rate['price_cents'] = 0;
            }
        }

        $taxRate   = self::taxRate($country, $region);
        $taxable   = max(0, $subtotal - $discountCents) + $shipping;
        $included  = self::pricesIncludeTax();
        $tax       = $taxRate > 0 ? ($included ? (int) round($taxable - $taxable / (1 + $taxRate / 100)) : (int) round($taxable * $taxRate / 100)) : 0;
        $total     = max(0, $subtotal - $discountCents) + $shipping + ($included ? 0 : $tax);

        return [
            'subtotal'           => $subtotal,
            'discount'           => $discountCents,
            'discount_row'       => $discount,
            'discount_error'     => $discountError,
            'needs_shipping'     => $needsShipping,
            'rates'              => $rates,
            'shipping_rate'      => $rate,
            'shipping'           => $shipping,
            'shipping_available' => ! $needsShipping || $rate !== null,
            'free_shipping'      => $freeShipping,
            'tax'                => $tax,
            'tax_rate'           => $taxRate,
            'tax_label'          => site_setting('tax_label', 'VAT'),
            'tax_included'       => $included,
            'total'              => $total,
            'free_shipping_over' => $freeOver,
            'currency'           => store_currency(),
            'country'            => $country,
        ];
    }

    /**
     * Shipping rates whose zone covers the country ("*" = rest of world),
     * cheapest first, respecting each rate's minimum order value.
     */
    public static function ratesFor(string $country, int $orderCents): array
    {
        $db       = Database::connect();
        $zones    = $db->table('shipping_zones')->get()->getResultArray();
        $matching = [];
        $fallback = [];
        foreach ($zones as $z) {
            $codes = array_map('trim', explode(',', strtoupper((string) $z['countries'])));
            if (in_array($country, $codes, true)) {
                $matching[] = (int) $z['id'];
            } elseif (in_array('*', $codes, true)) {
                $fallback[] = (int) $z['id'];
            }
        }
        $zoneIds = $matching ?: $fallback;
        if ($zoneIds === []) {
            // No zones configured yet: offer a single free standard option so checkout still works.
            return $zones === [] ? [['id' => 0, 'zone_id' => 0, 'name' => 'Standard delivery', 'price_cents' => 0, 'min_order_cents' => null]] : [];
        }
        $rates = $db->table('shipping_rates')->whereIn('zone_id', $zoneIds)->orderBy('price_cents')->get()->getResultArray();

        return array_values(array_filter($rates, static fn ($r) => empty($r['min_order_cents']) || $orderCents >= (int) $r['min_order_cents']));
    }

    public static function taxRate(string $country, ?string $region = null): float
    {
        $db   = Database::connect();
        $rows = $db->table('tax_rules')->where('country', strtoupper($country))->get()->getResultArray();
        $best = null;
        foreach ($rows as $r) {
            if ($r['region'] && $region && strcasecmp($r['region'], $region) === 0) {
                return (float) $r['rate_percent'];
            }
            if (! $r['region']) {
                $best = (float) $r['rate_percent'];
            }
        }

        return $best ?? 0.0;
    }
}
