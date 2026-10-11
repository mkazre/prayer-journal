<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Orders</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Models\OrderModel;

$link = static fn (?string $s) => site_url('studio/orders') . '?' . http_build_query(array_filter(['status' => $s, 'q' => $q]));
?>
<div class="studio-head">
    <div><h1>Orders</h1><p><?= number_format(array_sum($counts)) ?> orders all time.</p></div>
    <form method="get" class="input-icon"><?php if ($status) : ?><input type="hidden" name="status" value="<?= esc($status, 'attr') ?>"><?php endif ?><?= icon('search') ?><input class="input" type="search" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Order number, email or name" style="width:280px"></form>
</div>
<div class="toolbar">
    <div class="chips">
        <a class="chip <?= $status === '' ? 'is-active' : '' ?>" href="<?= $link(null) ?>">All <span class="text-muted"><?= array_sum($counts) ?></span></a>
        <?php foreach (OrderModel::STATUSES as $k => [$label]) : ?>
            <a class="chip <?= $status === $k ? 'is-active' : '' ?>" href="<?= $link($k) ?>"><?= esc($label) ?> <span class="text-muted"><?= (int) ($counts[$k] ?? 0) ?></span></a>
        <?php endforeach ?>
    </div>
</div>
<?php if ($orders === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('receipt', 'icon-lg') ?></div><h3>No orders here</h3><p>Orders appear as soon as a customer checks out.</p></div>
<?php else : ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Status</th><th class="num">Items</th><th class="num">Total</th></tr></thead>
    <tbody>
        <?php foreach ($orders as $o) : [$label, $tone] = OrderModel::STATUSES[$o['status']] ?? [$o['status'], 'brand']; ?>
            <tr>
                <td><a class="fw-600" href="<?= site_url('studio/orders/' . $o['id']) ?>"><?= esc($o['order_number']) ?></a></td>
                <td class="text-sm nowrap"><?= nice_date($o['created_at'], 'j M Y, H:i') ?></td>
                <td class="text-sm"><?= esc($o['customer_name'] ?: $o['email']) ?><?= $o['customer_name'] ? '<div class="text-xs text-muted">' . esc($o['email']) . '</div>' : '' ?><?= $o['user_id'] ? '' : ' <span class="badge">Guest</span>' ?></td>
                <td><span class="badge badge-dot badge-<?= $tone ?>"><?= esc($label) ?></span></td>
                <td class="num"><?= (int) $o['units'] ?></td>
                <td class="num fw-600"><?= money((int) $o['total_cents'], $o['currency']) ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table></div>
<?php endif ?>
<?= $this->endSection() ?>
