<?php

namespace App\Modules\Studio\Controllers;

use App\Libraries\HtmlSanitizer;
use App\Libraries\Uploads;
use App\Modules\Shop\Models\InventoryModel;
use App\Modules\Shop\Models\ProductModel;

/**
 * Product catalogue management: details, media, options & variants,
 * pricing, inventory, organisation and SEO. Vendors see only their own
 * products.
 */
class ProductController extends StudioController
{
    public function index(): string
    {
        $this->requireVendor();
        $filters = [
            'all_statuses' => true,
            'status'       => in_array($this->request->getGet('status'), ['published', 'draft'], true) ? $this->request->getGet('status') : null,
            'q'            => trim((string) $this->request->getGet('q')),
            'category'     => (string) $this->request->getGet('category'),
            'type'         => (string) $this->request->getGet('type'),
            'sort'         => 'updated',
            'vendor_id'    => $this->isManager() ? null : $this->userId(),
        ];
        $model = new ProductModel();
        $page  = $model->storefrontPage($filters, (int) $this->request->getGet('page') ?: 1, 25);

        return $this->render('products/index', ['title' => 'Products', 'active' => 'products', 'page' => $page, 'filters' => $filters, 'categories' => $model->categories()]);
    }

    public function create()
    {
        $this->requireVendor();
        if ($this->request->getMethod() === 'POST') {
            return $this->save(null);
        }
        $product = ['id' => null, 'title' => '', 'slug' => '', 'short_description' => '', 'description' => '', 'status' => 'draft', 'product_type' => 'physical',
            'brand' => '', 'tags' => '', 'options' => null, 'seo_title' => '', 'seo_description' => '', 'is_featured' => 0, 'track_inventory' => 1,
            'allow_backorder' => 0, 'category_id' => null, 'variants' => [], 'images' => [], 'option_defs' => []];

        return $this->form($product);
    }

    public function edit(int $id)
    {
        $product = $this->owned($id);
        if ($this->request->getMethod() === 'POST') {
            return $this->save($product);
        }

        return $this->form((new ProductModel())->withVariantsAndImages($id));
    }

    public function delete(int $id)
    {
        $this->owned($id);
        (new ProductModel())->delete($id);

        return $this->done('Product deleted.', [], site_url('studio/products'));
    }

    public function duplicate(int $id)
    {
        $product = (new ProductModel())->withVariantsAndImages($this->owned($id)['id']);
        $model   = new ProductModel();
        $db      = db_connect();
        $copy    = array_intersect_key($product, array_flip(['vendor_id', 'category_id', 'description', 'short_description', 'product_type', 'brand', 'tags', 'options', 'seo_title', 'seo_description', 'track_inventory', 'allow_backorder']));
        $copy['title']  = $product['title'] . ' (copy)';
        $copy['slug']   = $model->uniqueSlug($copy['title']);
        $copy['status'] = 'draft';
        $newId = (int) $model->insert($copy, true);
        foreach ($product['variants'] as $v) {
            unset($v['id'], $v['stock']);
            $v['product_id'] = $newId;
            $v['sku']        = $v['sku'] . '-COPY';
            $db->table('product_variants')->insert($v);
        }
        foreach ($product['images'] as $img) {
            $db->table('product_images')->insert(['product_id' => $newId, 'path' => $img['path'], 'position' => $img['position'], 'alt' => $img['alt']]);
        }
        foreach ($model->collectionIds((int) $product['id']) as $cid) {
            $db->table('collection_products')->insert(['collection_id' => $cid, 'product_id' => $newId, 'position' => 0]);
        }

        return redirect()->to(site_url('studio/products/' . $newId))->with('message', 'Duplicated as a draft (stock starts at zero).');
    }

