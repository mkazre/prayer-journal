<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php use App\Modules\Shop\Models\OrderModel; ?>
<div class="container container-narrow" style="max-width:960px">
    <div class="page-head"><h1>My orders</h1><p>Track deliveries, download digital items and print receipts.</p></div>
    <?php if ($orders === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('receipt', 'icon-lg') ?></div><h3>No orders yet</h3><p>When you buy something, it will appear here.</p><a class="btn btn-primary" href="<?= site_url('shop') ?>">Visit the shop</a></div>
    <?php else : ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Status</th><th class="num">Total</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($orders as $o) : [$l, $tone] = OrderModel::STATUSES[$o['status']] ?? [$o['status'], 'brand']; ?>
                    <tr><td class="fw-600"><?= esc($o['order_number']) ?></td><td><?= nice_date($o['created_at']) ?></td><td><?= (int) $o['item_count'] ?></td><td><span class="badge badge-<?= $tone ?>"><?= esc($l) ?></span></td><td class="num"><?= money((int) $o['total_cents'], $o['currency']) ?></td><td class="text-right"><a class="btn btn-sm" href="<?= site_url('orders/' . $o['id']) ?>">View</a></td></tr>
                <?php endforeach ?>
            </tbody>
        </table></div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
