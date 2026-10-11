<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\ProductModel;
use App\Modules\Shop\Models\WishlistModel;

class WishlistController extends BaseController
{
    public function index(): string
    {
        $ids = array_map('intval', array_column((new WishlistModel())->where('user_id', $this->userId())->findAll(), 'product_id'));

        return view('Shop/wishlist', [
            'title'      => 'My wishlist',
            'products'   => $ids ? (new ProductModel())->storefront(['ids' => $ids]) : [],
            'wishlisted' => $ids,
        ]);
    }

    public function toggle(int $productId)
    {
        if (! (new ProductModel())->find($productId)) {
            $this->notFound();
        }
        $added = (new WishlistModel())->toggle($this->userId(), $productId);

        return $this->done($added ? 'Saved to your wishlist.' : 'Removed from your wishlist.', ['wishlisted' => $added]);
    }
}
