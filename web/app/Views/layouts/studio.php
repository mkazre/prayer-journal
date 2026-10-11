<?php
$user      = auth()->user();
$isAdmin   = $user->inGroup('superadmin', 'admin');
$isManager = $isAdmin || $user->inGroup('manager');
$isTeacher = $isManager || $user->inGroup('instructor');
$isVendor  = $isManager || $user->inGroup('vendor');
$active    = $active ?? '';
$nav = [];
$nav[] = ['', [['studio', 'Overview', 'layout', 'overview']]];
if ($isTeacher) {
    $nav[] = ['Learning', [
        ['studio/courses', 'Courses', 'graduation', 'courses'],
        ['studio/grading', 'Grading', 'list-checks', 'grading'],
        ['studio/students', 'Students', 'users', 'students'],
    ]];
}
if ($isVendor) {
    $shop = [['studio/products', 'Products', 'package', 'products'], ['studio/inventory', 'Inventory', 'boxes', 'inventory']];
    if ($isManager) {
        $shop = array_merge($shop, [
            ['studio/orders', 'Orders', 'receipt', 'orders'],
            ['studio/collections', 'Collections', 'layers', 'collections'],
            ['studio/discounts', 'Discounts', 'percent', 'discounts'],
            ['studio/shipping', 'Shipping & tax', 'truck', 'shipping'],
            ['studio/plans', 'Membership plans', 'sparkles', 'plans'],
            ['studio/reports', 'Reports', 'chart', 'reports'],
        ]);
    }
    $nav[] = ['Shop', $shop];
}
if ($isManager) {
    $site = [['studio/prayer', 'Prayer wall', 'hands', 'prayer']];
    if ($isAdmin) {
        $site[] = ['studio/users', 'Users & roles', 'shield', 'users'];
        $site[] = ['studio/settings', 'Site settings', 'settings', 'settings'];
    }
    $nav[] = ['Community & site', $site];
}
?>
<?= $this->include('partials/html_open') ?>
<head>
    <?= $this->include('partials/head') ?>
</head>
<body>
    <?= $this->include('partials/flash') ?>
    <div class="drawer-backdrop"></div>
    <div class="studio">
        <aside class="studio-side" id="studio-side" data-drawer aria-label="Studio navigation">
            <a class="brand" href="<?= site_url('studio') ?>">
                <img src="<?= logo_url() ?>" alt="<?= esc(site_name()) ?> logo" width="34" height="34">
                <span>Studio</span>
            </a>
            <nav class="studio-nav">
                <?php foreach ($nav as [$label, $items]) : ?>
                    <?php if ($label !== '') : ?><div class="menu-label"><?= esc($label) ?></div><?php endif ?>
                    <?php foreach ($items as [$path, $text, $ic, $key]) : ?>
                        <a href="<?= site_url($path) ?>" class="<?= $active === $key ? 'is-active' : '' ?>"><?= icon($ic) ?><?= esc($text) ?></a>
                    <?php endforeach ?>
                <?php endforeach ?>
            </nav>
            <div style="padding:.75rem;border-top:1px solid var(--border)">
                <a class="btn btn-ghost btn-block" href="<?= site_url('/') ?>" style="justify-content:flex-start"><?= icon('arrow-left') ?>Back to site</a>
            </div>
        </aside>
        <div class="studio-main">
            <header class="studio-top">
                <button class="btn btn-ghost btn-icon studio-menu-btn" type="button" data-drawer-open="studio-side" aria-label="Open navigation"><?= icon('menu') ?></button>
                <div class="breadcrumbs mb-0 grow"><?= $this->renderSection('crumbs') ?></div>
                <button class="btn btn-ghost btn-icon" type="button" data-theme-toggle aria-label="Toggle dark mode"><?= icon('moon') ?></button>
                <div class="dropdown" data-dropdown>
                    <button class="btn btn-ghost" type="button" data-dropdown-toggle style="padding:.25rem .4rem"><?= avatar((int) $user->id, display_name(), 'avatar-sm') ?><?= icon('chevron-down', 'text-muted') ?></button>
                    <div class="dropdown-menu">
                        <div class="menu-head"><div class="fw-700"><?= esc(display_name()) ?></div><div class="text-xs text-muted"><?= esc(implode(', ', $user->getGroups())) ?></div></div>
                        <div class="menu-sep"></div>
                        <a class="menu-item" href="<?= site_url('dashboard') ?>"><?= icon('home') ?>My dashboard</a>
                        <a class="menu-item" href="<?= site_url('account') ?>"><?= icon('settings') ?>Account settings</a>
                        <a class="menu-item danger" href="<?= site_url('logout') ?>"><?= icon('log-out') ?>Sign out</a>
                    </div>
                </div>
            </header>
            <div class="studio-body">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>
    <style>@media (min-width: 961px) { .studio-menu-btn { display: none; } }</style>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
