<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class WishlistModel extends Model
{
    protected $table         = 'product_wishlists';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'product_id', 'created_at'];

    public function toggle(int $userId, int $productId): bool
    {
        $existing = $this->where(['user_id' => $userId, 'product_id' => $productId])->first();
        if ($existing) {
            $this->delete($existing['id']);

            return false;
        }
        $this->insert(['user_id' => $userId, 'product_id' => $productId, 'created_at' => date('Y-m-d H:i:s')]);

        return true;
    }

    public function forUser(int $userId): array
    {
        return $this->db->table('product_wishlists')
            ->select('product_wishlists.*, products.title, products.slug')
            ->join('products', 'products.id = product_wishlists.product_id')
            ->where('product_wishlists.user_id', $userId)
            ->get()->getResultArray();
    }

    public function has(int $userId, int $productId): bool
    {
        return (bool) $this->where(['user_id' => $userId, 'product_id' => $productId])->first();
    }
}
