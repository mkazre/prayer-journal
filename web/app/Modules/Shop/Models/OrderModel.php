<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table         = 'orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'order_number', 'user_id', 'email', 'status', 'subtotal_cents', 'discount_cents', 'tax_cents', 'shipping_cents',
        'total_cents', 'currency', 'discount_id', 'shipping_address', 'customer_name', 'phone', 'shipping_method',
        'customer_note', 'admin_note', 'paid_at',
    ];
    protected $useTimestamps = true;

    public const STATUSES = [
        'pending'    => ['Awaiting payment', 'warning'],
        'paid'       => ['Paid', 'success'],
        'processing' => ['Processing', 'info'],
        'shipped'    => ['Shipped', 'brand'],
        'completed'  => ['Completed', 'success'],
        'cancelled'  => ['Cancelled', 'danger'],
        'refunded'   => ['Refunded', 'danger'],
    ];

    /**
     * Creates an order + order_items from a cart, reserves stock, and leaves
     * the order pending until payment is confirmed.
     */
    public function createFromCart(array $cart, array $items, string $email, ?int $userId, array $totals, ?array $discount, $shippingAddress, array $extra = []): array
    {
        $orderNumber = 'PJ-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $orderId     = $this->insert(array_merge([
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
            'shipping_address' => is_array($shippingAddress) ? json_encode($shippingAddress) : $shippingAddress,
        ], array_intersect_key($extra, array_flip(['customer_name', 'phone', 'shipping_method', 'customer_note']))), true);

        $inventory       = new InventoryModel();
        $defaultLocation = $this->db->table('inventory_locations')->orderBy('id')->get()->getRowArray();
        foreach ($items as $item) {
            $label = CartModel::variantLabel($item);
            $this->db->table('order_items')->insert([
                'order_id'         => $orderId,
                'variant_id'       => $item['variant_id'],
                'product_id'       => $item['product_id'] ?? null,
                'sku'              => $item['sku'] ?? null,
                'title_snapshot'   => $item['title'] . ($label !== '' ? ' — ' . $label : ''),
                'quantity'         => $item['quantity'],
                'unit_price_cents' => $item['unit_price_cents'],
            ]);
            if ($defaultLocation && (int) ($item['track_inventory'] ?? 1) && ($item['product_type'] ?? 'physical') !== 'digital') {
                $inventory->adjust((int) $item['variant_id'], (int) $defaultLocation['id'], -1 * (int) $item['quantity'], 'order', $orderNumber);
            }
        }

        $this->db->table('order_fulfillments')->insert(['order_id' => $orderId, 'status' => 'pending']);
        if ($discount) {
            $this->db->table('discounts')->where('id', $discount['id'])->set('times_redeemed', 'times_redeemed + 1', false)->update();
        }
        $this->log($orderId, 'created', 'Order placed by ' . $email . '.', $userId);

        return $this->find($orderId);
    }

    public function markPaid(int $orderId, string $via = 'payment gateway'): void
    {
        $order = $this->find($orderId);
        if (! $order || in_array($order['status'], ['paid', 'processing', 'shipped', 'completed'], true)) {
            return;
        }
        $digitalOnly = ! $this->db->table('order_items oi')->join('products p', 'p.id = oi.product_id', 'left')->where('oi.order_id', $orderId)->groupStart()->where('p.product_type !=', 'digital')->orWhere('p.id', null)->groupEnd()->countAllResults();
        $this->update($orderId, ['status' => $digitalOnly ? 'completed' : 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
        $this->log($orderId, 'paid', 'Payment confirmed via ' . $via . '.' . ($digitalOnly ? ' Digital downloads unlocked.' : ''));
        $this->db->table('carts')->where('user_id', $order['user_id'])->where('status', 'checkout')->update(['status' => 'converted']);
    }

    public function setStatus(int $orderId, string $status, ?int $byUserId = null): void
    {
        $order = $this->find($orderId);
        if (! $order || ! isset(self::STATUSES[$status]) || $order['status'] === $status) {
            return;
        }
        // Return stock when an order that reserved it is cancelled or refunded.
        if (in_array($status, ['cancelled', 'refunded'], true) && ! in_array($order['status'], ['cancelled', 'refunded'], true)) {
            $location = $this->db->table('inventory_locations')->orderBy('id')->get()->getRowArray();
            if ($location) {
                $inventory = new InventoryModel();
                foreach ($this->items($orderId) as $item) {
                    $tracked = $this->db->table('products')->where('id', $item['product_id'])->where('track_inventory', 1)->where('product_type !=', 'digital')->countAllResults();
                    if ($tracked) {
                        $inventory->adjust((int) $item['variant_id'], (int) $location['id'], (int) $item['quantity'], 'restock', $order['order_number']);
                    }
                }
            }
        }
        $data = ['status' => $status];
        if ($status === 'paid' && ! $order['paid_at']) {
            $data['paid_at'] = date('Y-m-d H:i:s');
        }
        $this->update($orderId, $data);
        $this->log($orderId, 'status', 'Status changed to ' . self::STATUSES[$status][0] . '.', $byUserId);
    }

    public function log(int $orderId, string $type, string $message, ?int $userId = null): void
    {
        $this->db->table('order_events')->insert([
            'order_id'   => $orderId,
            'user_id'    => $userId,
            'type'       => $type,
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function events(int $orderId): array
    {
        return $this->db->table('order_events')->where('order_id', $orderId)->orderBy('id', 'DESC')->get()->getResultArray();
    }

    public function items(int $orderId): array
    {
        return $this->db->table('order_items oi')
            ->select('oi.*, p.slug, p.product_type, v.digital_file_path')
            ->select('(SELECT i.path FROM product_images i WHERE i.product_id = oi.product_id ORDER BY i.position, i.id LIMIT 1) AS image', false)
            ->join('products p', 'p.id = oi.product_id', 'left')
            ->join('product_variants v', 'v.id = oi.variant_id', 'left')
            ->where('oi.order_id', $orderId)
            ->get()->getResultArray();
    }

    public function fulfillment(int $orderId): ?array
    {
        return $this->db->table('order_fulfillments')->where('order_id', $orderId)->get()->getRowArray();
    }

    public function forUser(int $userId): array
    {
        return $this->db->table('orders o')
            ->select('o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count', false)
            ->where('o.user_id', $userId)
            ->orderBy('o.created_at', 'desc')
            ->get()->getResultArray();
    }

    public static function address(array $order): array
    {
        $a = json_decode((string) $order['shipping_address'], true);

        return is_array($a) ? $a : ['line1' => (string) $order['shipping_address']];
    }

    public static function isPaid(array $order): bool
    {
        return in_array($order['status'], ['paid', 'processing', 'shipped', 'completed'], true);
    }
}
