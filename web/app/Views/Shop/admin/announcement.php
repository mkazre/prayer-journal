<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Site Announcement Banner</h1>
    <form method="post">
        <?= csrf_field() ?>
        <textarea name="announcement" rows="3"><?= esc($current) ?></textarea>
        <button class="pj-btn" type="submit">Save</button>
    </form>
</div>
<?= $this->endSection() ?>
