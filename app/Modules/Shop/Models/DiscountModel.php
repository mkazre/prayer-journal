<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class DiscountModel extends Model
{
    protected $table         = 'discounts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['code', 'type', 'value', 'max_redemptions', 'times_redeemed', 'starts_at', 'expires_at'];
    protected $useTimestamps = true;

    public function findValid(string $code): ?array
    {
        $discount = $this->where('code', $code)->first();
        if (! $discount) {
            return null;
        }
        $now = date('Y-m-d H:i:s');
        if ($discount['starts_at'] && $discount['starts_at'] > $now) {
            return null;
        }
        if ($discount['expires_at'] && $discount['expires_at'] < $now) {
            return null;
        }
        if ($discount['max_redemptions'] !== null && $discount['times_redeemed'] >= $discount['max_redemptions']) {
            return null;
        }

        return $discount;
    }

    public function amountCents(array $discount, int $subtotalCents): int
    {
        if ($discount['type'] === 'percent') {
            return (int) round($subtotalCents * ($discount['value'] / 100));
        }

        return min($discount['value'], $subtotalCents);
    }
}
