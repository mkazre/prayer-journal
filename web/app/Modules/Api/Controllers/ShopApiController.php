<?php

namespace App\Modules\Api\Controllers;

use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Models\ProductModel;
use App\Modules\Shop\Models\SubscriptionPlanModel;
use App\Modules\Shop\Models\WishlistModel;

class ShopApiController extends BaseApiController
{
    public function products()
    {
        return $this->ok((new ProductModel())->published()->orderBy('created_at', 'desc')->findAll());
    }

    public function product(string $slug)
    {
        $product = (new ProductModel())->where('slug', $slug)->first();
        if (! $product) {
            return $this->fail('Product not found.', 404);
        }

        return $this->ok((new ProductModel())->withVariantsAndImages($product['id']));
    }

    public function plans()
    {
        return $this->ok((new SubscriptionPlanModel())->active());
    }

    public function cart()
    {
        $user      = $this->currentUser();
        $cartModel = new CartModel();
        $cart      = $cartModel->findOrCreate($user->id, 'api-' . $user->id);

        return $this->ok(['cart' => $cart, 'items' => $cartModel->items($cart['id']), 'subtotal_cents' => $cartModel->subtotalCents($cart['id'])]);
    }

    public function addToCart(int $variantId)
    {
        $user      = $this->currentUser();
        $quantity  = max(1, (int) ($this->request->getJSON(true)['quantity'] ?? 1));
        $variant   = (new \App\Modules\Shop\Models\ProductVariantModel())->find($variantId);
        if (! $variant) {
            return $this->fail('Variant not found.', 404);
        }
        $cartModel = new CartModel();
        $cart      = $cartModel->findOrCreate($user->id, 'api-' . $user->id);
        $cartModel->addItem($cart['id'], $variantId, $quantity, (int) $variant['price_cents']);

        return $this->ok(['message' => 'Added to cart.']);
    }

    public function toggleWishlist(int $productId)
    {
        $added = (new WishlistModel())->toggle($this->currentUser()->id, $productId);

        return $this->ok(['wishlisted' => $added]);
    }
}
