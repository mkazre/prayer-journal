<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($title) ?></h1>
    <?php foreach ($submissions as $s) : ?>
        <div class="pj-card" style="margin-bottom:1rem">
            <strong><?= esc($s['username']) ?></strong> — <?= esc($s['status']) ?>
            <p><?= nl2br(esc($s['content'])) ?></p>
            <?php if ($s['status'] === 'graded') : ?>
                <p>Score: <?= esc($s['points_awarded']) ?> / <?= esc($assignment['max_points']) ?><br>Feedback: <?= esc($s['feedback']) ?></p>
            <?php else : ?>
                <form method="post" action="<?= site_url('instructor/submissions/' . $s['id'] . '/grade') ?>">
                    <?= csrf_field() ?>
                    <input type="number" name="points" placeholder="Points" max="<?= esc($assignment['max_points']) ?>" required>
                    <input type="text" name="feedback" placeholder="Feedback">
                    <button class="pj-btn" type="submit">Grade</button>
                </form>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