    public function bulk()
    {
        $this->requireVendor();
        $ids    = array_map('intval', (array) $this->post('ids', []));
        $action = (string) $this->post('action');
        $model  = new ProductModel();
        $count  = 0;
        foreach ($ids as $id) {
            $p = $model->find($id);
            if (! $p || (! $this->isManager() && (int) $p['vendor_id'] !== $this->userId())) {
                continue;
            }
            match ($action) {
                'publish' => $model->update($id, ['status' => 'published', 'published_at' => $p['published_at'] ?: date('Y-m-d H:i:s')]),
                'draft'   => $model->update($id, ['status' => 'draft']),
                'feature' => $model->update($id, ['is_featured' => 1]),
                'unfeature' => $model->update($id, ['is_featured' => 0]),
                'delete'  => $model->delete($id),
                default   => null,
            };
            $count++;
        }

        return redirect()->back()->with('message', $count . ' product' . ($count === 1 ? '' : 's') . ' updated.');
    }

    public function deleteImage(int $imageId)
    {
        $img = db_connect()->table('product_images')->where('id', $imageId)->get()->getRowArray();
        if (! $img) {
            $this->notFound();
        }
        $this->owned((int) $img['product_id']);
        db_connect()->table('product_images')->where('id', $imageId)->delete();
        if (! db_connect()->table('product_images')->where('path', $img['path'])->countAllResults()) {
            Uploads::delete($img['path']);
        }

        return $this->done('Image removed.');
    }

    public function inventory(): string
    {
        $this->requireVendor();
        $db = db_connect();
        $b  = $db->table('product_variants v')
            ->select('v.id, v.sku, v.option1, v.option2, v.option3, v.price_cents, p.id AS product_id, p.title, p.status, COALESCE(SUM(il.quantity), 0) AS qty', false)
            ->join('products p', 'p.id = v.product_id')
            ->join('inventory_levels il', 'il.variant_id = v.id', 'left')
            ->where('p.deleted_at IS NULL')->where('p.track_inventory', 1)->where('p.product_type !=', 'digital');
        if (! $this->isManager()) {
            $b->where('p.vendor_id', $this->userId());
        }
        if ($q = trim((string) $this->request->getGet('q'))) {
            $b->groupStart()->like('p.title', $q)->orLike('v.sku', $q)->groupEnd();
        }
        $rows = $b->groupBy('v.id, v.sku, v.option1, v.option2, v.option3, v.price_cents, p.id, p.title, p.status')->orderBy('qty')->get()->getResultArray();
        $moves = $db->table('stock_movements m')->select('m.*, v.sku, p.title')->join('product_variants v', 'v.id = m.variant_id')->join('products p', 'p.id = v.product_id')
            ->orderBy('m.id', 'DESC')->limit(15)->get()->getResultArray();

        return $this->render('products/inventory', ['title' => 'Inventory', 'active' => 'inventory', 'rows' => $rows, 'moves' => $moves, 'q' => $q ?? '']);
    }

    public function adjustInventory()
    {
        $this->requireVendor();
        $variantId = (int) $this->post('variant_id');
        $variant   = db_connect()->table('product_variants')->where('id', $variantId)->get()->getRowArray();
        if (! $variant) {
            $this->notFound();
        }
        $this->owned((int) $variant['product_id']);
        $location = $this->location();
        $current  = (int) (db_connect()->table('inventory_levels')->selectSum('quantity')->where('variant_id', $variantId)->get()->getRow('quantity') ?? 0);
        $mode     = (string) $this->post('mode');
        $value    = (int) $this->post('quantity');
        $change   = $mode === 'set' ? $value - $current : $value;
        if ($change !== 0) {
            (new InventoryModel())->adjust($variantId, $location, $change, trim((string) $this->post('reason')) ?: 'adjustment');
        }

        return $this->done('Stock updated to ' . ($current + $change) . '.', ['quantity' => $current + $change]);
    }

    private function form(array $product): string
    {
        $model = new ProductModel();

        return $this->render('products/form', [
            'title'       => $product['id'] ? 'Edit product' : 'New product',
            'active'      => 'products',
            'product'     => $product,
            'categories'  => $model->categories(),
            'collections' => $model->collections(),
            'inCollections' => $product['id'] ? $model->collectionIds((int) $product['id']) : [],
            'sales'       => $product['id'] ? db_connect()->table('order_items')->selectSum('quantity')->where('product_id', $product['id'])->get()->getRow('quantity') : 0,
        ]);
    }

