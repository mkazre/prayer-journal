<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>My Wishlist</h1>
<div class="pj-grid">
    <?php foreach ($items as $i) : ?>
        <a class="pj-card" href="<?= site_url('shop/' . $i['slug']) ?>"><?= esc($i['title']) ?></a>
    <?php endforeach ?>
    <?php if (empty($items)) : ?><p>Nothing saved yet.</p><?php endif ?>
</div>
<?= $this->endSection() ?>
