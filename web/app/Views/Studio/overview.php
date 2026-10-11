<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><span>Overview</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="studio-head">
    <div><h1>Welcome back, <?= esc(display_name()) ?></h1><p>Here's what's happening across <?= esc(site_name()) ?> in the last 30 days.</p></div>
    <div class="row">
        <?php if ($isInstructor) : ?><a class="btn btn-primary" href="<?= site_url('studio/courses/new') ?>"><?= icon('plus') ?>New course</a><?php endif ?>
        <?php if ($isVendor) : ?><a class="btn" href="<?= site_url('studio/products/new') ?>"><?= icon('package') ?>New product</a><?php endif ?>
    </div>
</div>

<div class="grid grid-4">
    <?php if ($isAdmin) : ?>
        <div class="card stat"><div class="stat-label"><?= icon('wallet') ?>Revenue (30 days)</div><div class="stat-value"><?= money($revenue) ?></div><div class="stat-delta text-muted"><?= $orderCount ?> paid orders</div></div>
        <a class="card card-hover stat" href="<?= site_url('studio/orders?status=paid') ?>"><div class="stat-label"><?= icon('truck') ?>To fulfil</div><div class="stat-value"><?= $toFulfil ?></div><div class="stat-delta text-muted">paid, not yet shipped</div></a>
    <?php endif ?>
    <?php if ($isInstructor) : ?>
        <a class="card card-hover stat" href="<?= site_url('studio/students') ?>"><div class="stat-label"><?= icon('users') ?>Learners</div><div class="stat-value"><?= number_format($learners) ?></div><div class="stat-delta">+<?= $newLearners ?> this month</div></a>
        <a class="card card-hover stat" href="<?= site_url('studio/grading') ?>"><div class="stat-label"><?= icon('list-checks') ?>Waiting for grading</div><div class="stat-value"><?= $toGrade ?></div><div class="stat-delta text-muted"><?= number_format($completions) ?> lessons completed</div></a>
    <?php endif ?>
    <?php if ($isAdmin && ! $isInstructor) : ?>
        <div class="card stat"><div class="stat-label"><?= icon('users') ?>Users</div><div class="stat-value"><?= $users ?></div></div>
    <?php endif ?>
</div>

<div class="grid grid-2 mt-3">
    <?php if ($isAdmin) : ?>
        <section class="card"><div class="card-header"><h2><?= icon('chart') ?> Sales</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('studio/reports') ?>">Reports</a></div><div class="card-body"><?= view('Studio/_bars', ['series' => $salesChart, 'isMoney' => true, 'label' => 'Daily sales']) ?></div></section>
    <?php endif ?>
    <?php if ($isInstructor) : ?>
        <section class="card"><div class="card-header"><h2><?= icon('trending') ?> New enrollments</h2><span class="text-sm text-muted"><?= $newLearners ?> in 30 days</span></div><div class="card-body"><?= view('Studio/_bars', ['series' => $enrollChart, 'label' => 'Daily enrollments']) ?></div></section>
    <?php endif ?>
</div>

<div class="grid grid-2 mt-3">
    <?php if ($isInstructor) : ?>
        <section class="card">
            <div class="card-header"><h2><?= icon('graduation') ?> Your courses</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('studio/courses') ?>">All <?= $courseCount ?></a></div>
            <?php if ($courses === []) : ?><div class="card-body text-muted">No courses yet. <a href="<?= site_url('studio/courses/new') ?>">Create your first course</a>.</div><?php endif ?>
            <div class="divider-list">
                <?php foreach ($courses as $c) : ?>
                    <a class="card-body-sm row row-nowrap" href="<?= site_url('studio/courses/' . $c['id']) ?>" style="color:inherit">
                        <span class="course-cover" style="width:64px;aspect-ratio:16/10;border-radius:8px;flex:none;<?= course_cover_style($c) ?>"><?php if ($c['thumbnail_path']) : ?><img src="<?= base_url($c['thumbnail_path']) ?>" alt=""><?php endif ?></span>
                        <div class="grow"><div class="fw-600 truncate"><?= esc($c['title']) ?></div><div class="text-xs text-muted"><?= (int) $c['lesson_count'] ?> lessons · <?= (int) $c['student_count'] ?> learners</div></div>
                        <span class="badge <?= $c['status'] === 'published' ? 'badge-success' : '' ?>"><?= esc(ucfirst($c['status'])) ?></span>
                    </a>
                <?php endforeach ?>
            </div>
        </section>
        <section class="card">
            <div class="card-header"><h2><?= icon('users') ?> Recent learners</h2></div>
            <?php if ($recentLearners === []) : ?><div class="card-body text-muted">Enrollments will appear here.</div><?php endif ?>
            <div class="divider-list">
                <?php foreach ($recentLearners as $l) : $n = display_name(['id' => $l['user_id'], 'username' => $l['username']]); ?>
                    <div class="card-body-sm row row-nowrap"><?= avatar((int) $l['user_id'], $n, 'avatar-sm') ?><div class="grow"><div class="text-sm fw-600"><?= esc($n) ?></div><div class="text-xs text-muted truncate"><?= esc($l['course_title']) ?></div></div><span class="text-xs text-muted"><?= time_ago($l['enrolled_at']) ?></span></div>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>
    <?php if ($isAdmin) : ?>
        <section class="card">
            <div class="card-header"><h2><?= icon('receipt') ?> Latest orders</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('studio/orders') ?>">All orders</a></div>
            <?php if ($recentOrders === []) : ?><div class="card-body text-muted">No orders yet.</div><?php endif ?>
            <div class="divider-list">
                <?php foreach ($recentOrders as $o) : [$sl, $tone] = \App\Modules\Shop\Models\OrderModel::STATUSES[$o['status']] ?? [$o['status'], 'brand']; ?>
                    <a class="card-body-sm row" href="<?= site_url('studio/orders/' . $o['id']) ?>" style="color:inherit"><span class="fw-600 grow"><?= esc($o['order_number']) ?> <span class="text-xs text-muted fw-500"><?= esc((string) $o['customer_name']) ?></span></span><span class="badge badge-<?= $tone ?>"><?= $sl ?></span><span class="price text-sm"><?= money((int) $o['total_cents'], $o['currency']) ?></span></a>
                <?php endforeach ?>
            </div>
        </section>
        <section class="card">
            <div class="card-header"><h2><?= icon('alert') ?> Low stock</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('studio/inventory') ?>">Inventory</a></div>
            <?php if ($lowStock === []) : ?><div class="card-body text-sm text-muted"><?= icon('check-circle') ?> Everything is well stocked.</div><?php endif ?>
            <div class="divider-list">
                <?php foreach ($lowStock as $s) : ?><a class="card-body-sm row" href="<?= site_url('studio/products/' . $s['product_id']) ?>" style="color:inherit"><span class="grow text-sm"><?= esc($s['title']) ?> <span class="text-muted"><?= esc($s['sku']) ?></span></span><span class="badge <?= (int) $s['qty'] <= 0 ? 'badge-danger' : 'badge-warning' ?>"><?= (int) $s['qty'] ?> left</span></a><?php endforeach ?>
            </div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
