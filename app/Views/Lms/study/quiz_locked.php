<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($quiz['title']) ?></h1>
    <p>You've used all your attempts for this quiz.</p>
    <a class="pj-btn" href="<?= site_url('study/' . $course['slug']) ?>">Back to course</a>
</div>
<?= $this->endSection() ?>
