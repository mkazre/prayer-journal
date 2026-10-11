<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head-band"><div class="container page-head">
    <span class="eyebrow"><?= icon('graduation') ?>Learn</span><h1 class="mt-1">My learning</h1>
    <p>Pick up where you left off. Your progress, notes and journal are saved on every device.</p>
</div></div>
<div class="container">
    <div class="tabs" role="tablist">
        <button class="tab is-active" data-tab="in-progress" role="tab"><?= icon('play-circle') ?>In progress <span class="badge"><?= count($inProgress) ?></span></button>
        <button class="tab" data-tab="completed" role="tab"><?= icon('award') ?>Completed <span class="badge"><?= count($completed) ?></span></button>
    </div>
    <?php foreach (['in-progress' => $inProgress, 'completed' => $completed] as $id => $list) : ?>
        <div class="tab-panel <?= $id === 'in-progress' ? 'is-active' : '' ?>" id="<?= $id ?>">
            <?php if ($list === []) : ?>
                <div class="card empty"><div class="feature-icon"><?= icon($id === 'completed' ? 'award' : 'graduation', 'icon-lg') ?></div>
                    <h3><?= $id === 'completed' ? 'No completed courses yet' : 'You are not taking any courses' ?></h3>
                    <p><?= $id === 'completed' ? 'Finish a course to earn a certificate.' : 'Find a course and start today.' ?></p>
                    <a class="btn btn-primary" href="<?= site_url('courses') ?>">Browse courses</a></div>
            <?php else : ?>
                <div class="grid grid-3">
                    <?php foreach ($list as $e) : ?>
                        <div class="card course-card">
                            <a class="course-cover" href="<?= site_url('study/' . $e['slug']) ?>" style="<?= course_cover_style($e) ?>">
                                <?php if ($e['thumbnail_path']) : ?><img src="<?= base_url($e['thumbnail_path']) ?>" alt=""><?php else : ?><div class="cover-art"><span><?= esc($e['title']) ?></span></div><?php endif ?>
                            </a>
                            <div class="course-body">
                                <h3><a href="<?= site_url('study/' . $e['slug']) ?>" style="color:inherit"><?= esc($e['title']) ?></a></h3>
                                <p class="text-sm text-muted mb-0"><?= $e['last_lesson_title'] ? 'Last: ' . esc($e['last_lesson_title']) : 'Enrolled ' . nice_date($e['enrolled_at']) ?></p>
                                <div class="progress mt-1"><span style="width:<?= (int) $e['progress_percent'] ?>%"></span></div>
                                <div class="course-foot">
                                    <span class="text-sm text-muted"><?= (int) $e['progress_percent'] ?>% complete</span>
                                    <?php if (isset($certificates[$e['course_id']])) : ?>
                                        <a class="btn btn-sm btn-soft" href="<?= site_url('certificates/' . $certificates[$e['course_id']]) ?>"><?= icon('award') ?>Certificate</a>
                                    <?php else : ?>
                                        <a class="btn btn-sm btn-primary" href="<?= site_url('study/' . $e['slug']) ?>"><?= icon('play') ?>Resume</a>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
