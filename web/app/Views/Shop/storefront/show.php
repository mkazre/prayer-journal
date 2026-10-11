<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($product['title']) ?></h1>
    <p><?= esc($product['description']) ?></p>
    <?php foreach ($product['variants'] as $v) : ?>
        <form method="post" action="<?= site_url('cart/add/' . $v['id']) ?>" style="margin-bottom:.5rem">
            <?= csrf_field() ?>
            <span><?= esc($v['option_name'] ?: 'Default') ?> — $<?= number_format($v['price_cents'] / 100, 2) ?></span>
            <input type="number" name="quantity" value="1" min="1" style="width:60px">
            <button class="pj-btn" type="submit">Add to Cart</button>
        </form>
    <?php endforeach ?>

    <?php if (auth()->loggedIn()) : ?>
        <form method="post" action="<?= site_url('wishlist/toggle/' . $product['id']) ?>">
            <?= csrf_field() ?>
            <button class="pj-btn pj-btn-outline" style="color:var(--pj-purple);border-color:var(--pj-purple)" type="submit"><?= $wishlisted ? '💜 Saved' : '🤍 Save for later' ?></button>
        </form>
    <?php endif ?>
</div>

<?php if (! empty($related)) : ?>
    <h2 style="margin-top:2rem">You might also like</h2>
    <div class="pj-grid">
        <?php foreach ($related as $r) : ?>
            <a class="pj-card" href="<?= site_url('shop/' . $r['slug']) ?>"><?= esc($r['title']) ?></a>
        <?php endforeach ?>
    </div>
<?php endif ?>
<?= $this->endSection() ?>
