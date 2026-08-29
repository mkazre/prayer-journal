<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\WishlistModel;

class WishlistController extends BaseController
{
    public function index(): string
    {
        return view('Shop/wishlist', ['title' => 'My Wishlist', 'items' => (new WishlistModel())->forUser(auth()->id())]);
    }

    public function toggle(int $productId)
    {
        (new WishlistModel())->toggle(auth()->id(), $productId);

        return redirect()->back();
    }
}
