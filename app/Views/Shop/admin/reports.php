<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Sales Reports</h1>
    <h2>Sales by Day</h2>
    <table style="width:100%">
        <tr><th>Day</th><th>Orders</th><th>Total</th></tr>
        <?php foreach ($salesByDay as $r) : ?>
            <tr><td><?= esc($r['day']) ?></td><td><?= esc($r['order_count']) ?></td><td>$<?= number_format($r['total_cents'] / 100, 2) ?></td></tr>
        <?php endforeach ?>
    </table>
    <h2>Top Products</h2>
    <table style="width:100%">
        <tr><th>Product</th><th>Units Sold</th></tr>
        <?php foreach ($topProducts as $r) : ?>
            <tr><td><?= esc($r['title_snapshot']) ?></td><td><?= esc($r['units_sold']) ?></td></tr>
        <?php endforeach ?>
    </table>
</div>
<?= $this->endSection() ?>
