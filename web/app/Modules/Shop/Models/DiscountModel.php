<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class DiscountModel extends Model
{
    protected $table         = 'discounts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['code', 'type', 'value', 'max_redemptions', 'times_redeemed', 'starts_at', 'expires_at', 'description', 'min_subtotal_cents', 'is_active'];
    protected $useTimestamps = true;

    public const TYPES = ['percent' => 'Percentage off', 'fixed' => 'Fixed amount off', 'shipping' => 'Free shipping'];

    public function findValid(string $code, ?int $subtotalCents = null): ?array
    {
        $discount = $this->where('code', strtoupper(trim($code)))->first();

        return $discount && $this->problem($discount, $subtotalCents) === null ? $discount : null;
    }

    /**
     * Why a code cannot be used right now, or null if it can.
     */
    public function problem(array $d, ?int $subtotalCents = null): ?string
    {
        $now = date('Y-m-d H:i:s');
        if (isset($d['is_active']) && ! (int) $d['is_active']) {
            return 'This code is not active.';
        }
        if ($d['starts_at'] && $d['starts_at'] > $now) {
            return 'This code is not active yet.';
        }
        if ($d['expires_at'] && $d['expires_at'] < $now) {
            return 'This code has expired.';
        }
        if ($d['max_redemptions'] !== null && $d['times_redeemed'] >= $d['max_redemptions']) {
            return 'This code has been fully redeemed.';
        }
        if ($subtotalCents !== null && ! empty($d['min_subtotal_cents']) && $subtotalCents < (int) $d['min_subtotal_cents']) {
            return 'Spend ' . money((int) $d['min_subtotal_cents']) . ' or more to use this code.';
        }

        return null;
    }

    public function amountCents(array $discount, int $subtotalCents): int
    {
        if ($discount['type'] === 'percent') {
            return (int) round($subtotalCents * (min(100, (int) $discount['value']) / 100));
        }
        if ($discount['type'] === 'shipping') {
            return 0;
        }

        return min((int) $discount['value'], $subtotalCents);
    }

    public static function describe(array $d): string
    {
        return match ($d['type']) {
            'percent'  => (int) $d['value'] . '% off',
            'shipping' => 'Free shipping',
            default    => money((int) $d['value']) . ' off',
        };
    }
}
