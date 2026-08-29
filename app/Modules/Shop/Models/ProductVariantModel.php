<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table         = 'product_variants';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['product_id', 'sku', 'option_name', 'price_cents', 'currency', 'weight_grams'];
    protected $useTimestamps = true;

    public function stockOnHand(int $variantId): int
    {
        return (int) ($this->db->table('inventory_levels')->selectSum('quantity')->where('variant_id', $variantId)->get()->getRow()->quantity ?? 0);
    }
}
