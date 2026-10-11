<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $kinds = \App\Modules\Lms\Models\JournalEntryModel::KINDS; ?>
<div class="container container-narrow" style="max-width:860px">
    <div class="page-head row between items-start">
        <div>
            <nav class="breadcrumbs"><a href="<?= site_url('study/' . $course['slug']) ?>"><?= esc($course['title']) ?></a><?= icon('chevron-right') ?><span>My notes</span></nav>
            <h1>My notes &amp; journal</h1>
            <p>Everything you've written in this course, in lesson order.</p>
        </div>
        <button class="btn no-print" type="button" data-print><?= icon('printer') ?>Print</button>
    </div>
    <?php if ($notes === [] && $journal === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('note', 'icon-lg') ?></div><h3>Nothing here yet</h3><p>Notes and journal prompts appear under each lesson.</p></div>
    <?php endif ?>
    <?php if ($notes) : ?>
        <h2 style="font-size:1.3rem">Lesson notes</h2>
        <div class="card mb-3">
            <?php foreach ($notes as $n) : ?>
                <div class="journal-entry"><a class="fw-700" href="<?= site_url('study/' . $course['slug'] . '/lesson/' . $n['lesson_id']) ?>"><?= esc($n['lesson_title']) ?></a><div class="body"><?= esc($n['body']) ?></div></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
    <?php if ($journal) : ?>
        <h2 style="font-size:1.3rem">Journal entries</h2>
        <div class="card">
            <?php foreach ($journal as $j) : [$kLabel, $kIcon, $kClass] = $kinds[$j['kind']] ?? $kinds['note']; ?>
                <div class="journal-entry row items-start row-nowrap">
                    <span class="kind-icon <?= $kClass ?>"><?= icon($kIcon) ?></span>
                    <div class="grow"><div class="text-xs text-muted"><?= esc((string) $j['lesson_title']) ?> · <?= esc($kLabel) ?></div><div class="body"><?= esc($j['body']) ?></div>
                        <?php if ($j['is_answered']) : ?><span class="badge badge-success"><?= icon('check') ?>Answered</span><?php endif ?></div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
