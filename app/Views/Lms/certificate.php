<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card" style="text-align:center;padding:3rem;border:4px double var(--pj-gold)">
    <p class="pj-badge pj-badge-gold">Certificate of Completion</p>
    <h1 style="margin-top:1rem">Prayer Journal</h1>
    <p style="font-size:1.2rem">This certifies that</p>
    <h2 style="color:var(--pj-purple)"><?= esc($cert['username']) ?></h2>
    <p style="font-size:1.2rem">has successfully completed</p>
    <h2><?= esc($cert['course_title']) ?></h2>
    <p>Issued <?= esc(date('F j, Y', strtotime($cert['issued_at']))) ?></p>
    <p style="color:var(--pj-gray);font-size:.8rem">Certificate Code: <?= esc($cert['certificate_code']) ?></p>
</div>
<?= $this->endSection() ?>
