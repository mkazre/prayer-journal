<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Models\OrderModel;

[$statusLabel, $tone] = OrderModel::STATUSES[$order['status']] ?? [$order['status'], 'brand'];
$steps = ['created' => 'Placed', 'paid' => 'Paid', 'shipped' => 'Shipped', 'completed' => 'Delivered'];
$reached = ['created' => true, 'paid' => $paid, 'shipped' => in_array($order['status'], ['shipped', 'completed'], true), 'completed' => $order['status'] === 'completed'];
$hasPhysical = array_filter($items, static fn ($i) => ($i['product_type'] ?? 'physical') !== 'digital') !== [];
?>
<div class="container container-narrow" style="max-width:960px">
    <div class="page-head">
        <?php if (auth()->loggedIn()) : ?><nav class="breadcrumbs"><a href="<?= site_url('my-orders') ?>">My orders</a><?= icon('chevron-right') ?><span><?= esc($order['order_number']) ?></span></nav><?php endif ?>
        <div class="row between items-start">
            <div>
                <?php if ($paid) : ?><span class="eyebrow" style="color:var(--success)"><?= icon('check-circle') ?>Thank you<?= $order['customer_name'] ? ', ' . esc(strtok($order['customer_name'], ' ')) : '' ?>!</span><?php endif ?>
                <h1 class="mt-1">Order <?= esc($order['order_number']) ?></h1>
                <p>Placed <?= nice_date($order['created_at'], 'j F Y, H:i') ?> · confirmation sent to <?= esc($order['email']) ?></p>
            </div>
            <span class="badge badge-<?= $tone ?>" style="font-size:.85rem;padding:.35rem .8rem"><?= esc($statusLabel) ?></span>
        </div>
    </div>

    <?php if (in_array($order['status'], ['cancelled', 'refunded'], true)) : ?>
        <div class="alert alert-danger mb-3"><?= icon('info') ?><div>This order was <?= esc(strtolower($statusLabel)) ?>.</div></div>
    <?php elseif (! $paid) : ?>
        <div class="alert alert-warning mb-3"><?= icon('clock') ?><div><strong>Awaiting payment.</strong> If you completed payment, this page updates once the payment provider confirms it (usually within a minute).</div></div>
    <?php elseif ($hasPhysical) : ?>
        <div class="card card-body mb-3">
            <div class="grid" style="grid-template-columns:repeat(4,1fr);gap:.5rem">
                <?php foreach ($steps as $k => $l) : ?>
                    <div class="text-center">
                        <div style="height:6px;border-radius:99px;background:<?= $reached[$k] ? 'var(--success)' : 'var(--surface-3)' ?>"></div>
                        <div class="text-sm mt-1 <?= $reached[$k] ? 'fw-700' : 'text-muted' ?>"><?= $l ?></div>
                    </div>
                <?php endforeach ?>
            </div>
            <?php if (! empty($fulfillment['tracking_number'])) : ?><p class="text-sm mt-2 mb-0"><?= icon('truck') ?> <?= esc((string) $fulfillment['carrier']) ?> tracking: <strong><?= esc($fulfillment['tracking_number']) ?></strong></p><?php endif ?>
        </div>
    <?php endif ?>

    <div class="split">
        <div class="stack">
            <section class="card card-body" style="padding-top:.25rem;padding-bottom:.25rem">
                <?php foreach ($items as $item) : ?>
                    <div class="cart-line">
                        <span class="cart-thumb"><?= $item['image'] ? '<img src="' . base_url($item['image']) . '" alt="">' : icon('book', 'icon-lg') ?></span>
                        <div>
                            <?php if ($item['slug']) : ?><a class="fw-600" href="<?= site_url('shop/' . $item['slug']) ?>" style="color:inherit"><?= esc($item['title_snapshot']) ?></a><?php else : ?><strong><?= esc($item['title_snapshot']) ?></strong><?php endif ?>
                            <div class="text-sm text-muted">Qty <?= (int) $item['quantity'] ?> · <?= money((int) $item['unit_price_cents'], $order['currency']) ?> each</div>
                            <?php if (($item['product_type'] ?? '') === 'digital') : ?>
                                <?php if ($paid && $item['digital_file_path'] && auth()->loggedIn()) : ?>
                                    <a class="btn btn-sm btn-soft mt-1" href="<?= site_url('orders/' . $order['id'] . '/download/' . $item['id']) ?>"><?= icon('download') ?>Download</a>
                                <?php else : ?><span class="badge badge-info mt-1"><?= icon('download') ?>Download available after payment</span><?php endif ?>
                            <?php endif ?>
                        </div>
                        <span class="price"><?= money((int) $item['unit_price_cents'] * (int) $item['quantity'], $order['currency']) ?></span>
                    </div>
                <?php endforeach ?>
            </section>
            <?php if ($events) : ?>
                <section class="card card-body">
                    <h3 style="font-family:var(--font-body);font-size:1rem">Order history</h3>
                    <ul class="timeline">
                        <?php foreach ($events as $e) : ?><li><?= esc($e['message']) ?><time><?= nice_date($e['created_at'], 'j M Y, H:i') ?></time></li><?php endforeach ?>
                    </ul>
                </section>
            <?php endif ?>
        </div>
        <aside class="stack">
            <section class="card card-body">
                <h3 style="font-family:var(--font-body);font-size:1rem">Summary</h3>
                <div class="summary-row"><span>Subtotal</span><span><?= money((int) $order['subtotal_cents'], $order['currency']) ?></span></div>
                <?php if ((int) $order['discount_cents'] > 0) : ?><div class="summary-row text-success"><span>Discount</span><span>−<?= money((int) $order['discount_cents'], $order['currency']) ?></span></div><?php endif ?>
                <?php if ($hasPhysical) : ?><div class="summary-row"><span>Shipping<?= $order['shipping_method'] ? ' · ' . esc($order['shipping_method']) : '' ?></span><span><?= (int) $order['shipping_cents'] ? money((int) $order['shipping_cents'], $order['currency']) : 'Free' ?></span></div><?php endif ?>
                <?php if ((int) $order['tax_cents'] > 0) : ?><div class="summary-row text-muted"><span><?= esc(site_setting('tax_label', 'VAT')) ?></span><span><?= money((int) $order['tax_cents'], $order['currency']) ?></span></div><?php endif ?>
                <div class="summary-row total"><span>Total</span><span><?= money((int) $order['total_cents'], $order['currency']) ?></span></div>
            </section>
            <?php if (! empty($address['line1'])) : ?>
                <section class="card card-body text-sm">
                    <h3 style="font-family:var(--font-body);font-size:1rem"><?= icon('map') ?> Delivery</h3>
                    <div><?= esc((string) ($address['name'] ?? $order['customer_name'])) ?></div>
                    <div><?= esc($address['line1']) ?><?= ! empty($address['line2']) ? ', ' . esc($address['line2']) : '' ?></div>
                    <div><?= esc(trim(($address['city'] ?? '') . ', ' . ($address['region'] ?? ''), ', ')) ?> <?= esc((string) ($address['postal_code'] ?? '')) ?></div>
                    <div><?= esc(\App\Modules\Shop\Services\Pricing::COUNTRIES[$address['country'] ?? ''] ?? ($address['country'] ?? '')) ?></div>
                </section>
            <?php endif ?>
            <button class="btn btn-block" type="button" data-print><?= icon('printer') ?>Print receipt</button>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
