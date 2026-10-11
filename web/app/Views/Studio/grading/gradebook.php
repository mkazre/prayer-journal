<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><span><?= esc($course['title']) ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Studio/courses/_header', ['course' => $course, 'tab' => $tab]) ?>
<?php if ($rows === []) : ?><div class="card empty"><div class="feature-icon"><?= icon('list-checks', 'icon-lg') ?></div><h3>No learners yet</h3></div><?php else : ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Learner</th><th>Progress</th><th class="num">Quiz average</th><th class="num">Assignment average</th><th class="num">Overall</th></tr></thead>
    <tbody><?php foreach ($rows as $r) : $n = display_name(['id' => $r['user_id'], 'username' => $r['username']]); ?>
        <tr><td class="fw-600"><?= esc($n) ?></td><td style="min-width:140px"><div class="progress progress-sm"><span style="width:<?= (int) $r['progress_percent'] ?>%"></span></div><span class="text-xs text-muted"><?= (int) $r['progress_percent'] ?>%</span></td>
            <td class="num"><?= $r['quiz_avg_percent'] !== null ? (int) $r['quiz_avg_percent'] . '%' : '—' ?></td><td class="num"><?= $r['assignment_avg_percent'] !== null ? (int) $r['assignment_avg_percent'] . '%' : '—' ?></td>
            <td class="num fw-700"><?= $r['overall_percent'] !== null ? (int) $r['overall_percent'] . '%' : '—' ?></td></tr>
    <?php endforeach ?></tbody>
</table></div>
<?php endif ?>
<?= $this->endSection() ?>
