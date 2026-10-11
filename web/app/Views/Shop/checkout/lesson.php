<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Unlock: <?= esc($lesson['title']) ?></h1>
    <p>Part of <?= esc($course['title']) ?></p>
    <p>Price: $<?= number_format($lesson['price_cents'] / 100, 2) ?></p>
    <form method="post">
        <?= csrf_field() ?>
        <p><label><input type="radio" name="gateway" value="stripe" checked> Card (Stripe)</label>
           <label><input type="radio" name="gateway" value="payfast"> PayFast</label></p>
        <button class="pj-btn pj-btn-gold" type="submit">Unlock Chapter</button>
    </form>
</div>
<?= $this->endSection() ?>