    private function save(?array $product)
    {
        $p     = (array) $this->request->getPost();
        $model = new ProductModel();
        $db    = db_connect();
        $title = trim((string) ($p['title'] ?? ''));
        if ($title === '') {
            return redirect()->back()->withInput()->with('error', 'Give the product a title.');
        }
        $slug = url_title((string) (($p['slug'] ?? '') ?: $title), '-', true);
        if ($model->withDeleted()->where('slug', $slug)->where('id !=', $product['id'] ?? 0)->first()) {
            $slug = $model->uniqueSlug($slug, $product['id'] ?? null);
        }

        // Option definitions: name + comma-separated values.
        $options = [];
        foreach (array_slice((array) ($p['options'] ?? []), 0, 3) as $o) {
            $name   = trim((string) ($o['name'] ?? ''));
            $values = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($o['values'] ?? ''))), 'strlen')));
            if ($name !== '' && $values) {
                $options[] = ['name' => mb_substr($name, 0, 40), 'values' => $values];
            }
        }
        $type   = in_array($p['product_type'] ?? '', ['physical', 'digital'], true) ? $p['product_type'] : 'physical';
        $status = in_array($p['status'] ?? '', ['published', 'draft'], true) ? $p['status'] : 'draft';
        $data   = [
            'title'             => mb_substr($title, 0, 200),
            'slug'              => $slug,
            'short_description' => trim(mb_substr((string) ($p['short_description'] ?? ''), 0, 500)) ?: null,
            'description'       => HtmlSanitizer::clean((string) ($p['description'] ?? '')),
            'status'            => $status,
            'product_type'      => $type,
            'brand'             => trim((string) ($p['brand'] ?? '')) ?: null,
            'tags'              => trim((string) ($p['tags'] ?? '')) ?: null,
            'category_id'       => (int) ($p['category_id'] ?? 0) ?: null,
            'options'           => $options ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'seo_title'         => trim((string) ($p['seo_title'] ?? '')) ?: null,
            'seo_description'   => trim((string) ($p['seo_description'] ?? '')) ?: null,
            'track_inventory'   => $type === 'digital' ? 0 : (! empty($p['track_inventory']) ? 1 : 0),
            'allow_backorder'   => ! empty($p['allow_backorder']) ? 1 : 0,
            'flipbook'          => $type === 'digital' && ! empty($p['flipbook']) ? 1 : 0,
        ];
        // A digital product needs at least one way to deliver it.
        $data['allow_download'] = $type !== 'digital' || ! $data['flipbook'] || ! empty($p['allow_download']) ? 1 : 0;
        if ($this->isManager()) {
            $data['is_featured'] = ! empty($p['is_featured']) ? 1 : 0;
        }
        if ($status === 'published' && empty($product['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        $db->transStart();
        if ($product) {
            $model->update($product['id'], $data);
            $id = (int) $product['id'];
        } else {
            $data['vendor_id'] = $this->isManager() ? null : $this->userId();
            $id                = (int) $model->insert($data, true);
        }

        // Variants: update existing rows by id, add new ones, deactivate removed ones (keeps order history intact).
        $location = $this->location();
        $seen     = [];
        $existing = array_column($db->table('product_variants')->where('product_id', $id)->get()->getResultArray(), null, 'id');
        foreach (array_values((array) ($p['variants'] ?? [])) as $i => $v) {
            $price = (int) round((float) ($v['price'] ?? 0) * 100);
            $row   = [
                'product_id'       => $id,
                'sku'              => trim((string) ($v['sku'] ?? '')) ?: strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $title), 0, 8)) . '-' . $id . '-' . ($i + 1),
                'option1'          => trim((string) ($v['option1'] ?? '')) ?: null,
                'option2'          => trim((string) ($v['option2'] ?? '')) ?: null,
                'option3'          => trim((string) ($v['option3'] ?? '')) ?: null,
                'option_name'      => implode(' / ', array_filter([$v['option1'] ?? null, $v['option2'] ?? null, $v['option3'] ?? null])) ?: null,
                'price_cents'      => max(0, $price),
                'compare_at_cents' => ($v['compare_at'] ?? '') !== '' ? (int) round((float) $v['compare_at'] * 100) : null,
                'weight_grams'     => ($v['weight'] ?? '') !== '' ? max(0, (int) $v['weight']) : null,
                'currency'         => store_currency(),
                'position'         => $i,
                'is_active'        => 1,
            ];
            $vid = (int) ($v['id'] ?? 0);
            if ($vid && isset($existing[$vid])) {
                $db->table('product_variants')->where('id', $vid)->update($row);
            } else {
                $row['created_at'] = date('Y-m-d H:i:s');
                $db->table('product_variants')->insert($row);
                $vid = (int) $db->insertID();
            }
            $seen[] = $vid;
            if ($type === 'physical' && ($v['stock'] ?? '') !== '') {
                $current = (int) ($db->table('inventory_levels')->selectSum('quantity')->where('variant_id', $vid)->get()->getRow('quantity') ?? 0);
                $target  = (int) $v['stock'];
                if ($target !== $current) {
                    (new InventoryModel())->adjust($vid, $location, $target - $current, 'adjustment', 'product editor');
                }
            }
            try {
                if ($type === 'digital' && ($file = Uploads::file($this->request->getFile('variant_file_' . $i), 'digital', 204800, true))) {
                    $db->table('product_variants')->where('id', $vid)->update(['digital_file_path' => $file]);
                }
            } catch (\RuntimeException $e) {
                $db->transRollback();

                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }
        if ($seen === []) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', 'Add at least one variant with a price.');
        }
        $removed = array_diff(array_keys($existing), $seen);
        if ($removed) {
            $db->table('product_variants')->whereIn('id', $removed)->update(['is_active' => 0]);
        }

        // Collections.
        $db->table('collection_products')->where('product_id', $id)->delete();
        foreach (array_map('intval', (array) ($p['collections'] ?? [])) as $cid) {
            $db->table('collection_products')->insert(['collection_id' => $cid, 'product_id' => $id, 'position' => 0]);
        }

        // Images: new uploads, alt text and order.
        foreach ((array) ($p['image_order'] ?? []) as $pos => $imgId) {
            $db->table('product_images')->where(['id' => (int) $imgId, 'product_id' => $id])->update(['position' => $pos, 'alt' => trim((string) ($p['image_alt'][$imgId] ?? '')) ?: null]);
        }
        $next = (int) $db->table('product_images')->selectMax('position')->where('product_id', $id)->get()->getRow('position') + 1;
        foreach ($this->request->getFileMultiple('images') ?? [] as $file) {
            try {
                if ($path = Uploads::image($file, 'products', 8192)) {
                    $db->table('product_images')->insert(['product_id' => $id, 'path' => $path, 'position' => $next++, 'alt' => $title]);
                }
            } catch (\RuntimeException $e) {
                session()->setFlashdata('error', $e->getMessage());
            }
        }
        $db->transComplete();

        return redirect()->to(site_url('studio/products/' . $id))->with('message', $product ? 'Product saved.' : 'Product created.');
    }

    private function owned(int $id): array
    {
        $this->requireVendor();
        $product = (new ProductModel())->find($id);
        if (! $product || (! $this->isManager() && (int) $product['vendor_id'] !== $this->userId())) {
            $this->notFound();
        }

        return $product;
    }

    private function location(): int
    {
        $db = db_connect();
        $id = $db->table('inventory_locations')->orderBy('id')->get()->getRow('id');
        if (! $id) {
            $db->table('inventory_locations')->insert(['name' => 'Main warehouse']);
            $id = $db->insertID();
        }

        return (int) $id;
    }
}
