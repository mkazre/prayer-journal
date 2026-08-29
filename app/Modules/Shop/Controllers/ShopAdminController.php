<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\DiscountModel;
use App\Modules\Shop\Models\InventoryModel;
use App\Modules\Shop\Models\OrderModel;
use App\Modules\Shop\Models\ProductModel;
use App\Modules\Shop\Models\ProductVariantModel;
use App\Modules\Shop\Models\SubscriptionPlanModel;

class ShopAdminController extends BaseController
{
    public function products(): string
    {
        $vendorId = auth()->user()->inGroup('vendor') && ! auth()->user()->inGroup('admin', 'superadmin') ? auth()->id() : null;
        $model    = (new ProductModel())->orderBy('created_at', 'desc');
        if ($vendorId) {
            $model->where('vendor_id', $vendorId);
        }

        return view('Shop/admin/products', ['title' => 'Products', 'products' => $model->findAll()]);
    }

    public function createProduct()
    {
        if ($this->request->getMethod() === 'POST') {
            $productModel = new ProductModel();
            $productId    = $productModel->insert([
                'vendor_id'   => auth()->user()->inGroup('vendor') ? auth()->id() : null,
                'title'       => $this->request->getPost('title'),
                'slug'        => url_title($this->request->getPost('title'), '-', true),
                'description' => $this->request->getPost('description'),
                'status'      => 'draft',
            ], true);

            $variantModel = new ProductVariantModel();
            $variantId    = $variantModel->insert([
                'product_id'  => $productId,
                'sku'         => strtoupper(url_title($this->request->getPost('title'), '', true)) . '-' . $productId,
                'price_cents' => (int) round((float) $this->request->getPost('price') * 100),
            ], true);

            $location = $this->db()->table('inventory_locations')->get()->getRowArray();
            if (! $location) {
                $this->db()->table('inventory_locations')->insert(['name' => 'Main Warehouse']);
                $location = $this->db()->table('inventory_locations')->get()->getRowArray();
            }
            (new InventoryModel())->adjust($variantId, (int) $location['id'], (int) $this->request->getPost('stock'), 'initial_stock');

            return redirect()->to(site_url('shop-admin/products'))->with('message', 'Product created.');
        }

        return view('Shop/admin/product_form', ['title' => 'New Product']);
    }

    public function publishProduct(int $id)
    {
        (new ProductModel())->update($id, ['status' => 'published']);

        return redirect()->to(site_url('shop-admin/products'))->with('message', 'Product published.');
    }

    public function inventory(): string
    {
        $levels = (new InventoryModel())->db->table('inventory_levels')
            ->select('inventory_levels.*, product_variants.sku, products.title, inventory_locations.name as location_name')
            ->join('product_variants', 'product_variants.id = inventory_levels.variant_id')
            ->join('products', 'products.id = product_variants.product_id')
            ->join('inventory_locations', 'inventory_locations.id = inventory_levels.location_id')
            ->get()->getResultArray();

        return view('Shop/admin/inventory', ['title' => 'Inventory', 'levels' => $levels]);
    }

    public function discounts(): string
    {
        return view('Shop/admin/discounts', ['title' => 'Discounts', 'discounts' => (new DiscountModel())->findAll()]);
    }

    public function createDiscount()
    {
        (new DiscountModel())->insert([
            'code'  => strtoupper((string) $this->request->getPost('code')),
            'type'  => $this->request->getPost('type'),
            'value' => (int) $this->request->getPost('value'),
            'expires_at' => $this->request->getPost('expires_at') ?: null,
        ]);

        return redirect()->to(site_url('shop-admin/discounts'))->with('message', 'Discount created.');
    }

    public function orders(): string
    {
        $orders = (new OrderModel())->orderBy('created_at', 'desc')->findAll();

        return view('Shop/admin/orders', ['title' => 'Orders', 'orders' => $orders]);
    }

    public function fulfillOrder(int $orderId)
    {
        $this->db()->table('order_fulfillments')->where('order_id', $orderId)->update([
            'status'          => 'shipped',
            'tracking_number' => $this->request->getPost('tracking_number'),
            'carrier'         => $this->request->getPost('carrier'),
            'shipped_at'      => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('shop-admin/orders'))->with('message', 'Order marked as shipped.');
    }

    public function reports(): string
    {
        $db = $this->db();

        $salesByDay = $db->table('orders')
            ->select("DATE(created_at) as day, SUM(total_cents) as total_cents, COUNT(*) as order_count")
            ->where('status', 'paid')
            ->groupBy('day')
            ->orderBy('day', 'desc')
            ->limit(30)
            ->get()->getResultArray();

        $topProducts = $db->table('order_items')
            ->select('title_snapshot, SUM(quantity) as units_sold')
            ->groupBy('title_snapshot')
            ->orderBy('units_sold', 'desc')
            ->limit(10)
            ->get()->getResultArray();

        return view('Shop/admin/reports', ['title' => 'Sales Reports', 'salesByDay' => $salesByDay, 'topProducts' => $topProducts]);
    }

    public function plans(): string
    {
        return view('Shop/admin/plans', ['title' => 'Subscription Plans', 'plans' => (new SubscriptionPlanModel())->findAll()]);
    }

    public function createPlan()
    {
        (new SubscriptionPlanModel())->insert([
            'name'             => $this->request->getPost('name'),
            'type'             => $this->request->getPost('type'),
            'billing_interval' => $this->request->getPost('type') === 'subscription' ? $this->request->getPost('billing_interval') : null,
            'price_cents'      => (int) round((float) $this->request->getPost('price') * 100),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('shop-admin/plans'))->with('message', 'Plan created.');
    }

    public function announcement()
    {
        $model = new \App\Modules\Core\Models\AppSettingModel();
        if ($this->request->getMethod() === 'POST') {
            $model->put('announcement', (string) $this->request->getPost('announcement'));

            return redirect()->to(site_url('shop-admin/announcement'))->with('message', 'Announcement updated.');
        }

        return view('Shop/admin/announcement', ['title' => 'Site Announcement', 'current' => $model->get('announcement', '')]);
    }

    protected function db()
    {
        return \Config\Database::connect();
    }
}
