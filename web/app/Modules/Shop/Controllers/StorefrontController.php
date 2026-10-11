<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\ProductModel;
use App\Modules\Shop\Models\WishlistModel;

class StorefrontController extends BaseController
{
    public function index(): string
    {
        return $this->listing(null);
    }

    public function collection(string $slug): string
    {
        $collection = db_connect()->table('collections')->where('slug', $slug)->get()->getRowArray();
        if (! $collection) {
            $this->notFound();
        }

        return $this->listing($collection);
    }

    private function listing(?array $collection): string
    {
        $model   = new ProductModel();
        $filters = [
            'q'          => trim((string) $this->request->getGet('q')),
            'category'   => (string) $this->request->getGet('category'),
            'collection' => $collection['slug'] ?? (string) $this->request->getGet('collection'),
            'type'       => (string) $this->request->getGet('type'),
            'min'        => (string) $this->request->getGet('min'),
            'max'        => (string) $this->request->getGet('max'),
            'in_stock'   => (string) $this->request->getGet('in_stock'),
            'on_sale'    => (string) $this->request->getGet('on_sale'),
            'sort'       => isset(ProductModel::SORTS[$this->request->getGet('sort')]) ? (string) $this->request->getGet('sort') : 'featured',
        ];
        $page = $model->storefrontPage($filters, (int) $this->request->getGet('page') ?: 1, 12);

        return view('Shop/storefront/index', [
            'title'       => $collection['title'] ?? 'Shop',
            'collection'  => $collection,
            'page'        => $page,
            'filters'     => $filters,
            'categories'  => $model->categories(),
            'collections' => $model->collections(),
            'featured'    => $collection ? [] : $model->collections(true),
            'wishlisted'  => $this->wishlistIds(),
        ]);
    }

    public function show(string $slug)
    {
        $model   = new ProductModel();
        $product = $model->where('slug', $slug)->first();
        if (! $product || ($product['status'] !== 'published' && ! is_staff('superadmin', 'admin', 'vendor'))) {
            $this->notFound();
        }
        $product  = $model->withVariantsAndImages((int) $product['id']);
        $reviews  = $model->reviews((int) $product['id']);
        $related  = $model->storefront(['category' => $product['category_id'] ? db_connect()->table('product_categories')->select('slug')->where('id', $product['category_id'])->get()->getRow('slug') : null, 'exclude' => $product['id'], 'limit' => 4]);
        if (count($related) < 4) {
            $related = array_merge($related, $model->storefront(['exclude' => $product['id'], 'limit' => 4 - count($related), 'sort' => 'best', 'ids' => null]));
            $related = array_values(array_slice(array_filter($related, static fn ($p) => (int) $p['id'] !== (int) $product['id']), 0, 4));
        }
        $breakdown = array_fill(1, 5, 0);
        foreach ($reviews as $r) {
            $breakdown[max(1, min(5, (int) $r['rating']))]++;
        }
        $purchased = auth()->loggedIn() && db_connect()->table('order_items oi')->join('orders o', 'o.id = oi.order_id')
            ->where('o.user_id', $this->userId())->where('oi.product_id', $product['id'])->whereIn('o.status', ['paid', 'processing', 'shipped', 'completed'])->countAllResults() > 0;

        return view('Shop/storefront/show', [
            'title'           => $product['seo_title'] ?: $product['title'],
            'metaDescription' => $product['seo_description'] ?: ($product['short_description'] ?: excerpt($product['description'], 160)),
            'ogImage'         => isset($product['images'][0]) ? base_url($product['images'][0]['path']) : null,
            'product'         => $product,
            'reviews'         => $reviews,
            'breakdown'       => $breakdown,
            'avgRating'       => $reviews ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : null,
            'related'         => $related,
            'wishlisted'      => in_array((int) $product['id'], $this->wishlistIds(), true),
            'wishlistIds'     => $this->wishlistIds(),
            'purchased'       => $purchased,
            'myReview'        => auth()->loggedIn() ? db_connect()->table('product_reviews')->where(['product_id' => $product['id'], 'user_id' => $this->userId()])->get()->getRowArray() : null,
        ]);
    }

    public function review(string $slug)
    {
        $product = (new ProductModel())->where('slug', $slug)->where('status', 'published')->first();
        if (! $product) {
            $this->notFound();
        }
        $rating = (int) $this->request->getPost('rating');
        if ($rating < 1 || $rating > 5) {
            return $this->failed('Choose a rating from 1 to 5 stars.');
        }
        $db   = db_connect();
        $data = [
            'rating' => $rating,
            'title'  => trim(mb_substr((string) $this->request->getPost('title'), 0, 150)) ?: null,
            'body'   => trim(mb_substr((string) $this->request->getPost('body'), 0, 3000)) ?: null,
            'status' => 'published',
        ];
        $existing = $db->table('product_reviews')->where(['product_id' => $product['id'], 'user_id' => $this->userId()])->get()->getRowArray();
        if ($existing) {
            $db->table('product_reviews')->where('id', $existing['id'])->update($data);
        } else {
            $db->table('product_reviews')->insert($data + ['product_id' => $product['id'], 'user_id' => $this->userId(), 'created_at' => date('Y-m-d H:i:s')]);
        }

        return $this->done('Thanks for your review!', [], site_url('shop/' . $slug . '#reviews'));
    }

    private function wishlistIds(): array
    {
        if (! auth()->loggedIn()) {
            return [];
        }

        return array_map('intval', array_column((new WishlistModel())->where('user_id', $this->userId())->findAll(), 'product_id'));
    }
}
