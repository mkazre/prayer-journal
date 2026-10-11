<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Students</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="studio-head"><div><h1>Students</h1><p>Everyone enrolled in your courses.</p></div>
    <form method="get" class="input-icon"><?= icon('search') ?><input class="input" type="search" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Search by name or email" style="width:280px"></form></div>
<?php if ($rows === []) : ?><div class="card empty"><div class="feature-icon"><?= icon('users', 'icon-lg') ?></div><h3>No students yet</h3></div><?php else : ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Learner</th><th class="num">Courses</th><th class="num">Completed</th><th>Average progress</th><th>Last active</th></tr></thead>
    <tbody><?php foreach ($rows as $r) : $n = display_name(['id' => $r['user_id'], 'username' => $r['username']]); ?>
        <tr><td><div class="row row-nowrap"><?= avatar((int) $r['user_id'], $n, 'avatar-sm') ?><div><div class="fw-600"><?= esc($n) ?></div><div class="text-xs text-muted"><?= esc((string) $r['email']) ?></div></div></div></td>
            <td class="num"><?= (int) $r['courses'] ?></td><td class="num"><?= (int) $r['completed'] ?></td>
            <td style="min-width:160px"><div class="progress progress-sm"><span style="width:<?= round((float) $r['avg_progress']) ?>%"></span></div><span class="text-xs text-muted"><?= round((float) $r['avg_progress']) ?>%</span></td>
            <td class="text-sm text-muted"><?= time_ago($r['last_seen']) ?></td></tr>
    <?php endforeach ?></tbody>
</table></div>
<?php endif ?>
<?= $this->endSection() ?>
