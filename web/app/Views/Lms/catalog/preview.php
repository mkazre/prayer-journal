<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container container-narrow" style="max-width:820px">
    <div class="page-head">
        <nav class="breadcrumbs"><a href="<?= site_url('courses') ?>">Courses</a><?= icon('chevron-right') ?><a href="<?= site_url('courses/' . $course['slug']) ?>"><?= esc($course['title']) ?></a><?= icon('chevron-right') ?><span>Free preview</span></nav>
        <span class="badge badge-brand"><?= icon('eye') ?>Free preview</span>
        <h1 class="mt-1"><?= esc($lesson['title']) ?></h1>
    </div>
    <?= view('Lms/study/_scripture', ['verses' => $verses, 'reference' => $lesson['bible_reference']]) ?>
    <article class="prose"><?= scripture_links(clean_html($lesson['content'])) ?></article>
    <div class="welcome-band mt-4 text-center">
        <h2>Continue the journey</h2>
        <p>Enroll to unlock every lesson, save your journal entries and track your progress.</p>
        <a class="btn btn-accent btn-lg" href="<?= site_url('courses/' . $course['slug']) ?>">View the full course <?= icon('arrow-right') ?></a>
    </div>
</div>
<?= $this->endSection() ?>
