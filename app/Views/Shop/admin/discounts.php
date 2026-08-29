<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Discounts</h1>
    <table style="width:100%">
        <tr><th>Code</th><th>Type</th><th>Value</th><th>Redeemed</th></tr>
        <?php foreach ($discounts as $d) : ?>
            <tr><td><?= esc($d['code']) ?></td><td><?= esc($d['type']) ?></td><td><?= esc($d['value']) ?></td><td><?= esc($d['times_redeemed']) ?></td></tr>
        <?php endforeach ?>
    </table>
    <h2>New Discount</h2>
    <form method="post" action="<?= site_url('shop-admin/discounts/create') ?>">
        <?= csrf_field() ?>
        <input type="text" name="code" placeholder="CODE" required>
        <select name="type"><option value="percent">Percent</option><option value="fixed">Fixed (cents)</option></select>
        <input type="number" name="value" placeholder="Value" required>
        <input type="date" name="expires_at">
        <button class="pj-btn" type="submit">Create</button>
    </form>
</div>
<?= $this->endSection() ?>
