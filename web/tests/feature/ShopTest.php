<?php

use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Models\OrderModel;
use App\Modules\Shop\Services\Pricing;
use Tests\Support\AppTestCase;

/**
 * Pricing (discounts, shipping zones, tax) and order stock handling.
 *
 * @internal
 */
final class ShopTest extends AppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $db = db_connect();
        $db->table('shipping_zones')->insertBatch([['name' => 'South Africa', 'countries' => 'ZA'], ['name' => 'Rest of world', 'countries' => '*']]);
        $db->table('shipping_rates')->insertBatch([
            ['zone_id' => 1, 'name' => 'Courier', 'price_cents' => 8500, 'min_order_cents' => null],
            ['zone_id' => 2, 'name' => 'International', 'price_cents' => 45000, 'min_order_cents' => null],
        ]);
        $db->table('tax_rules')->insert(['country' => 'ZA', 'rate_percent' => 15]);
        $this->setting('prices_include_tax', '1');
        $this->setting('free_shipping_over_cents', '75000');
    }

    private function line(int $price, int $qty = 1, string $type = 'physical'): array
    {
        return ['unit_price_cents' => $price, 'quantity' => $qty, 'product_type' => $type];
    }

    public function testInclusiveVatIsExtractedNotAdded(): void
    {
        $q = Pricing::quote([$this->line(25000)], 'ZA');

        $this->assertSame(8500, $q['shipping']);
        $this->assertSame(33500, $q['total']);
        // 15% VAT included in R335.00 is R43.70.
        $this->assertSame(4370, $q['tax']);
    }

    public function testExclusiveTaxIsAddedOnTop(): void
    {
        $this->setting('prices_include_tax', '0');
        $q = Pricing::quote([$this->line(10000)], 'ZA');

        $this->assertSame(2775, $q['tax']);
        $this->assertSame(10000 + 8500 + 2775, $q['total']);
    }

    public function testRestOfWorldZoneAndFreeShippingThreshold(): void
    {
        $this->assertSame(45000, Pricing::quote([$this->line(10000)], 'US')['shipping']);

        $q = Pricing::quote([$this->line(40000, 2)], 'ZA');
        $this->assertTrue($q['free_shipping']);
        $this->assertSame(0, $q['shipping']);
    }

    public function testDigitalOnlyOrdersSkipShipping(): void
    {
        $q = Pricing::quote([$this->line(12000, 1, 'digital')], 'ZA');

        $this->assertFalse($q['needs_shipping']);
        $this->assertSame(12000, $q['total']);
    }

    public function testDiscountCodesApplyAndValidate(): void
    {
        $db = db_connect();
        foreach ([
            ['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10],
            ['code' => 'BIGSPEND', 'type' => 'fixed', 'value' => 5000, 'min_subtotal_cents' => 50000],
            ['code' => 'EXPIRED', 'type' => 'percent', 'value' => 50, 'expires_at' => '2000-01-01 00:00:00'],
        ] as $row) {
            $db->table('discounts')->insert($row + ['is_active' => 1, 'times_redeemed' => 0]);
        }

        $this->assertSame(2500, Pricing::quote([$this->line(25000)], 'ZA', 'welcome10')['discount']);
        $this->assertNotNull(Pricing::quote([$this->line(25000)], 'ZA', 'BIGSPEND')['discount_error']);
        $this->assertNotNull(Pricing::quote([$this->line(25000)], 'ZA', 'EXPIRED')['discount_error']);
        $this->assertNotNull(Pricing::quote([$this->line(25000)], 'ZA', 'NOPE')['discount_error']);
    }

    public function testOrdersReserveStockAndCancellationReturnsIt(): void
    {
        ['variant' => $variant] = $this->makeProduct(25000, 10);
        $carts  = new CartModel();
        $cart   = $carts->findOrCreate(null, 'guest-session');
        $carts->addItem((int) $cart['id'], $variant, 3, 25000);
        $items  = $carts->items((int) $cart['id']);
        $q      = Pricing::quote($items, 'ZA');
        $totals = ['subtotal_cents' => $q['subtotal'], 'discount_cents' => 0, 'tax_cents' => $q['tax'], 'shipping_cents' => $q['shipping'], 'total_cents' => $q['total'], 'currency' => 'ZAR'];

        $orders = new OrderModel();
        $order  = $orders->createFromCart($cart, $items, 'buyer@example.test', null, $totals, null, ['line1' => '1 Church St', 'country' => 'ZA']);
        $stock  = static fn () => (int) db_connect()->table('inventory_levels')->selectSum('quantity')->where('variant_id', $variant)->get()->getRow('quantity');

        $this->assertSame(7, $stock());
        $orders->setStatus((int) $order['id'], 'cancelled');
        $this->assertSame(10, $stock());
        // Cancelling again must not restock twice.
        $orders->setStatus((int) $order['id'], 'refunded');
        $this->assertSame(10, $stock());
    }
}
