<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Welcome, <?= esc($user->username ?? $user->email) ?></h1>
    <p>Your roles: <?= esc(implode(', ', $roles) ?: 'student') ?></p>

    <?php if (! empty($enrollments)) : ?>
        <h2>Continue Learning</h2>
        <?php foreach ($enrollments as $e) : ?>
            <div class="pj-card" style="margin-bottom:.75rem">
                <strong><?= esc($e['course_title']) ?></strong>
                <div class="pj-progress" style="margin:.5rem 0"><span style="width:<?= (int) $e['progress_percent'] ?>%"></span></div>
                <?php if ($e['lesson_title']) : ?>
                    <a class="pj-btn" href="<?= site_url('study/' . $e['slug'] . '/lesson/' . $e['last_lesson_id']) ?>">Continue: <?= esc($e['lesson_title']) ?></a>
                <?php else : ?>
                    <a class="pj-btn" href="<?= site_url('study/' . $e['slug']) ?>">Start Course</a>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    <?php endif ?>

    <?php if (in_array('instructor', $roles, true) || in_array('admin', $roles, true) || in_array('superadmin', $roles, true)) : ?>
        <p><a class="pj-btn" href="<?= site_url('instructor/courses') ?>">My Courses (Instructor)</a></p>
    <?php endif ?>

    <?php if (in_array('vendor', $roles, true) || in_array('admin', $roles, true) || in_array('superadmin', $roles, true)) : ?>
        <p><a class="pj-btn" href="<?= site_url('shop-admin/products') ?>">Manage Products</a></p>
    <?php endif ?>

    <?php if (in_array('admin', $roles, true) || in_array('superadmin', $roles, true)) : ?>
        <p>
            <a class="pj-btn" href="<?= site_url('shop-admin/orders') ?>">Orders</a>
            <a class="pj-btn" href="<?= site_url('shop-admin/discounts') ?>">Discounts</a>
            <a class="pj-btn" href="<?= site_url('shop-admin/reports') ?>">Reports</a>
            <a class="pj-btn" href="<?= site_url('shop-admin/plans') ?>">Plans</a>
            <a class="pj-btn" href="<?= site_url('shop-admin/announcement') ?>">Announcement</a>
        </p>
    <?php endif ?>

    <p>
        <a class="pj-btn" href="<?= site_url('courses') ?>">Browse Courses</a>
        <a class="pj-btn" href="<?= site_url('my-orders') ?>">My Orders</a>
        <a class="pj-btn" href="<?= site_url('my-certificates') ?>">My Certificates</a>
        <a class="pj-btn" href="<?= site_url('wishlist') ?>">My Wishlist</a>
        <a class="pj-btn" href="<?= site_url('bible/my-bookmarks') ?>">My Bible Bookmarks</a>
    </p>
</div>
<?= $this->endSection() ?>
