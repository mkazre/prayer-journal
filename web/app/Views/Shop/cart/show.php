<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Your Cart</h1>
    <?php foreach ($items as $i) : ?>
        <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:.5rem 0">
            <span><?= esc($i['title']) ?> <?= esc($i['option_name']) ?> x <?= esc($i['quantity']) ?></span>
            <span>$<?= number_format($i['unit_price_cents'] * $i['quantity'] / 100, 2) ?></span>
        </div>
    <?php endforeach ?>
    <p><strong>Subtotal: $<?= number_format($subtotal / 100, 2) ?></strong></p>
    <?php if (! empty($items)) : ?>
        <a class="pj-btn pj-btn-gold" href="<?= site_url('checkout/cart') ?>">Checkout</a>
    <?php else : ?>
        <p>Your cart is empty. <a href="<?= site_url('shop') ?>">Browse the shop</a>.</p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
