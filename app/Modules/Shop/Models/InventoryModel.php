<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class InventoryModel extends Model
{
    protected $table = 'inventory_levels';

    public function adjust(int $variantId, int $locationId, int $change, string $reason, ?string $reference = null): void
    {
        $level = $this->db->table('inventory_levels')->where(['variant_id' => $variantId, 'location_id' => $locationId])->get()->getRowArray();

        if ($level) {
            $this->db->table('inventory_levels')->where('id', $level['id'])->update([
                'quantity'   => $level['quantity'] + $change,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->table('inventory_levels')->insert([
                'variant_id'  => $variantId,
                'location_id' => $locationId,
                'quantity'    => $change,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->table('stock_movements')->insert([
            'variant_id'  => $variantId,
            'location_id' => $locationId,
            'change'      => $change,
            'reason'      => $reason,
            'reference'   => $reference,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function levelsForProduct(int $productId): array
    {
        return $this->db->table('inventory_levels')
            ->select('inventory_levels.*, product_variants.sku, inventory_locations.name as location_name')
            ->join('product_variants', 'product_variants.id = inventory_levels.variant_id')
            ->join('inventory_locations', 'inventory_locations.id = inventory_levels.location_id')
            ->where('product_variants.product_id', $productId)
            ->get()->getResultArray();
    }
}
