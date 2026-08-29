<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\ProductModel;
use App\Modules\Shop\Models\WishlistModel;

class StorefrontController extends BaseController
{
    public function index(): string
    {
        $productModel = (new ProductModel())->published();
        $category     = $this->request->getGet('category');
        $search       = $this->request->getGet('q');
        if ($category) {
            $productModel->where('category_id', $category);
        }
        if ($search) {
            $productModel->like('title', $search);
        }

        return view('Shop/storefront/index', ['title' => 'Shop', 'products' => $productModel->orderBy('created_at', 'desc')->findAll()]);
    }

    public function show(string $slug)
    {
        $product = (new ProductModel())->where('slug', $slug)->first();
        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $product = (new ProductModel())->withVariantsAndImages($product['id']);

        $related = (new ProductModel())->published()
            ->where('category_id', $product['category_id'])
            ->where('id !=', $product['id'])
            ->limit(4)->findAll();

        $wishlisted = auth()->loggedIn() && (new WishlistModel())->has(auth()->id(), $product['id']);

        return view('Shop/storefront/show', ['title' => $product['title'], 'product' => $product, 'related' => $related, 'wishlisted' => $wishlisted]);
    }
}
