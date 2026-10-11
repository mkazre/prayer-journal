<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/orders') ?>">Orders</a><?= icon('chevron-right') ?><span><?= esc($order['order_number']) ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Models\OrderModel;
use App\Modules\Shop\Services\Pricing;

[$label, $tone] = OrderModel::STATUSES[$order['status']] ?? [$order['status'], 'brand'];
$cur         = $order['currency'];
$paid        = OrderModel::isPaid($order);
$hasPhysical = array_filter($items, static fn ($i) => ($i['product_type'] ?? 'physical') !== 'digital') !== [];
?>
<div class="studio-head">
    <div>
        <h1 class="row gap-sm"><?= esc($order['order_number']) ?> <span class="badge badge-<?= $tone ?>"><?= esc($label) ?></span></h1>
        <p>Placed <?= nice_date($order['created_at'], 'j F Y, H:i') ?><?= $order['paid_at'] ? ' · paid ' . nice_date($order['paid_at'], 'j M, H:i') : '' ?></p>
    </div>
    <div class="row">
        <a class="btn" href="<?= site_url('orders/' . $order['id']) ?>" target="_blank" rel="noopener"><?= icon('external') ?>Customer view</a>
        <button class="btn" type="button" data-print><?= icon('printer') ?>Print</button>
        <form method="post" action="<?= site_url('studio/orders/' . $order['id'] . '/status') ?>" class="row gap-xs">
            <?= csrf_field() ?>
            <select class="select" name="status" style="width:auto" aria-label="Status">
                <?php foreach (OrderModel::STATUSES as $k => [$l]) : ?><option value="<?= $k ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= esc($l) ?></option><?php endforeach ?>
            </select>
            <button class="btn btn-primary" type="submit" data-confirm="Change the order status? Cancelling or refunding returns reserved stock.">Update</button>
        </form>
    </div>
</div>

