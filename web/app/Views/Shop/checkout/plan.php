<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Subscribe: <?= esc($plan['name']) ?></h1>
    <p><?= $plan['type'] === 'lifetime' ? 'One-time lifetime access' : 'Billed per ' . esc($plan['billing_interval']) ?></p>
    <p style="font-size:1.5rem"><strong>$<?= number_format($plan['price_cents'] / 100, 2) ?></strong></p>
    <form method="post">
        <?= csrf_field() ?>
        <p><label><input type="radio" name="gateway" value="stripe" checked> Card (Stripe)</label>
           <label><input type="radio" name="gateway" value="payfast"> PayFast</label></p>
        <button class="pj-btn pj-btn-gold" type="submit">Subscribe</button>
    </form>
</div>
<?= $this->endSection() ?>
