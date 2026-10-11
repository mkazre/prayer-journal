<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Grading</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="studio-head"><div><h1>Grading</h1><p>Review assignment submissions and leave feedback.</p></div></div>
<div class="chips mb-2"><a class="chip <?= $status !== 'graded' ? 'is-active' : '' ?>" href="<?= site_url('studio/grading') ?>">Waiting</a><a class="chip <?= $status === 'graded' ? 'is-active' : '' ?>" href="<?= site_url('studio/grading?status=graded') ?>">Graded</a></div>
<?php if ($rows === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('check-circle', 'icon-lg') ?></div><h3><?= $status === 'graded' ? 'Nothing graded yet' : 'All caught up' ?></h3><p>New submissions will appear here.</p></div>
<?php endif ?>
<div class="stack">
    <?php foreach ($rows as $r) : $n = display_name(['id' => $r['user_id'], 'username' => $r['username']]); ?>
        <div class="card">
            <div class="card-header"><div class="row row-nowrap"><?= avatar((int) $r['user_id'], $n, 'avatar-sm') ?><div><strong><?= esc($n) ?></strong> <span class="text-sm text-muted">· <?= esc($r['assignment_title']) ?> · <?= esc($r['course_title']) ?></span></div></div><span class="text-xs text-muted"><?= time_ago($r['submitted_at']) ?></span></div>
            <div class="card-body">
                <div class="panel" style="white-space:pre-wrap;font-family:var(--reading-font)"><?= esc((string) $r['content']) ?: '<em class="text-muted">No written response.</em>' ?></div>
                <?php if ($r['attachment_path']) : ?><p class="text-sm mt-1"><?= icon('paperclip') ?> File attached (stored privately)</p><?php endif ?>
                <form method="post" action="<?= site_url('studio/submissions/' . $r['id'] . '/grade') ?>" class="row items-start mt-2" data-ajax data-ajax-reload>
                    <?= csrf_field() ?>
                    <div class="field mb-0"><label class="label">Points / <?= (int) $r['max_points'] ?></label><input class="input" type="number" name="points" min="0" max="<?= (int) $r['max_points'] ?>" value="<?= esc((string) ($r['points_awarded'] ?? ''), 'attr') ?>" required style="width:110px"></div>
                    <div class="field mb-0 grow"><label class="label">Feedback</label><textarea class="textarea" name="feedback" rows="2" style="min-height:2.65rem"><?= esc((string) $r['feedback']) ?></textarea></div>
                    <button class="btn btn-primary" type="submit" style="margin-top:1.7rem"><?= icon('check') ?><?= $r['status'] === 'graded' ? 'Update' : 'Grade' ?></button>
                </form>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
