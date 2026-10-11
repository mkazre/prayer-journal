<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Models\ProductModel;
use App\Modules\Shop\Models\ProductVariantModel;
use App\Modules\Shop\Services\Pricing;

class CartController extends BaseController
{
    public function show(): string
    {
        $carts = new CartModel();
        $cart  = $carts->current();
        $items = $carts->items((int) $cart['id']);

        return view('Shop/cart/show', [
            'title'     => 'Your cart',
            'items'     => $items,
            'quote'     => Pricing::quote($items, null, session('cart_discount')),
            'suggested' => (new ProductModel())->storefront(['limit' => 4, 'sort' => 'best']),
        ]);
    }

    public function add(int $variantId)
    {
        $variant = (new ProductVariantModel())->find($variantId);
        $product = $variant ? (new ProductModel())->find($variant['product_id']) : null;
        if (! $variant || ! $product || $product['status'] !== 'published' || ! (int) $variant['is_active']) {
            return $this->failed('This item is not available.', 404);
        }
        $quantity = max(1, min(99, (int) $this->request->getPost('quantity')));
        $stock    = (new ProductVariantModel())->stockOnHand($variantId);
        $carts    = new CartModel();
        $cart     = $carts->current();
        $inCart   = (int) (db_connect()->table('cart_items')->where(['cart_id' => $cart['id'], 'variant_id' => $variantId])->get()->getRow('quantity') ?? 0);
        $tracked  = (int) $product['track_inventory'] && ! (int) $product['allow_backorder'] && $product['product_type'] !== 'digital';
        if ($tracked && $inCart + $quantity > $stock) {
            return $this->failed($stock - $inCart > 0 ? 'Only ' . ($stock - $inCart) . ' more available.' : 'Sorry, this is out of stock.');
        }
        if ($product['product_type'] === 'digital' && $inCart > 0) {
            return $this->failed('This download is already in your cart.');
        }
        $carts->addItem((int) $cart['id'], $variantId, $product['product_type'] === 'digital' ? 1 : $quantity, (int) $variant['price_cents']);

        if ($this->request->getPost('buy_now')) {
            return redirect()->to(site_url('checkout'));
        }

        return $this->done('Added to your cart.', ['count' => $carts->currentCount()], site_url('cart'));
    }

    public function update(int $itemId)
    {
        $carts = new CartModel();
        $cart  = $carts->current();
        $item  = db_connect()->table('cart_items ci')->select('ci.*, p.track_inventory, p.allow_backorder, p.product_type')
            ->join('product_variants v', 'v.id = ci.variant_id')->join('products p', 'p.id = v.product_id')
            ->where(['ci.id' => $itemId, 'ci.cart_id' => $cart['id']])->get()->getRowArray();
        if (! $item) {
            $this->notFound();
        }
        $qty = (int) $this->request->getPost('quantity');
        if ($qty > 0 && (int) $item['track_inventory'] && ! (int) $item['allow_backorder'] && $item['product_type'] !== 'digital') {
            $qty = min($qty, (new ProductVariantModel())->stockOnHand((int) $item['variant_id']));
        }
        if ($item['product_type'] === 'digital') {
            $qty = min($qty, 1);
        }
        $carts->updateItemQuantity((int) $cart['id'], $itemId, $qty);

        return $this->done($qty > 0 ? 'Cart updated.' : 'Item removed.', [], site_url('cart'));
    }

    public function remove(int $itemId)
    {
        $carts = new CartModel();
        $carts->updateItemQuantity((int) $carts->current()['id'], $itemId, 0);

        return $this->done('Item removed.', [], site_url('cart'));
    }

    public function discount()
    {
        $code = strtoupper(trim((string) $this->request->getPost('code')));
        if ($code === '' || $this->request->getPost('remove')) {
            session()->remove('cart_discount');

            return $this->done('Discount removed.', [], site_url('cart'));
        }
        $carts = new CartModel();
        $quote = Pricing::quote($carts->items((int) $carts->current()['id']), null, $code);
        if ($quote['discount_error']) {
            return $this->failed($quote['discount_error'], 422, site_url('cart'));
        }
        session()->set('cart_discount', $code);

        return $this->done('Code ' . $code . ' applied.', [], site_url('cart'));
    }
}
