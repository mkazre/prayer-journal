<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table         = 'orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'order_number', 'user_id', 'email', 'status', 'subtotal_cents', 'discount_cents',
        'tax_cents', 'shipping_cents', 'total_cents', 'currency', 'discount_id', 'shipping_address',
    ];
    protected $useTimestamps = true;

    /**
     * Creates an order + order_items from a cart, decrements inventory,
     * and leaves the order in 'pending' status awaiting payment confirmation.
     */
    public function createFromCart(array $cart, array $items, string $email, ?int $userId, array $totals, ?array $discount, ?string $shippingAddress): array
    {
        $orderNumber = 'PJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $orderId = $this->insert([
            'order_number'     => $orderNumber,
            'user_id'          => $userId,
            'email'            => $email,
            'status'           => 'pending',
            'subtotal_cents'   => $totals['subtotal_cents'],
            'discount_cents'   => $totals['discount_cents'],
            'tax_cents'        => $totals['tax_cents'],
            'shipping_cents'   => $totals['shipping_cents'],
            'total_cents'      => $totals['total_cents'],
            'currency'         => $totals['currency'],
            'discount_id'      => $discount['id'] ?? null,
            'shipping_address' => $shippingAddress,
        ], true);

        $inventory = new InventoryModel();
        $defaultLocation = $this->db->table('inventory_locations')->get()->getRowArray();

        foreach ($items as $item) {
            $this->db->table('order_items')->insert([
                'order_id'         => $orderId,
                'variant_id'       => $item['variant_id'],
                'title_snapshot'   => $item['title'] . ($item['option_name'] ? ' - ' . $item['option_name'] : ''),
                'quantity'         => $item['quantity'],
                'unit_price_cents' => $item['unit_price_cents'],
            ]);

            if ($defaultLocation) {
                $inventory->adjust((int) $item['variant_id'], (int) $defaultLocation['id'], -1 * (int) $item['quantity'], 'order', $orderNumber);
            }
        }

        $this->db->table('order_fulfillments')->insert([
            'order_id' => $orderId,
            'status'   => 'pending',
        ]);

        if ($discount) {
            $this->db->table('discounts')->where('id', $discount['id'])->set('times_redeemed', 'times_redeemed + 1', false)->update();
        }

        return $this->find($orderId);
    }

    public function markPaid(int $orderId): void
    {
        $this->update($orderId, ['status' => 'paid']);
    }

    public function items(int $orderId): array
    {
        return $this->db->table('order_items')->where('order_id', $orderId)->get()->getResultArray();
    }

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)->orderBy('created_at', 'desc')->findAll();
    }
}
