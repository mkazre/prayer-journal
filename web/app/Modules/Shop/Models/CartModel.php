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

    public static function sessionKey(): string
    {
        $session = session();
        $key     = $session->get('cart_key');
        if (! $key) {
            $key = bin2hex(random_bytes(16));
            $session->set('cart_key', $key);
        }

        return $key;
    }

    /**
     * The visitor's active cart (by user when signed in, otherwise by a
     * random key stored in their session).
     */
    public function current(): array
    {
        return $this->findOrCreate(auth()->loggedIn() ? (int) auth()->id() : null, self::sessionKey());
    }

    public function findOrCreate(?int $userId, string $sessionKey): array
    {
        $builder = $this->where('status', 'active');
        $cart    = $userId ? $builder->where('user_id', $userId)->first() : $builder->where('session_key', $sessionKey)->where('user_id', null)->first();
        if ($cart) {
            return $cart;
        }
        $id = $this->insert(['user_id' => $userId, 'session_key' => $sessionKey, 'status' => 'active'], true);

        return $this->find($id);
    }

    public function currentCount(): int
    {
        $key = session()->get('cart_key');
        $b   = $this->db->table('cart_items ci')->selectSum('ci.quantity', 'n')->join('carts c', 'c.id = ci.cart_id')->where('c.status', 'active');
        if (auth()->loggedIn()) {
            $b->where('c.user_id', auth()->id());
        } elseif ($key) {
            $b->where('c.session_key', $key)->where('c.user_id', null);
        } else {
            return 0;
        }

        return (int) ($b->get()->getRow()->n ?? 0);
    }

    /**
     * Moves a guest cart's lines into the user's cart after sign-in.
     */
    public function mergeGuestCart(int $userId, ?string $sessionKey): void
    {
        if (! $sessionKey) {
            return;
        }
        $guest = $this->where(['status' => 'active', 'session_key' => $sessionKey, 'user_id' => null])->first();
        if (! $guest) {
            return;
        }
        $userCart = $this->findOrCreate($userId, $sessionKey);
        foreach ($this->db->table('cart_items')->where('cart_id', $guest['id'])->get()->getResultArray() as $item) {
            $this->addItem((int) $userCart['id'], (int) $item['variant_id'], (int) $item['quantity'], (int) $item['unit_price_cents']);
        }
        $this->db->table('cart_items')->where('cart_id', $guest['id'])->delete();
        $this->update($guest['id'], ['status' => 'merged']);
    }

    public function addItem(int $cartId, int $variantId, int $quantity, int $unitPriceCents): void
    {
        $existing = $this->db->table('cart_items')->where(['cart_id' => $cartId, 'variant_id' => $variantId])->get()->getRowArray();
        if ($existing) {
            $this->db->table('cart_items')->where('id', $existing['id'])->update(['quantity' => min(999, $existing['quantity'] + $quantity), 'unit_price_cents' => $unitPriceCents]);

            return;
        }
        $this->db->table('cart_items')->insert([
            'cart_id'          => $cartId,
            'variant_id'       => $variantId,
            'quantity'         => min(999, $quantity),
            'unit_price_cents' => $unitPriceCents,
        ]);
        $this->update($cartId, ['status' => 'active']);
    }

    public function updateItemQuantity(int $cartId, int $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->db->table('cart_items')->where(['id' => $itemId, 'cart_id' => $cartId])->delete();

            return;
        }
        $this->db->table('cart_items')->where(['id' => $itemId, 'cart_id' => $cartId])->update(['quantity' => min(999, $quantity)]);
    }

    public function items(int $cartId): array
    {
        return $this->db->table('cart_items')
            ->select('cart_items.*, product_variants.sku, product_variants.option_name, product_variants.option1, product_variants.option2, product_variants.option3, product_variants.price_cents AS current_price_cents, product_variants.compare_at_cents, product_variants.weight_grams, product_variants.is_active, products.id AS product_id, products.title, products.slug, products.product_type, products.track_inventory, products.allow_backorder')
            ->select('(SELECT i.path FROM product_images i WHERE i.product_id = products.id ORDER BY i.position, i.id LIMIT 1) AS image', false)
            ->select('(SELECT COALESCE(SUM(il.quantity),0) FROM inventory_levels il WHERE il.variant_id = cart_items.variant_id) AS stock', false)
            ->join('product_variants', 'product_variants.id = cart_items.variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->where('cart_items.cart_id', $cartId)
            ->orderBy('cart_items.id')
            ->get()->getResultArray();
    }

    public function subtotalCents(int $cartId): int
    {
        $sum = 0;
        foreach ($this->items($cartId) as $item) {
            $sum += $item['unit_price_cents'] * $item['quantity'];
        }

        return $sum;
    }

    public static function variantLabel(array $item): string
    {
        $parts = array_filter([$item['option1'] ?? null, $item['option2'] ?? null, $item['option3'] ?? null]);

        return $parts ? implode(' / ', $parts) : (string) ($item['option_name'] ?? '');
    }

    public static function needsShipping(array $items): bool
    {
        foreach ($items as $i) {
            if (($i['product_type'] ?? 'physical') !== 'digital') {
                return true;
            }
        }

        return false;
    }
}
