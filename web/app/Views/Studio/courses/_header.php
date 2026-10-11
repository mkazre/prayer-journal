<?php
/** @var array $course @var string $tab */
$tabs = [
    'details'       => ['studio/courses/' . $course['id'], 'Details', 'edit'],
    'curriculum'    => ['studio/courses/' . $course['id'] . '/curriculum', 'Curriculum', 'layers'],
    'settings'      => ['studio/courses/' . $course['id'] . '/settings', 'Settings', 'sliders'],
    'students'      => ['studio/courses/' . $course['id'] . '/students', 'Students', 'users'],
    'gradebook'     => ['studio/courses/' . $course['id'] . '/gradebook', 'Gradebook', 'list-checks'],
    'analytics'     => ['studio/courses/' . $course['id'] . '/analytics', 'Analytics', 'chart'],
    'announcements' => ['studio/courses/' . $course['id'] . '/announcements', 'Announcements', 'megaphone'],
    'reviews'       => ['studio/courses/' . $course['id'] . '/reviews', 'Reviews', 'star'],
];
$published = $course['status'] === 'published';
?>
<div class="studio-head">
    <div>
        <div class="row gap-sm"><h1><?= esc($course['title']) ?></h1><span class="badge <?= $published ? 'badge-success' : 'badge-warning' ?> badge-dot"><?= $published ? 'Published' : 'Draft' ?></span></div>
        <p><?= esc(site_url('courses/' . $course['slug'])) ?></p>
    </div>
    <div class="row">
        <a class="btn" href="<?= site_url('courses/' . $course['slug']) ?>" target="_blank" rel="noopener"><?= icon('external') ?>View page</a>
        <a class="btn" href="<?= site_url('study/' . $course['slug']) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview as learner</a>
        <form method="post" action="<?= site_url('studio/courses/' . $course['id'] . '/publish') ?>" data-ajax data-ajax-reload><?= csrf_field() ?>
            <button class="btn <?= $published ? '' : 'btn-primary' ?>" type="submit"><?= icon($published ? 'eye-off' : 'send') ?><?= $published ? 'Unpublish' : 'Publish' ?></button>
        </form>
        <div class="dropdown" data-dropdown>
            <button class="btn btn-icon" type="button" data-dropdown-toggle aria-label="More actions"><?= icon('more') ?></button>
            <div class="dropdown-menu">
                <form method="post" action="<?= site_url('studio/courses/' . $course['id'] . '/duplicate') ?>"><?= csrf_field() ?><button class="menu-item" type="submit"><?= icon('copy-plus') ?>Duplicate course</button></form>
                <button class="menu-item danger" type="button" data-modal-open="delete-course"><?= icon('trash') ?>Delete course</button>
            </div>
        </div>
    </div>
</div>
<nav class="tabs">
    <?php foreach ($tabs as $key => [$url, $label, $ic]) : ?><a class="tab <?= $tab === $key ? 'is-active' : '' ?>" href="<?= site_url($url) ?>"><?= icon($ic) ?><?= $label ?></a><?php endforeach ?>
</nav>
<dialog class="modal" id="delete-course">
    <form method="post" action="<?= site_url('studio/courses/' . $course['id'] . '/delete') ?>">
        <?= csrf_field() ?>
        <div class="modal-head"><h3>Delete this course?</h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
        <div class="modal-body"><p>Learners will lose access to <strong><?= esc($course['title']) ?></strong>. Type <strong>DELETE</strong> to confirm.</p><input class="input" name="confirm" autocomplete="off" required></div>
        <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-danger" type="submit"><?= icon('trash') ?>Delete</button></div>
    </form>
</dialog>
