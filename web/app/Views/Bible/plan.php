<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php use App\Modules\Bible\Models\ReadingPlanModel; $pct = (int) floor(count($done) / max(1, (int) $plan['days']) * 100); ?>
<div class="container container-narrow" style="max-width:900px">
    <div class="page-head">
        <nav class="breadcrumbs"><a href="<?= site_url('bible') ?>">Bible</a><?= icon('chevron-right') ?><a href="<?= site_url('bible/plans') ?>">Reading plans</a><?= icon('chevron-right') ?><span><?= esc($plan['title']) ?></span></nav>
        <div class="row between items-start">
            <div><h1><?= esc($plan['title']) ?></h1><p><?= esc($plan['description']) ?></p></div>
            <?php if (! auth()->loggedIn()) : ?>
                <a class="btn btn-primary" href="<?= site_url('login') ?>">Sign in to start</a>
            <?php elseif (! $userPlan) : ?>
                <form method="post" action="<?= site_url('bible/plans/' . $plan['slug'] . '/start') ?>"><?= csrf_field() ?><button class="btn btn-primary btn-lg" type="submit"><?= icon('play') ?>Start this plan</button></form>
            <?php else : ?>
                <form method="post" action="<?= site_url('bible/plans/' . $plan['slug'] . '/leave') ?>" data-confirm="Leave this plan? Your progress will be removed."><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Leave plan</button></form>
            <?php endif ?>
        </div>
        <?php if ($userPlan) : ?>
            <div class="card card-body mt-2 row gap-lg">
                <div class="ring" style="--p:<?= $pct ?>;--size:72px"><span><?= $pct ?>%</span></div>
                <div class="grow"><strong><?= count($done) ?> of <?= (int) $plan['days'] ?> days complete</strong><div class="text-sm text-muted">Started <?= nice_date($userPlan['started_at']) ?><?= $userPlan['completed_at'] ? ' · finished ' . nice_date($userPlan['completed_at']) : '' ?></div></div>
                <?php if ($nextDay <= (int) $plan['days']) : ?><a class="btn btn-primary" href="#day-<?= $nextDay ?>">Go to day <?= $nextDay ?> <?= icon('arrow-down') ?></a><?php endif ?>
            </div>
        <?php endif ?>
    </div>
    <div class="day-list">
        <?php foreach ($days as $d) : $isDone = in_array((int) $d['day'], $done, true); ?>
            <div class="day-item <?= $isDone ? 'is-done' : '' ?> <?= $userPlan && (int) $d['day'] === $nextDay ? 'is-today' : '' ?>" id="day-<?= $d['day'] ?>">
                <span class="day-num"><?= $isDone ? icon('check') : (int) $d['day'] ?></span>
                <div class="grow">
                    <div class="text-xs text-muted fw-600">Day <?= (int) $d['day'] ?></div>
                    <div class="chips mt-1">
                        <?php foreach (ReadingPlanModel::links($d['readings']) as $l) : ?><a class="chip" style="padding:.25rem .65rem;font-size:.82rem" href="<?= $l['url'] ?>"><?= esc($l['label']) ?></a><?php endforeach ?>
                    </div>
                </div>
                <?php if ($userPlan) : ?>
                    <form method="post" action="<?= site_url('bible/plans/' . $plan['slug'] . '/day/' . $d['day']) ?>" data-ajax data-ajax-reload><?= csrf_field() ?>
                        <button class="btn btn-sm <?= $isDone ? 'btn-ghost' : 'btn-soft' ?>" type="submit"><?= $isDone ? icon('undo') . 'Undo' : icon('check') . 'Done' ?></button>
                    </form>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
