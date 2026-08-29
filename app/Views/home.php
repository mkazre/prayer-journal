<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Welcome to Prayer Journal</h1>
    <p>Study lessons, grow in the Word, and shop the store &mdash; all in one place.</p>
    <?php if (! auth()->loggedIn()) : ?>
        <a class="pj-btn pj-btn-gold" href="<?= site_url('register') ?>">Get Started</a>
    <?php else : ?>
        <a class="pj-btn" href="<?= site_url('dashboard') ?>">Go to your Dashboard</a>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
