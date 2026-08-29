<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($assignment['title']) ?></h1>
    <p><?= esc($assignment['instructions']) ?></p>

    <?php if ($submission) : ?>
        <p><strong>Status:</strong> <?= esc($submission['status']) ?></p>
        <?php if ($submission['status'] === 'graded') : ?>
            <p><strong>Score:</strong> <?= esc($submission['points_awarded']) ?> / <?= esc($assignment['max_points']) ?></p>
            <p><strong>Feedback:</strong> <?= esc($submission['feedback']) ?></p>
        <?php endif ?>
        <p><em>Your submission:</em></p>
        <p><?= nl2br(esc($submission['content'])) ?></p>
    <?php endif ?>

    <form method="post">
        <?= csrf_field() ?>
        <textarea name="content" rows="6" style="width:100%" placeholder="Write your submission..."><?= esc($submission['content'] ?? '') ?></textarea>
        <button class="pj-btn pj-btn-gold" type="submit">Submit</button>
    </form>
</div>
<?= $this->endSection() ?>
