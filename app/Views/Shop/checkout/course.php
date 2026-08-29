<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Checkout: <?= esc($course['title']) ?></h1>
    <p>Price: $<?= number_format($course['price_cents'] / 100, 2) ?></p>
    <form method="post">
        <?= csrf_field() ?>
        <p><label><input type="radio" name="gateway" value="stripe" checked> Card (Stripe)</label>
           <label><input type="radio" name="gateway" value="payfast"> PayFast</label></p>
        <button class="pj-btn pj-btn-gold" type="submit">Pay & Enroll</button>
    </form>
</div>
<?= $this->endSection() ?>