<div class="split">
    <div class="stack">
        <section class="card">
            <div class="card-header"><h2><?= icon('package') ?> Items</h2></div>
            <div class="card-body" style="padding-top:.25rem;padding-bottom:.25rem">
                <?php foreach ($items as $item) : ?>
                    <div class="cart-line">
                        <span class="cart-thumb"><?= $item['image'] ? '<img src="' . base_url($item['image']) . '" alt="">' : icon('book', 'icon-lg') ?></span>
                        <div>
                            <?php if ($item['product_id']) : ?><a class="fw-600" href="<?= site_url('studio/products/' . $item['product_id']) ?>"><?= esc($item['title_snapshot']) ?></a><?php else : ?><strong><?= esc($item['title_snapshot']) ?></strong><?php endif ?>
                            <div class="text-sm text-muted"><?= $item['sku'] ? 'SKU ' . esc($item['sku']) . ' · ' : '' ?><?= (int) $item['quantity'] ?> × <?= money((int) $item['unit_price_cents'], $cur) ?><?= ($item['product_type'] ?? '') === 'digital' ? ' · ' . icon('download') . ' Digital' : '' ?></div>
                        </div>
                        <span class="price"><?= money((int) $item['unit_price_cents'] * (int) $item['quantity'], $cur) ?></span>
                    </div>
                <?php endforeach ?>
            </div>
            <div class="card-body" style="border-top:1px solid var(--border)">
                <div class="summary-row"><span>Subtotal</span><span><?= money((int) $order['subtotal_cents'], $cur) ?></span></div>
                <?php if ((int) $order['discount_cents'] > 0) : ?><div class="summary-row text-success"><span>Discount<?= ! empty($order['discount_code']) ? ' · ' . esc($order['discount_code']) : '' ?></span><span>−<?= money((int) $order['discount_cents'], $cur) ?></span></div><?php endif ?>
                <div class="summary-row"><span>Shipping<?= $order['shipping_method'] ? ' · ' . esc($order['shipping_method']) : '' ?></span><span><?= (int) $order['shipping_cents'] ? money((int) $order['shipping_cents'], $cur) : 'Free' ?></span></div>
                <?php if ((int) $order['tax_cents'] > 0) : ?><div class="summary-row text-muted"><span><?= esc(site_setting('tax_label', 'VAT')) ?></span><span><?= money((int) $order['tax_cents'], $cur) ?></span></div><?php endif ?>
                <div class="summary-row total"><span>Total</span><span><?= money((int) $order['total_cents'], $cur) ?></span></div>
            </div>
        </section>

        <?php if ($hasPhysical && $paid) : ?>
        <section class="card">
            <div class="card-header"><h2><?= icon('truck') ?> Fulfilment</h2><?php if (! empty($fulfillment['shipped_at'])) : ?><span class="badge badge-brand">Shipped <?= nice_date($fulfillment['shipped_at'], 'j M') ?></span><?php endif ?></div>
            <form class="card-body grid grid-3" method="post" action="<?= site_url('studio/orders/' . $order['id'] . '/fulfill') ?>">
                <?= csrf_field() ?>
                <div class="field"><label class="label" for="carrier">Carrier</label><input class="input" id="carrier" name="carrier" list="carriers" value="<?= esc((string) ($fulfillment['carrier'] ?? ''), 'attr') ?>" placeholder="e.g. The Courier Guy"><datalist id="carriers"><option>The Courier Guy</option><option>PostNet</option><option>Pargo</option><option>Aramex</option><option>DHL Express</option><option>SA Post Office</option></datalist></div>
                <div class="field"><label class="label" for="tracking">Tracking number</label><input class="input" id="tracking" name="tracking_number" value="<?= esc((string) ($fulfillment['tracking_number'] ?? ''), 'attr') ?>"></div>
                <div class="field" style="align-self:end"><button class="btn btn-primary btn-block" type="submit"><?= icon('send') ?><?= ! empty($fulfillment['shipped_at']) ? 'Update shipment' : 'Mark as shipped' ?></button></div>
            </form>
        </section>
        <?php endif ?>

        <section class="card">
            <div class="card-header"><h2><?= icon('history') ?> Timeline</h2></div>
            <div class="card-body">
                <form method="post" action="<?= site_url('studio/orders/' . $order['id'] . '/note') ?>" class="row row-nowrap gap-sm mb-2">
                    <?= csrf_field() ?>
                    <input class="input" name="note" placeholder="Add a private note for your team" required>
                    <button class="btn" type="submit">Post</button>
                </form>
                <?php if ($events === []) : ?><p class="text-sm text-muted mb-0">No activity yet.</p><?php else : ?>
                <ul class="timeline">
                    <?php foreach ($events as $e) : ?><li><?= esc($e['message']) ?><time><?= nice_date($e['created_at'], 'j M Y, H:i') ?><?= $e['type'] === 'note' ? ' · internal' : '' ?></time></li><?php endforeach ?>
                </ul>
                <?php endif ?>
            </div>
        </section>
    </div>

    <aside class="stack">
        <section class="card card-body text-sm">
            <h3 class="h-sm"><?= icon('user') ?> Customer</h3>
            <div class="fw-600"><?= esc($order['customer_name'] ?: 'Guest customer') ?></div>
            <div><a href="mailto:<?= esc($order['email'], 'attr') ?>"><?= esc($order['email']) ?></a></div>
            <?php if ($order['phone']) : ?><div><?= esc($order['phone']) ?></div><?php endif ?>
            <div class="text-muted mt-1"><?= (int) $customerOrders ?> order<?= (int) $customerOrders === 1 ? '' : 's' ?> in total<?= $order['user_id'] ? '' : ' · checked out as guest' ?></div>
            <?php if ($order['customer_note']) : ?><div class="alert alert-info mt-2"><?= icon('message') ?><div><strong>Customer note</strong><br><?= esc($order['customer_note']) ?></div></div><?php endif ?>
        </section>
        <?php if (! empty($address['line1'])) : ?>
            <section class="card card-body text-sm">
                <h3 class="h-sm"><?= icon('map') ?> Shipping address</h3>
                <div><?= esc((string) ($address['name'] ?? $order['customer_name'])) ?></div>
                <div><?= esc($address['line1']) ?><?= ! empty($address['line2']) ? ', ' . esc($address['line2']) : '' ?></div>
                <div><?= esc(trim(($address['city'] ?? '') . ', ' . ($address['region'] ?? ''), ', ')) ?> <?= esc((string) ($address['postal_code'] ?? '')) ?></div>
                <div><?= esc(Pricing::COUNTRIES[$address['country'] ?? ''] ?? ($address['country'] ?? '')) ?></div>
                <button class="btn btn-sm btn-ghost mt-1" type="button" data-copy="<?= esc(implode("\n", array_filter([$address['name'] ?? $order['customer_name'], $address['line1'] ?? '', $address['line2'] ?? '', trim(($address['city'] ?? '') . ' ' . ($address['postal_code'] ?? '')), $address['region'] ?? '', Pricing::COUNTRIES[$address['country'] ?? ''] ?? ''])), 'attr') ?>"><?= icon('copy') ?>Copy address</button>
            </section>
        <?php endif ?>
        <section class="card card-body text-sm">
            <h3 class="h-sm"><?= icon('credit-card') ?> Payments</h3>
            <?php if ($payments === []) : ?><p class="text-muted mb-0">No payment attempts recorded.</p><?php endif ?>
            <?php foreach ($payments as $p) : ?>
                <div class="row between mb-1"><span><span class="fw-600"><?= esc(ucfirst($p['gateway'])) ?></span><br><span class="text-xs text-muted"><?= esc((string) $p['gateway_ref']) ?> · <?= time_ago($p['created_at']) ?></span></span><span class="text-right"><?= money((int) $p['amount_cents'], $p['currency']) ?><br><span class="badge <?= $p['status'] === 'paid' || $p['status'] === 'complete' ? 'badge-success' : 'badge-warning' ?>"><?= esc($p['status']) ?></span></span></div>
            <?php endforeach ?>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
