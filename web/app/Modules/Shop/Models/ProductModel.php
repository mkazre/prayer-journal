<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['vendor_id', 'category_id', 'title', 'slug', 'description', 'status'];
    protected $useTimestamps    = true;

    public function withVariantsAndImages(int $id): ?array
    {
        $product = $this->find($id);
        if (! $product) {
            return null;
        }
        $product['variants'] = $this->db->table('product_variants')->where('product_id', $id)->get()->getResultArray();
        $product['images']   = $this->db->table('product_images')->where('product_id', $id)->orderBy('position', 'asc')->get()->getResultArray();

        return $product;
    }

    public function published()
    {
        return $this->where('status', 'published');
    }
}
