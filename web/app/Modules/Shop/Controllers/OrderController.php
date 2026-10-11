<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Uploads;
use App\Modules\Shop\Models\OrderModel;

class OrderController extends BaseController
{
    /**
     * Order status page. Visible to the customer who placed it (account or
     * this browser session for guests) and to administrators.
     */
    public function show(int $orderId)
    {
        $orders = new OrderModel();
        $order  = $orders->find($orderId);
        if (! $order || ! $this->canView($order)) {
            $this->notFound();
        }

        return view('Shop/orders/show', [
            'title'       => 'Order ' . $order['order_number'],
            'order'       => $order,
            'items'       => $orders->items($orderId),
            'events'      => $orders->events($orderId),
            'fulfillment' => $orders->fulfillment($orderId),
            'address'     => OrderModel::address($order),
            'paid'        => OrderModel::isPaid($order),
        ]);
    }

    public function myOrders(): string
    {
        return view('Shop/orders/index', ['title' => 'My orders', 'orders' => (new OrderModel())->forUser($this->userId())]);
    }

    public function download(int $orderId, int $itemId)
    {
        $orders = new OrderModel();
        $order  = $orders->find($orderId);
        if (! $order || (int) $order['user_id'] !== $this->userId() || ! OrderModel::isPaid($order)) {
            $this->notFound();
        }
        foreach ($orders->items($orderId) as $item) {
            if ((int) $item['id'] === $itemId && $item['digital_file_path']) {
                $path = Uploads::path($item['digital_file_path']);
                if (! is_file($path)) {
                    break;
                }
                $orders->log($orderId, 'download', 'Downloaded ' . $item['title_snapshot'] . '.', $this->userId());

                return $this->response->download($path, null)->setFileName(url_title($item['title_snapshot'], '-', true) . '.' . pathinfo($path, PATHINFO_EXTENSION));
            }
        }

        return redirect()->to(site_url('orders/' . $orderId))->with('error', 'That download is not available. Please contact us.');
    }

    private function canView(array $order): bool
    {
        if (auth()->loggedIn() && ((int) $order['user_id'] === $this->userId() || is_staff('superadmin', 'admin', 'manager'))) {
            return true;
        }

        return in_array((int) $order['id'], array_map('intval', (array) session('guest_orders')), true);
    }
}
