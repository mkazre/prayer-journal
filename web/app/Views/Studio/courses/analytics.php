<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><span><?= esc($course['title']) ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Studio/courses/_header', ['course' => $course, 'tab' => $tab]) ?>
<div class="grid grid-4">
    <div class="card stat"><div class="stat-label"><?= icon('users') ?>Enrolled</div><div class="stat-value"><?= $total ?></div></div>
    <div class="card stat"><div class="stat-label"><?= icon('award') ?>Completion rate</div><div class="stat-value"><?= $completionRate ?>%</div></div>
    <div class="card stat"><div class="stat-label"><?= icon('activity') ?>Average progress</div><div class="stat-value"><?= $avgProgress ?>%</div></div>
    <div class="card stat"><div class="stat-label"><?= icon('notebook') ?>Journal entries</div><div class="stat-value"><?= number_format($journalTotal) ?></div></div>
</div>
<div class="grid grid-2 mt-3">
    <section class="card"><div class="card-header"><h2><?= icon('trending') ?> Enrollments (30 days)</h2></div><div class="card-body"><?= view('Studio/_bars', ['series' => $series, 'label' => 'Enrollments']) ?></div></section>
    <section class="card"><div class="card-header"><h2><?= icon('layers') ?> Where learners are</h2></div><div class="card-body">
        <?php foreach ($bands as $label => $n) : ?><div class="hbar"><span><?= esc($label) ?></span><div class="progress"><span style="width:<?= $total ? round($n / $total * 100) : 0 ?>%"></span></div><span class="text-right fw-600"><?= $n ?></span></div><?php endforeach ?>
    </div></section>
</div>
<?php if ($quizzes) : ?>
    <section class="mt-3"><h2 style="font-size:1.1rem;font-family:var(--font-body)">Quizzes</h2>
        <div class="table-wrap"><table class="table"><thead><tr><th>Quiz</th><th class="num">Attempts</th><th class="num">Average score</th><th class="num">Learners passed</th><th class="num">Pass mark</th></tr></thead><tbody>
            <?php foreach ($quizzes as $q) : ?><tr><td><a href="<?= site_url('studio/quizzes/' . $q['id']) ?>"><?= esc($q['title']) ?></a></td><td class="num"><?= (int) $q['attempts'] ?></td><td class="num"><?= $q['avg_score'] !== null ? round((float) $q['avg_score']) . '%' : '—' ?></td><td class="num"><?= (int) $q['passed_users'] ?></td><td class="num"><?= (int) $q['pass_percent'] ?>%</td></tr><?php endforeach ?>
        </tbody></table></div></section>
<?php endif ?>
<section class="mt-3"><h2 style="font-size:1.1rem;font-family:var(--font-body)">Lesson engagement</h2>
    <?php $maxC = max(1, ...array_map('intval', array_column($lessons, 'completions') ?: [0])); ?>
    <div class="card card-body" style="max-height:520px;overflow:auto">
        <?php foreach ($lessons as $l) : ?>
            <div class="hbar"><a class="truncate" href="<?= site_url('studio/lessons/' . $l['id']) ?>" style="color:inherit"><?= esc($l['title']) ?></a><div class="progress"><span style="width:<?= round($l['completions'] / $maxC * 100) ?>%"></span></div><span class="text-right text-sm"><?= (int) $l['completions'] ?><?= $l['journal'] ? ' · ' . (int) $l['journal'] . 'j' : '' ?></span></div>
        <?php endforeach ?>
    </div>
    <p class="help">Completions per lesson (j = journal entries written). A sharp drop shows where learners stop.</p>
</section>
<?= $this->endSection() ?>
