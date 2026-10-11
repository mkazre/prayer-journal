<?php

namespace App\Modules\Studio\Controllers;

use App\Libraries\Uploads;
use App\Modules\Shop\Models\ProductModel;

/**
 * Collections (curated sets) and categories (the product taxonomy).
 */
class CatalogController extends StudioController
{
    public function index(): string
    {
        $this->requireAdmin();
        $model = new ProductModel();

        return $this->render('shop/catalog', ['title' => 'Collections & categories', 'active' => 'collections', 'collections' => $model->collections(), 'categories' => $model->categories()]);
    }

    public function saveCollection()
    {
        $this->requireAdmin();
        $db    = db_connect();
        $id    = (int) $this->post('id');
        $title = trim((string) $this->post('title'));
        if ($title === '') {
            return $this->failed('Give the collection a title.');
        }
        $slug = url_title((string) ($this->post('slug') ?: $title), '-', true);
        if ($db->table('collections')->where('slug', $slug)->where('id !=', $id)->countAllResults()) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        $data = ['title' => $title, 'slug' => $slug, 'description' => trim((string) $this->post('description')) ?: null, 'is_featured' => $this->bool('is_featured'), 'position' => (int) $this->post('position'), 'updated_at' => date('Y-m-d H:i:s')];
        try {
            if ($img = Uploads::image($this->request->getFile('image'), 'collections')) {
                $data['image_path'] = $img;
            }
        } catch (\RuntimeException $e) {
            return $this->failed($e->getMessage());
        }
        if ($id) {
            $db->table('collections')->where('id', $id)->update($data);
        } else {
            $db->table('collections')->insert($data + ['created_at' => date('Y-m-d H:i:s')]);
        }

        return $this->done('Collection saved.', [], site_url('studio/collections'));
    }

    public function deleteCollection(int $id)
    {
        $this->requireAdmin();
        db_connect()->table('collections')->where('id', $id)->delete();

        return $this->done('Collection deleted. Its products were kept.', [], site_url('studio/collections'));
    }

    public function saveCategory()
    {
        $this->requireAdmin();
        $db   = db_connect();
        $id   = (int) $this->post('id');
        $name = trim((string) $this->post('name'));
        if ($name === '') {
            return $this->failed('Give the category a name.');
        }
        $slug = url_title((string) ($this->post('slug') ?: $name), '-', true);
        if ($db->table('product_categories')->where('slug', $slug)->where('id !=', $id)->countAllResults()) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        $parent = (int) $this->post('parent_id') ?: null;
        $data   = ['name' => $name, 'slug' => $slug, 'description' => trim((string) $this->post('description')) ?: null, 'parent_id' => $parent !== $id ? $parent : null, 'position' => (int) $this->post('position')];
        if ($id) {
            $db->table('product_categories')->where('id', $id)->update($data);
        } else {
            $db->table('product_categories')->insert($data);
        }

        return $this->done('Category saved.', [], site_url('studio/collections#categories'));
    }

    public function deleteCategory(int $id)
    {
        $this->requireAdmin();
        $db = db_connect();
        $db->table('products')->where('category_id', $id)->update(['category_id' => null]);
        $db->table('product_categories')->where('parent_id', $id)->update(['parent_id' => null]);
        $db->table('product_categories')->where('id', $id)->delete();

        return $this->done('Category deleted.', [], site_url('studio/collections#categories'));
    }
}
