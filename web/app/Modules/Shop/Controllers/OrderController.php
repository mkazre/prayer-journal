<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\OrderModel;

class OrderController extends BaseController
{
    public function confirmation(int $orderId): string
    {
        $order = (new OrderModel())->find($orderId);
        if (! $order) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('Shop/checkout/confirmation', ['title' => 'Order Confirmation', 'order' => $order, 'items' => (new OrderModel())->items($orderId)]);
    }

    public function myOrders(): string
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }

        return view('Shop/checkout/my_orders', ['title' => 'My Orders', 'orders' => (new OrderModel())->forUser(auth()->id())]);
    }
}
