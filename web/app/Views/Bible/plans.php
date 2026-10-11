<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head-band"><div class="container page-head">
    <nav class="breadcrumbs"><a href="<?= site_url('bible') ?>">Bible</a><?= icon('chevron-right') ?><span>Reading plans</span></nav>
    <h1>Reading plans</h1>
    <p>Small daily readings that add up. Tick off each day and watch your progress grow.</p>
</div></div>
<div class="container">
    <?php if ($plans === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('calendar', 'icon-lg') ?></div><h3>No plans yet</h3><p>An administrator can generate them with <code>php spark bible:plans</code>.</p></div>
    <?php endif ?>
    <div class="grid grid-3">
        <?php foreach ($plans as $p) : $up = $p['user_plan']; $pct = $up ? (int) floor($up['days_done'] / max(1, $p['days']) * 100) : 0; ?>
            <a class="card card-hover card-body plan-card" href="<?= site_url('bible/plans/' . $p['slug']) ?>" style="color:inherit">
                <div class="row between"><span class="feature-icon <?= $up ? '' : 'gold' ?>" style="margin:0"><?= icon($up && $up['completed_at'] ? 'trophy' : 'calendar-check', 'icon-lg') ?></span><span class="badge"><?= (int) $p['days'] ?> days</span></div>
                <h3 class="mt-2"><?= esc($p['title']) ?></h3>
                <p class="text-sm text-2"><?= esc($p['description']) ?></p>
                <?php if ($up) : ?>
                    <div class="progress"><span style="width:<?= $pct ?>%"></span></div>
                    <div class="text-xs text-muted mt-1"><?= $up['completed_at'] ? 'Completed ' . nice_date($up['completed_at']) : (int) $up['days_done'] . ' of ' . (int) $p['days'] . ' days · ' . $pct . '%' ?></div>
                <?php else : ?>
                    <span class="text-sm fw-600 text-brand">View plan <?= icon('arrow-right') ?></span>
                <?php endif ?>
            </a>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
