<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Subscription Plans</h1>
    <table style="width:100%">
        <tr><th>Name</th><th>Type</th><th>Price</th><th>Active</th></tr>
        <?php foreach ($plans as $p) : ?>
            <tr><td><?= esc($p['name']) ?></td><td><?= esc($p['type']) ?> <?= esc($p['billing_interval']) ?></td><td>$<?= number_format($p['price_cents'] / 100, 2) ?></td><td><?= $p['is_active'] ? 'Yes' : 'No' ?></td></tr>
        <?php endforeach ?>
    </table>
    <h2>New Plan</h2>
    <form method="post" action="<?= site_url('shop-admin/plans/create') ?>">
        <?= csrf_field() ?>
        <p><label>Name<br><input type="text" name="name" required></label></p>
        <p><label>Type<br>
            <select name="type">
                <option value="subscription">Subscription</option>
                <option value="lifetime">Lifetime Deal</option>
            </select>
        </label></p>
        <p><label>Billing Interval (subscription only)<br>
            <select name="billing_interval"><option value="month">Monthly</option><option value="year">Yearly</option></select>
        </label></p>
        <p><label>Price (USD)<br><input type="number" step="0.01" name="price" required></label></p>
        <button class="pj-btn" type="submit">Create</button>
    </form>
</div>
<?= $this->endSection() ?>
