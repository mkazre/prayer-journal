<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\HtmlSanitizer;
use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Models\ProductModel;
use App\Modules\Shop\Models\ProductVariantModel;
use App\Modules\Shop\Models\SubscriptionPlanModel;
use App\Modules\Shop\Models\WishlistModel;

/**
 * Catalogue, cart and wishlist for the mobile app. Checkout itself happens
 * on the website (the app opens the cart page), so payments stay on one
 * audited code path.
 */
class ShopApiController extends BaseApiController
{
    public function products()
    {
        $rows = (new ProductModel())->storefront([
            'q' => trim((string) $this->request->getGet('q')), 'category' => (string) $this->request->getGet('category'),
            'collection' => (string) $this->request->getGet('collection'), 'sort' => (string) ($this->request->getGet('sort') ?: 'featured'),
        ]);

        return $this->ok(array_map(static fn ($p) => [
            'id' => (int) $p['id'], 'slug' => $p['slug'], 'title' => $p['title'], 'short_description' => $p['short_description'],
            'product_type' => $p['product_type'], 'image_url' => ! empty($p['image']) ? base_url($p['image']) : null,
            'price_cents' => (int) $p['price_cents'], 'compare_at_cents' => ! empty($p['compare_at_cents']) ? (int) $p['compare_at_cents'] : null,
            'rating' => isset($p['avg_rating']) ? round((float) $p['avg_rating'], 1) : null, 'is_featured' => (bool) $p['is_featured'],
            'in_stock' => $p['product_type'] === 'digital' || ! (int) $p['track_inventory'] || (int) $p['allow_backorder'] || (int) $p['stock'] > 0,
        ], $rows));
    }

    public function product(string $slug)
    {
        $model   = new ProductModel();
        $product = $model->where('slug', $slug)->where('status', 'published')->first();
        if (! $product) {
            return $this->fail('Product not found.', 404);
        }
        $p    = $model->withVariantsAndImages((int) $product['id']);
        $user = $this->optionalUser();

        return $this->ok([
            'id' => (int) $p['id'], 'slug' => $p['slug'], 'title' => $p['title'], 'short_description' => $p['short_description'],
            'description_html' => HtmlSanitizer::clean((string) $p['description']), 'product_type' => $p['product_type'], 'brand' => $p['brand'],
            'images' => array_map(static fn ($i) => ['url' => base_url($i['path']), 'alt' => $i['alt'] ?? ''], $p['images']),
            'options' => $p['option_defs'],
            'variants' => array_values(array_map(static fn ($v) => [
                'id' => (int) $v['id'], 'sku' => $v['sku'], 'label' => implode(' / ', array_filter([$v['option1'], $v['option2'], $v['option3']])) ?: ($v['option_name'] ?: 'Default'),
                'options' => array_values(array_filter([$v['option1'], $v['option2'], $v['option3']])),
                'price_cents' => (int) $v['price_cents'], 'compare_at_cents' => $v['compare_at_cents'] ? (int) $v['compare_at_cents'] : null,
                'in_stock' => $p['product_type'] === 'digital' || ! (int) $p['track_inventory'] || (int) $p['allow_backorder'] || (int) $v['stock'] > 0,
            ], array_filter($p['variants'], static fn ($v) => (int) ($v['is_active'] ?? 1) === 1))),
            'reviews' => array_map(static fn ($r) => ['rating' => (int) $r['rating'], 'title' => $r['title'] ?? null, 'body' => $r['body'], 'author' => $r['username'] ?? 'Customer', 'created_at' => $r['created_at']], $model->reviews((int) $p['id'])),
            'wishlisted' => $user ? (new WishlistModel())->has((int) $user->id, (int) $p['id']) : false,
            'web_url' => site_url('shop/' . $p['slug']),
        ]);
    }

    public function plans()
    {
        return $this->ok((new SubscriptionPlanModel())->active());
    }

    public function cart()
    {
        $user  = $this->currentUser();
        $model = new CartModel();
        $cart  = $model->findOrCreate((int) $user->id, 'api-' . $user->id);
        $items = $model->items((int) $cart['id']);

        return $this->ok([
            'items' => array_map(static fn ($i) => [
                'id' => (int) $i['id'], 'variant_id' => (int) $i['variant_id'], 'title' => $i['title'], 'slug' => $i['slug'],
                'variant' => CartModel::variantLabel($i), 'quantity' => (int) $i['quantity'], 'unit_price_cents' => (int) $i['current_price_cents'],
                'image_url' => $i['image'] ? base_url($i['image']) : null,
            ], $items),
            'count' => array_sum(array_column($items, 'quantity')),
            'subtotal_cents' => $model->subtotalCents((int) $cart['id']),
            'currency' => store_currency(),
            'checkout_url' => site_url('cart'),
        ]);
    }

    public function addToCart(int $variantId)
    {
        $user    = $this->currentUser();
        $variant = (new ProductVariantModel())->find($variantId);
        if (! $variant || ! (int) ($variant['is_active'] ?? 1)) {
            return $this->fail('That option is not available.', 404);
        }
        $product = (new ProductModel())->find((int) $variant['product_id']);
        if (! $product || $product['status'] !== 'published') {
            return $this->fail('That product is not available.', 404);
        }
        $quantity = min(99, max(1, (int) ($this->body()['quantity'] ?? 1)));
        $model    = new CartModel();
        $cart     = $model->findOrCreate((int) $user->id, 'api-' . $user->id);
        $model->addItem((int) $cart['id'], $variantId, $quantity, (int) $variant['price_cents']);

        return $this->ok(['message' => 'Added to cart.', 'count' => array_sum(array_column($model->items((int) $cart['id']), 'quantity'))]);
    }

    public function updateCartItem(int $itemId)
    {
        $user  = $this->currentUser();
        $model = new CartModel();
        $cart  = $model->findOrCreate((int) $user->id, 'api-' . $user->id);
        $model->updateItemQuantity((int) $cart['id'], $itemId, (int) ($this->body()['quantity'] ?? 0));

        return $this->cart();
    }

    public function toggleWishlist(int $productId)
    {
        $added = (new WishlistModel())->toggle((int) $this->currentUser()->id, $productId);

        return $this->ok(['wishlisted' => $added]);
    }
}
