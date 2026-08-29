<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Pricing</h1>
<div class="pj-grid">
    <?php foreach ($plans as $p) : ?>
        <div class="pj-card" style="text-align:center">
            <span class="pj-badge <?= $p['type'] === 'lifetime' ? 'pj-badge-gold' : '' ?>"><?= esc(ucfirst($p['type'])) ?></span>
            <h3><?= esc($p['name']) ?></h3>
            <p style="font-size:2rem;font-family:var(--font-display)">$<?= number_format($p['price_cents'] / 100, 2) ?></p>
            <?php if ($p['billing_interval']) : ?><p>per <?= esc($p['billing_interval']) ?></p><?php endif ?>
            <a class="pj-btn pj-btn-gold" href="<?= site_url('checkout/plan/' . $p['id']) ?>">Choose Plan</a>
        </div>
    <?php endforeach ?>
    <?php if (empty($plans)) : ?><p>No plans configured yet.</p><?php endif ?>
</div>
<?= $this->endSection() ?>
