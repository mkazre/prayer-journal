<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container">
    <div class="page-head"><h1>My wishlist</h1><p>Things you've saved for later.</p></div>
    <?php if ($products === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('heart', 'icon-lg') ?></div><h3>Your wishlist is empty</h3><p>Tap the heart on any product to save it here.</p><a class="btn btn-primary" href="<?= site_url('shop') ?>">Browse the shop</a></div>
    <?php else : ?>
        <div class="grid grid-4"><?php foreach ($products as $p) : ?><?= view('partials/product_card', ['product' => $p, 'wishlisted' => $wishlisted]) ?><?php endforeach ?></div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
