<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Shop</h1>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem">
    <?php foreach ($products as $p) : ?>
        <a class="pj-card" style="text-decoration:none;color:inherit" href="<?= site_url('shop/' . $p['slug']) ?>">
            <h3><?= esc($p['title']) ?></h3>
        </a>
    <?php endforeach ?>
    <?php if (empty($products)) : ?><p>No products yet.</p><?php endif ?>
</div>
<?= $this->endSection() ?>
