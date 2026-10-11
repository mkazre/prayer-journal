<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Courses</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="studio-head">
    <div><h1>Courses</h1><p>Create, organise and publish your courses.</p></div>
    <a class="btn btn-primary" href="<?= site_url('studio/courses/new') ?>"><?= icon('plus') ?>New course</a>
</div>
<div class="chips mb-2">
    <?php foreach (['' => 'All', 'published' => 'Published', 'draft' => 'Drafts'] as $k => $l) : ?><a class="chip <?= $status === $k ? 'is-active' : '' ?>" href="<?= site_url('studio/courses' . ($k ? '?status=' . $k : '')) ?>"><?= $l ?></a><?php endforeach ?>
</div>
<?php if ($courses === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('graduation', 'icon-lg') ?></div><h3>No courses yet</h3><p>Courses are made of modules, lessons, quizzes and assignments. Start with a title — you can change everything later.</p><a class="btn btn-primary" href="<?= site_url('studio/courses/new') ?>"><?= icon('plus') ?>Create a course</a></div>
<?php else : ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Course</th><th>Status</th><th class="num">Lessons</th><th class="num">Learners</th><th class="num">Rating</th><th>Price</th><th>Updated</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($courses as $c) : ?>
                <tr>
                    <td><a class="row row-nowrap" href="<?= site_url('studio/courses/' . $c['id']) ?>" style="color:inherit"><span class="course-cover" style="width:72px;aspect-ratio:16/10;border-radius:8px;flex:none;<?= course_cover_style($c) ?>"><?php if ($c['thumbnail_path']) : ?><img src="<?= base_url($c['thumbnail_path']) ?>" alt=""><?php endif ?></span><span class="fw-600"><?= esc($c['title']) ?></span></a></td>
                    <td><span class="badge badge-dot <?= $c['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst($c['status']) ?></span><?= $c['is_featured'] ? ' <span class="badge badge-accent">Featured</span>' : '' ?></td>
                    <td class="num"><?= (int) $c['lesson_count'] ?></td>
                    <td class="num"><?= (int) $c['student_count'] ?></td>
                    <td class="num"><?= $c['avg_rating'] ? number_format((float) $c['avg_rating'], 1) : '—' ?></td>
                    <td><?= ($c['access_mode'] ?? '') === 'subscription' ? 'Membership' : price_label((int) $c['price_cents'], $c['currency']) ?></td>
                    <td class="text-muted text-sm"><?= time_ago($c['updated_at']) ?></td>
                    <td class="text-right nowrap"><a class="btn btn-sm" href="<?= site_url('studio/courses/' . $c['id'] . '/curriculum') ?>"><?= icon('layers') ?>Curriculum</a> <a class="btn btn-sm btn-ghost btn-icon" href="<?= site_url('studio/courses/' . $c['id']) ?>" aria-label="Edit"><?= icon('edit') ?></a></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table></div>
<?php endif ?>
<?= $this->endSection() ?>
