<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($lesson['title']) ?></h1>
    <?php if ($lesson['bible_reference']) : ?>
        <p><em><?= esc($lesson['bible_reference']) ?></em></p>
    <?php endif ?>
    <?php if ($lesson['video_url']) : ?>
        <p><a href="<?= esc($lesson['video_url']) ?>" target="_blank">Watch Video</a></p>
    <?php endif ?>
    <div><?= nl2br(esc($lesson['content'])) ?></div>

    <form method="post" action="<?= site_url('study/' . $course['slug'] . '/lesson/' . $lesson['id'] . '/complete') ?>">
        <?= csrf_field() ?>
        <button class="pj-btn pj-btn-gold" type="submit">Mark Complete</button>
    </form>
    <a href="<?= site_url('study/' . $course['slug']) ?>">Back to course</a>
</div>
<?= $this->endSection() ?>
