<?= $this->extend('layouts/bare') ?>
<?= $this->section('content') ?>
<div class="player">
    <?= view('Lms/study/_sidebar', compact('course', 'sections', 'current', 'enrollment', 'preview', 'settings')) ?>
    <div class="player-main">
        <?= view('Lms/study/_top', ['course' => $course, 'current' => $current]) ?>
        <div class="player-content">
            <div class="card empty">
                <div class="feature-icon gold"><?= icon('lock', 'icon-lg') ?></div>
                <h3><?= esc($quiz['title']) ?></h3>
                <p>You've used all <?= (int) $quiz['max_attempts'] ?> attempts for this quiz. Your best score was <?= $history ? max(array_column($history, 'score_percent')) : 0 ?>%.</p>
                <?php if ($next) : ?><a class="btn btn-primary" href="<?= site_url('study/' . $course['slug'] . '/' . $next['kind'] . '/' . $next['id']) ?>">Continue <?= icon('arrow-right') ?></a><?php endif ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
