<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Models\ProductVariantModel;

class CartController extends BaseController
{
    public function show(): string
    {
        $cartModel = new CartModel();
        $cart      = $cartModel->findOrCreate(auth()->id(), session()->session_id ?? session_id());
        $items     = $cartModel->items($cart['id']);

        return view('Shop/cart/show', ['title' => 'Your Cart', 'cart' => $cart, 'items' => $items, 'subtotal' => $cartModel->subtotalCents($cart['id'])]);
    }

    public function add(int $variantId)
    {
        $variant = (new ProductVariantModel())->find($variantId);
        if (! $variant) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $quantity  = max(1, (int) $this->request->getPost('quantity'));
        $cartModel = new CartModel();
        $cart      = $cartModel->findOrCreate(auth()->id(), session()->session_id ?? session_id());
        $cartModel->addItem($cart['id'], $variantId, $quantity, (int) $variant['price_cents']);

        return redirect()->to(site_url('cart'))->with('message', 'Added to cart.');
    }

    public function update(int $itemId)
    {
        $cartModel = new CartModel();
        $cart      = $cartModel->findOrCreate(auth()->id(), session()->session_id ?? session_id());
        $cartModel->updateItemQuantity($cart['id'], $itemId, (int) $this->request->getPost('quantity'));

        return redirect()->to(site_url('cart'));
    }
}
