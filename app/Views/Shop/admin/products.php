<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Products</h1>
    <a class="pj-btn pj-btn-gold" href="<?= site_url('shop-admin/products/create') ?>">+ New Product</a>
    <table style="width:100%;margin-top:1rem">
        <?php foreach ($products as $p) : ?>
            <tr>
                <td><?= esc($p['title']) ?></td>
                <td><?= esc($p['status']) ?></td>
                <td><?php if ($p['status'] !== 'published') : ?>
                    <form method="post" action="<?= site_url('shop-admin/products/' . $p['id'] . '/publish') ?>">
                        <?= csrf_field() ?>
                        <button class="pj-btn" type="submit">Publish</button>
                    </form>
                <?php endif ?></td>
            </tr>
        <?php endforeach ?>
    </table>
</div>
<?= $this->endSection() ?>
