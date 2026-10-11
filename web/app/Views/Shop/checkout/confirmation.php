<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Order <?= esc($order['order_number']) ?></h1>
    <p>Status: <strong><?= esc($order['status']) ?></strong></p>
    <?php foreach ($items as $i) : ?>
        <p><?= esc($i['title_snapshot']) ?> x <?= esc($i['quantity']) ?> — $<?= number_format($i['unit_price_cents'] * $i['quantity'] / 100, 2) ?></p>
    <?php endforeach ?>
    <p><strong>Total: $<?= number_format($order['total_cents'] / 100, 2) ?></strong></p>
</div>
<?= $this->endSection() ?>
