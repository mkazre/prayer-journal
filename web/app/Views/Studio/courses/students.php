<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><span><?= esc($course['title']) ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Studio/courses/_header', ['course' => $course, 'tab' => $tab]) ?>
<div class="toolbar">
    <form method="get" class="input-icon"><?= icon('search') ?><input class="input input-sm" type="search" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Search learners" style="width:260px"></form>
    <form method="post" action="<?= site_url('studio/courses/' . $course['id'] . '/students/enroll') ?>" class="input-group" style="max-width:420px">
        <?= csrf_field() ?><input class="input input-sm" type="email" name="email" placeholder="Enrol someone by email" required><button class="btn btn-sm btn-primary" type="submit"><?= icon('plus') ?>Enroll</button>
    </form>
</div>
<?php if ($rows === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('users', 'icon-lg') ?></div><h3>No learners yet</h3><p>Share the course page or enroll someone by email.</p></div>
<?php else : ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Learner</th><th>Progress</th><th>Status</th><th class="num">Journal</th><th>Enrolled</th><th>Last active</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($rows as $r) : $n = display_name(['id' => $r['user_id'], 'username' => $r['username']]); ?>
                <tr>
                    <td><div class="row row-nowrap"><?= avatar((int) $r['user_id'], $n, 'avatar-sm') ?><div><div class="fw-600"><?= esc($n) ?></div><div class="text-xs text-muted"><?= esc((string) $r['email']) ?></div></div></div></td>
                    <td style="min-width:150px"><div class="progress progress-sm"><span style="width:<?= (int) $r['progress_percent'] ?>%"></span></div><span class="text-xs text-muted"><?= (int) $r['progress_percent'] ?>%</span></td>
                    <td><span class="badge <?= $r['status'] === 'completed' ? 'badge-success' : ($r['status'] === 'revoked' ? 'badge-danger' : 'badge-info') ?>"><?= ucfirst($r['status']) ?></span> <span class="text-xs text-muted"><?= esc((string) $r['source']) ?></span></td>
                    <td class="num"><?= (int) $r['journal_count'] ?></td>
                    <td class="text-sm"><?= nice_date($r['enrolled_at']) ?></td>
                    <td class="text-sm text-muted"><?= time_ago($r['last_activity_at'] ?: $r['enrolled_at']) ?></td>
                    <td class="text-right">
                        <div class="dropdown" data-dropdown><button class="btn btn-sm btn-ghost btn-icon" type="button" data-dropdown-toggle aria-label="Actions"><?= icon('more') ?></button>
                            <div class="dropdown-menu">
                                <form method="post" action="<?= site_url('studio/enrollments/' . $r['id'] . '/reset') ?>" data-ajax data-ajax-reload data-confirm="Reset this learner's progress?"><?= csrf_field() ?><button class="menu-item" type="submit"><?= icon('refresh') ?>Reset progress</button></form>
                                <form method="post" action="<?= site_url('studio/enrollments/' . $r['id'] . '/revoke') ?>" data-ajax data-ajax-reload><?= csrf_field() ?><button class="menu-item <?= $r['status'] === 'revoked' ? '' : 'danger' ?>" type="submit"><?= icon($r['status'] === 'revoked' ? 'unlock' : 'lock') ?><?= $r['status'] === 'revoked' ? 'Restore access' : 'Remove access' ?></button></form>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table></div>
<?php endif ?>
<?= $this->endSection() ?>
