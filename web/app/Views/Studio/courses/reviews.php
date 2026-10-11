<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><span><?= esc($course['title']) ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Studio/courses/_header', ['course' => $course, 'tab' => $tab]) ?>
<?php if ($reviews === []) : ?><div class="card empty"><div class="feature-icon gold"><?= icon('star', 'icon-lg') ?></div><h3>No reviews yet</h3><p>Enrolled learners can rate the course from its landing page.</p></div><?php endif ?>
<div class="stack">
    <?php foreach ($reviews as $r) : $n = display_name(['id' => $r['user_id'], 'username' => $r['username']]); ?>
        <div class="card card-body row items-start row-nowrap">
            <?= avatar((int) $r['user_id'], $n) ?>
            <div class="grow"><div class="row gap-sm"><strong><?= esc($n) ?></strong><?= stars((float) $r['rating']) ?><span class="text-xs text-muted"><?= time_ago($r['created_at']) ?></span></div><?php if ($r['body']) : ?><p class="text-2 mb-0 mt-1"><?= esc($r['body']) ?></p><?php endif ?></div>
            <form method="post" action="<?= site_url('studio/reviews/' . $r['id'] . '/delete') ?>" data-ajax data-ajax-reload data-confirm="Remove this review?"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit"><?= icon('trash') ?>Remove</button></form>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
