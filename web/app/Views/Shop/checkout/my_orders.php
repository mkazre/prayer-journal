<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>My Orders</h1>
<?php foreach ($orders as $o) : ?>
    <div class="pj-card" style="margin-top:.5rem">
        <a href="<?= site_url('orders/' . $o['id'] . '/confirmation') ?>"><?= esc($o['order_number']) ?></a>
        — <?= esc($o['status']) ?> — $<?= number_format($o['total_cents'] / 100, 2) ?>
    </div>
<?php endforeach ?>
<?= $this->endSection() ?>
