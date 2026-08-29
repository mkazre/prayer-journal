<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Inventory</h1>
    <table style="width:100%">
        <tr><th>Product</th><th>SKU</th><th>Location</th><th>Qty</th></tr>
        <?php foreach ($levels as $l) : ?>
            <tr>
                <td><?= esc($l['title']) ?></td><td><?= esc($l['sku']) ?></td><td><?= esc($l['location_name']) ?></td>
                <td><?= esc($l['quantity']) ?> <?php if ($l['quantity'] < 5) : ?><span class="pj-badge" style="background:#fdecec;color:#8a1f1f">Low stock</span><?php endif ?></td>
            </tr>
        <?php endforeach ?>
    </table>
</div>
<?= $this->endSection() ?>
