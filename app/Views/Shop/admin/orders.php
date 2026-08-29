<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Orders</h1>
    <table style="width:100%">
        <tr><th>Order #</th><th>Status</th><th>Total</th><th>Fulfill</th></tr>
        <?php foreach ($orders as $o) : ?>
            <tr>
                <td><?= esc($o['order_number']) ?></td>
                <td><?= esc($o['status']) ?></td>
                <td>$<?= number_format($o['total_cents'] / 100, 2) ?></td>
                <td>
                    <form method="post" action="<?= site_url('shop-admin/orders/' . $o['id'] . '/fulfill') ?>">
                        <?= csrf_field() ?>
                        <input type="text" name="tracking_number" placeholder="Tracking #">
                        <input type="text" name="carrier" placeholder="Carrier">
                        <button class="pj-btn" type="submit">Mark Shipped</button>
                    </form>
                </td>
            </tr>
        <?php endforeach ?>
    </table>
</div>
<?= $this->endSection() ?>
