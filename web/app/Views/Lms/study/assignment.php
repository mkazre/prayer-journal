<?= $this->extend('layouts/bare') ?>
<?= $this->section('content') ?>
<?php $graded = $submission && $submission['status'] === 'graded'; ?>
<div class="player">
    <?= view('Lms/study/_sidebar', compact('course', 'sections', 'current', 'enrollment', 'preview', 'settings')) ?>
    <div class="player-main">
        <?= view('Lms/study/_top', ['course' => $course, 'current' => $current]) ?>
        <div class="player-content">
            <header class="lesson-head mb-3">
                <span class="eyebrow"><?= icon('pen') ?>Assignment · <?= (int) $assignment['max_points'] ?> points<?= $assignment['due_at'] ? ' · due ' . nice_date($assignment['due_at']) : '' ?></span>
                <h1 class="mt-1"><?= esc($assignment['title']) ?></h1>
            </header>
            <div class="card card-body prose" style="max-width:none;white-space:pre-wrap;font-size:1.02rem"><?= scripture_links(esc((string) $assignment['instructions'])) ?></div>

            <?php if ($graded) : ?>
                <div class="card card-body mt-3" style="border-color:var(--success)">
                    <div class="row between"><h3 class="mb-0"><?= icon('award') ?> Graded</h3><span class="result-score" style="font-size:2rem;color:var(--success)"><?= (int) $submission['points_awarded'] ?>/<?= (int) $assignment['max_points'] ?></span></div>
                    <?php if ($submission['feedback']) : ?><div class="panel mt-2"><strong class="text-sm">Feedback from your instructor</strong><p class="mb-0 mt-1" style="white-space:pre-wrap"><?= esc($submission['feedback']) ?></p></div><?php endif ?>
                </div>
            <?php endif ?>

            <form class="card card-body mt-3" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row between mb-1"><h3 class="mb-0">Your response</h3>
                    <?php if ($submission) : ?><span class="badge <?= $graded ? 'badge-success' : 'badge-info' ?>"><?= $graded ? 'Graded' : 'Submitted ' . time_ago($submission['submitted_at']) ?></span><?php endif ?></div>
                <textarea class="textarea" name="content" rows="10" <?= $graded ? 'readonly' : '' ?> style="font-family:var(--reading-font);font-size:1.02rem" placeholder="Write your response here…"><?= esc((string) old('content', $submission['content'] ?? '')) ?></textarea>
                <?php if ((int) $assignment['allow_file'] && ! $graded) : ?>
                    <div class="field mt-2"><label class="label" for="attachment">Attach a file <span class="opt">(PDF, image, audio — max 20 MB)</span></label><input class="input" type="file" id="attachment" name="attachment"></div>
                <?php endif ?>
                <?php if (! empty($submission['attachment_path'])) : ?><p class="text-sm mt-1"><?= icon('paperclip') ?> A file is attached to your submission.</p><?php endif ?>
                <?php if (! $graded) : ?><div class="row mt-2"><button class="btn btn-primary" type="submit" <?= $enrollment ? '' : 'disabled' ?>><?= icon('send') ?><?= $submission ? 'Update submission' : 'Submit' ?></button></div><?php endif ?>
            </form>
        </div>
        <nav class="lesson-nav">
            <?php if ($prev) : ?><a class="btn btn-ghost" href="<?= site_url('study/' . $course['slug'] . '/' . $prev['kind'] . '/' . $prev['id']) ?>" data-key-prev><?= icon('arrow-left') ?>Previous</a><?php else : ?><span></span><?php endif ?>
            <?php if ($next) : ?><a class="btn btn-ghost" href="<?= site_url('study/' . $course['slug'] . '/' . $next['kind'] . '/' . $next['id']) ?>" data-key-next>Next<?= icon('arrow-right') ?></a><?php else : ?><a class="btn btn-ghost" href="<?= site_url('study/' . $course['slug']) ?>"><?= icon('home') ?>Overview</a><?php endif ?>
        </nav>
    </div>
</div>
<?= $this->endSection() ?>
