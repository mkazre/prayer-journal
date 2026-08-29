<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class CartModel extends Model
{
    protected $table         = 'carts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'session_key', 'status'];
    protected $useTimestamps = true;

    public function findOrCreate(?int $userId, string $sessionKey): array
    {
        $builder = $this->where('status', 'active');
        $cart    = $userId ? $builder->where('user_id', $userId)->first() : $builder->where('session_key', $sessionKey)->first();

        if ($cart) {
            return $cart;
        }

        $id = $this->insert(['user_id' => $userId, 'session_key' => $sessionKey, 'status' => 'active'], true);

        return $this->find($id);
    }

    public function addItem(int $cartId, int $variantId, int $quantity, int $unitPriceCents): void
    {
        $existing = $this->db->table('cart_items')->where(['cart_id' => $cartId, 'variant_id' => $variantId])->get()->getRowArray();
        if ($existing) {
            $this->db->table('cart_items')->where('id', $existing['id'])->update(['quantity' => $existing['quantity'] + $quantity]);

            return;
        }
        $this->db->table('cart_items')->insert([
            'cart_id'          => $cartId,
            'variant_id'       => $variantId,
            'quantity'         => $quantity,
            'unit_price_cents' => $unitPriceCents,
        ]);
    }

    public function updateItemQuantity(int $cartId, int $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->db->table('cart_items')->where(['id' => $itemId, 'cart_id' => $cartId])->delete();

            return;
        }
        $this->db->table('cart_items')->where(['id' => $itemId, 'cart_id' => $cartId])->update(['quantity' => $quantity]);
    }

    public function items(int $cartId): array
    {
        return $this->db->table('cart_items')
            ->select('cart_items.*, product_variants.sku, product_variants.option_name, products.title, products.slug')
            ->join('product_variants', 'product_variants.id = cart_items.variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->where('cart_items.cart_id', $cartId)
            ->get()->getResultArray();
    }

    public function subtotalCents(int $cartId): int
    {
        $items = $this->items($cartId);
        $sum   = 0;
        foreach ($items as $item) {
            $sum += $item['unit_price_cents'] * $item['quantity'];
        }

        return $sum;
    }
}
