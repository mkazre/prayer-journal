<?php

namespace App\Modules\Studio\Controllers;

use App\Modules\Shop\Models\OrderModel;

class OrderController extends StudioController
{
    public function index(): string
    {
        $this->requireManager();
        $status = (string) $this->request->getGet('status');
        $q      = trim((string) $this->request->getGet('q'));
        $b      = db_connect()->table('orders o')->select('o.*, (SELECT COALESCE(SUM(quantity),0) FROM order_items oi WHERE oi.order_id = o.id) AS units', false);
        if (isset(OrderModel::STATUSES[$status])) {
            $b->where('o.status', $status);
        }
        if ($q !== '') {
            $b->groupStart()->like('o.order_number', $q)->orLike('o.email', $q)->orLike('o.customer_name', $q)->groupEnd();
        }
        $counts = [];
        foreach (db_connect()->table('orders')->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }

        return $this->render('orders/index', ['title' => 'Orders', 'active' => 'orders', 'orders' => $b->orderBy('o.created_at', 'DESC')->limit(200)->get()->getResultArray(), 'status' => $status, 'q' => $q, 'counts' => $counts]);
    }

    public function show(int $id): string
    {
        $this->requireManager();
        $orders = new OrderModel();
        $order  = $orders->find($id) ?? $this->notFound();
        $payments = db_connect()->table('payments')->where('order_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();

        return $this->render('orders/show', [
            'title' => 'Order ' . $order['order_number'], 'active' => 'orders', 'order' => $order, 'items' => $orders->items($id),
            'events' => $orders->events($id), 'fulfillment' => $orders->fulfillment($id), 'address' => OrderModel::address($order), 'payments' => $payments,
            'customerOrders' => $order['user_id'] ? db_connect()->table('orders')->where('user_id', $order['user_id'])->countAllResults() : db_connect()->table('orders')->where('email', $order['email'])->countAllResults(),
        ]);
    }

    public function status(int $id)
    {
        $this->requireManager();
        $status = (string) $this->post('status');
        if (! isset(OrderModel::STATUSES[$status])) {
            return $this->failed('Unknown status.');
        }
        (new OrderModel())->setStatus($id, $status, $this->userId());

        return $this->done('Order marked ' . strtolower(OrderModel::STATUSES[$status][0]) . '.', [], site_url('studio/orders/' . $id));
    }

    public function fulfill(int $id)
    {
        $this->requireManager();
        $orders  = new OrderModel();
        $order   = $orders->find($id) ?? $this->notFound();
        $carrier = trim((string) $this->post('carrier'));
        $track   = trim((string) $this->post('tracking_number'));
        $db      = db_connect();
        $data    = ['status' => 'shipped', 'carrier' => $carrier ?: null, 'tracking_number' => $track ?: null, 'shipped_at' => date('Y-m-d H:i:s')];
        if ($db->table('order_fulfillments')->where('order_id', $id)->countAllResults()) {
            $db->table('order_fulfillments')->where('order_id', $id)->update($data);
        } else {
            $db->table('order_fulfillments')->insert($data + ['order_id' => $id]);
        }
        $orders->setStatus($id, 'shipped', $this->userId());
        $orders->log($id, 'fulfilment', 'Shipped' . ($carrier ? ' with ' . $carrier : '') . ($track ? ' · tracking ' . $track : '') . '.', $this->userId());
        \App\Libraries\Notify::orderShipped($id, $carrier ?: null, $track ?: null);

        return $this->done('Marked as shipped.', [], site_url('studio/orders/' . $id));
    }

    public function note(int $id)
    {
        $this->requireManager();
        $orders = new OrderModel();
        $orders->find($id) ?? $this->notFound();
        $note = trim((string) $this->post('note'));
        if ($note === '') {
            return $this->failed('Write a note first.');
        }
        $orders->log($id, 'note', 'Note: ' . $note, $this->userId());

        return $this->done('Note added.', [], site_url('studio/orders/' . $id));
    }
}
