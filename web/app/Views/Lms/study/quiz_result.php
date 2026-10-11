<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($quiz['title']) ?> — Result</h1>
    <p style="font-size:1.5rem"><strong><?= esc($result['score_percent']) ?>%</strong></p>
    <p><?= $result['passed'] ? 'You passed! 🎉' : 'You did not pass this time.' ?></p>
    <a class="pj-btn" href="<?= site_url('study/' . $course['slug']) ?>">Back to course</a>
</div>
<?= $this->endSection() ?>
