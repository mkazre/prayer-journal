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
        // Website session, or a signed link handed to the mobile app.
        $userId = auth()->loggedIn() ? $this->userId() : \App\Libraries\SignedLink::verify('download:' . $orderId . ':' . $itemId, (array) $this->request->getGet());
        if (! $userId) {
            return redirect()->to(site_url('login'));
        }
        $orders = new OrderModel();
        $order  = $orders->find($orderId);
        if (! $order || (int) $order['user_id'] !== $userId || ! OrderModel::isPaid($order)) {
            $this->notFound();
        }
        foreach ($orders->items($orderId) as $item) {
            if ((int) $item['id'] === $itemId && $item['digital_file_path']) {
                $readable = (int) ($item['flipbook'] ?? 0) && strtolower(pathinfo($item['digital_file_path'], PATHINFO_EXTENSION)) === 'pdf';
                if ($readable && ! (int) ($item['allow_download'] ?? 1)) {
                    $signed = auth()->loggedIn() ? '' : '?' . \App\Libraries\SignedLink::sign('flipbook:' . $orderId . ':' . $itemId, $userId);

                    return redirect()->to(site_url('orders/' . $orderId . '/read/' . $itemId) . $signed)->with('message', 'This book is read online — downloading is switched off.');
                }
                $path = Uploads::path($item['digital_file_path']);
                if (! is_file($path)) {
                    break;
                }
                $orders->log($orderId, 'download', 'Downloaded ' . $item['title_snapshot'] . '.', $userId);

                return $this->response->download($path, null)->setFileName(url_title($item['title_snapshot'], '-', true) . '.' . pathinfo($path, PATHINFO_EXTENSION));
            }
        }

        return redirect()->to(site_url('orders/' . $orderId))->with('error', 'That download is not available. Please contact us.');
    }

    /**
     * The flipbook reader for a purchased PDF. Opened from the website
     * (signed-in session) or from the mobile app (signed link).
     */
    public function read(int $orderId, int $itemId)
    {
        $userId = $this->readerUser($orderId, $itemId);
        if (! $userId) {
            return redirect()->to(site_url('login'));
        }
        [$order, $item] = $this->flipbookItem($orderId, $itemId, $userId);
        $user = auth()->getProvider()->findById($userId);
        $sig  = auth()->loggedIn() ? '' : http_build_query(array_intersect_key($this->request->getGet(), array_flip(['u', 'e', 's'])));

        return view('Shop/orders/flipbook', [
            'title'     => $item['title_snapshot'],
            'order'     => $order,
            'item'      => $item,
            'fileUrl'   => site_url('orders/' . $orderId . '/read/' . $itemId . '/file') . ($sig !== '' ? '?' . $sig : ''),
            'watermark' => trim(($user->username ?? '') . ' · ' . ($user->email ?? '')),
            'inApp'     => $sig !== '',
        ]);
    }

    /**
     * The PDF bytes for the reader. Refuses a direct visit (typing the URL
     * into the address bar) so the file is only ever fetched by the reader.
     */
    public function readerFile(int $orderId, int $itemId)
    {
        $userId = $this->readerUser($orderId, $itemId);
        if (! $userId) {
            $this->notFound();
        }
        [, $item] = $this->flipbookItem($orderId, $itemId, $userId);
        $dest = $this->request->getHeaderLine('Sec-Fetch-Dest');
        if ($dest !== '' && $dest !== 'empty') {
            return redirect()->to(site_url('orders/' . $orderId . '/read/' . $itemId));
        }
        $path = Uploads::path($item['digital_file_path']);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setBody((string) file_get_contents($path));
    }

    /**
     * Signed-in user, or the user a valid signed link was issued for.
     */
    private function readerUser(int $orderId, int $itemId): ?int
    {
        if (auth()->loggedIn()) {
            return $this->userId();
        }

        return \App\Libraries\SignedLink::verify('flipbook:' . $orderId . ':' . $itemId, (array) $this->request->getGet());
    }

    /**
     * @return array{0: array, 1: array} the paid order and its flipbook item
     */
    private function flipbookItem(int $orderId, int $itemId, int $userId): array
    {
        $orders = new OrderModel();
        $order  = $orders->find($orderId);
        if (! $order || (int) $order['user_id'] !== $userId || ! OrderModel::isPaid($order)) {
            $this->notFound();
        }
        foreach ($orders->items($orderId) as $item) {
            if ((int) $item['id'] === $itemId && (int) ($item['flipbook'] ?? 0) && $item['digital_file_path']
                && strtolower(pathinfo($item['digital_file_path'], PATHINFO_EXTENSION)) === 'pdf' && is_file(Uploads::path($item['digital_file_path']))) {
                return [$order, $item];
            }
        }
        $this->notFound();
    }

    private function canView(array $order): bool
    {
        if (auth()->loggedIn() && ((int) $order['user_id'] === $this->userId() || is_staff('superadmin', 'admin', 'manager'))) {
            return true;
        }

        return in_array((int) $order['id'], array_map('intval', (array) session('guest_orders')), true);
    }
}
