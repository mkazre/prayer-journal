<?php
$loggedIn = auth()->loggedIn();
$name     = $loggedIn ? display_name() : '';
$cartN    = cart_count();
$links    = [
    ['courses', 'Courses', 'graduation'],
    ['bible', 'Bible', 'book-open'],
    ['journal', 'Journal', 'notebook'],
    ['shop', 'Shop', 'bag'],
    ['prayer-requests', 'Prayer Wall', 'hands'],
];
// Blog archives switched on for the menus in Studio (e.g. Prayerthon).
foreach (\App\Modules\Blog\Models\BlogArchiveModel::menu() as $a) {
    $links[] = ['blog/' . $a['slug'], $a['title'], 'map'];
}
?>
<?php if ($announcement = site_announcement()) : ?>
    <div class="announcement no-print"><?= esc($announcement) ?></div>
<?php endif ?>
<header class="site-header no-print">
    <div class="container">
        <button class="btn btn-ghost btn-icon nav-toggle" type="button" data-drawer-open="site-drawer" aria-label="Open menu"><?= icon('menu') ?></button>
        <a class="brand" href="<?= site_url('/') ?>">
            <img src="<?= logo_url() ?>" alt="<?= esc(site_name()) ?> logo" width="36" height="36">
            <span class="hide-sm"><?= esc(site_name()) ?></span>
        </a>
        <nav class="nav-links" aria-label="Main">
            <?php foreach ($links as [$path, $label, $ic]) : ?>
                <a class="nav-link<?= nav_active($path) ?>" href="<?= site_url($path) ?>"><?= icon($ic) ?><?= esc($label) ?></a>
            <?php endforeach ?>
        </nav>
        <form class="nav-search input-icon" method="get" action="<?= site_url('search') ?>" role="search">
            <?= icon('search') ?>
            <input class="input" type="search" name="q" placeholder="Search courses, scripture, products…" value="<?= esc((string) service('request')->getGet('q')) ?>" aria-label="Search" data-search-focus>
        </form>
        <div class="nav-actions">
            <div class="dropdown" data-dropdown>
                <button class="btn btn-ghost btn-icon" type="button" data-dropdown-toggle aria-label="Appearance" aria-expanded="false"><?= icon('palette') ?></button>
                <div class="dropdown-menu" style="min-width:260px">
                    <div class="menu-label">Theme</div>
                    <div class="row gap-xs" style="padding:.25rem .5rem .5rem">
                        <button class="btn btn-sm btn-ghost grow" type="button" data-theme-choice="light"><?= icon('sun') ?>Light</button>
                        <button class="btn btn-sm btn-ghost grow" type="button" data-theme-choice="dark"><?= icon('moon') ?>Dark</button>
                        <button class="btn btn-sm btn-ghost grow" type="button" data-theme-choice="system"><?= icon('monitor') ?>Auto</button>
                    </div>
                    <div class="menu-label">Text size</div>
                    <div class="row gap-xs" style="padding:.25rem .5rem .5rem">
                        <?php foreach (['sm' => 'A', 'md' => 'A', 'lg' => 'A', 'xl' => 'A'] as $k => $l) : ?>
                            <button class="btn btn-sm btn-ghost grow" type="button" data-text-size="<?= $k ?>" style="font-size:<?= ['sm' => '.75rem', 'md' => '.9rem', 'lg' => '1.05rem', 'xl' => '1.2rem'][$k] ?>"><?= $l ?></button>
                        <?php endforeach ?>
                    </div>
                    <div class="menu-label">Reading font</div>
                    <div class="row gap-xs" style="padding:.25rem .5rem .5rem">
                        <button class="btn btn-sm btn-ghost grow" type="button" data-reading-font="serif" style="font-family:var(--font-read)">Serif</button>
                        <button class="btn btn-sm btn-ghost grow" type="button" data-reading-font="sans">Sans</button>
                    </div>
                </div>
            </div>
            <a class="btn btn-ghost btn-icon" href="<?= site_url('cart') ?>" aria-label="Cart (<?= $cartN ?> items)">
                <?= icon('bag') ?><?php if ($cartN > 0) : ?><span class="count-dot"><?= $cartN ?></span><?php endif ?>
            </a>
            <?php if ($loggedIn) : ?>
                <div class="dropdown" data-dropdown>
                    <button class="btn btn-ghost" type="button" data-dropdown-toggle aria-expanded="false" style="padding:.25rem .4rem">
                        <?= avatar((int) auth()->id(), $name, 'avatar-sm') ?><span class="hide-sm" style="max-width:110px;overflow:hidden;text-overflow:ellipsis"><?= esc($name) ?></span><?= icon('chevron-down', 'text-muted') ?>
                    </button>
                    <div class="dropdown-menu">
                        <div class="menu-head row row-nowrap">
                            <?= avatar((int) auth()->id(), $name) ?>
                            <div class="grow"><div class="fw-700 truncate"><?= esc($name) ?></div><div class="text-xs text-muted truncate"><?= esc(auth()->user()->email) ?></div></div>
                        </div>
                        <div class="menu-sep"></div>
                        <a class="menu-item" href="<?= site_url('dashboard') ?>"><?= icon('home') ?>Dashboard</a>
                        <a class="menu-item" href="<?= site_url('my-learning') ?>"><?= icon('graduation') ?>My learning</a>
                        <a class="menu-item" href="<?= site_url('journal') ?>"><?= icon('notebook') ?>Prayer journal</a>
                        <a class="menu-item" href="<?= site_url('bible/library') ?>"><?= icon('bookmark') ?>Bible library</a>
                        <a class="menu-item" href="<?= site_url('my-certificates') ?>"><?= icon('award') ?>Certificates</a>
                        <a class="menu-item" href="<?= site_url('my-orders') ?>"><?= icon('receipt') ?>Orders</a>
                        <a class="menu-item" href="<?= site_url('wishlist') ?>"><?= icon('heart') ?>Wishlist</a>
                        <a class="menu-item" href="<?= site_url('account') ?>"><?= icon('settings') ?>Account settings</a>
                        <?php if (is_staff()) : ?>
                            <div class="menu-sep"></div>
                            <a class="menu-item" href="<?= site_url('studio') ?>"><?= icon('layout') ?>Studio</a>
                        <?php endif ?>
                        <div class="menu-sep"></div>
                        <a class="menu-item danger" href="<?= site_url('logout') ?>"><?= icon('log-out') ?>Sign out</a>
                    </div>
                </div>
            <?php else : ?>
                <a class="btn btn-ghost hide-sm" href="<?= site_url('login') ?>">Sign in</a>
                <a class="btn btn-primary" href="<?= site_url('register') ?>">Get started</a>
            <?php endif ?>
        </div>
    </div>
</header>

<div class="drawer-backdrop no-print"></div>
<aside class="drawer no-print" id="site-drawer" data-drawer aria-label="Menu">
    <div class="drawer-head">
        <a class="brand" href="<?= site_url('/') ?>"><img src="<?= logo_url() ?>" alt="<?= esc(site_name()) ?> logo" width="36" height="36"><span><?= esc(site_name()) ?></span></a>
        <button class="btn btn-ghost btn-icon" type="button" data-drawer-close aria-label="Close menu"><?= icon('x') ?></button>
    </div>
    <div class="drawer-body">
        <form method="get" action="<?= site_url('search') ?>" class="input-icon mb-2" role="search">
            <?= icon('search') ?><input class="input" type="search" name="q" placeholder="Search…" aria-label="Search">
        </form>
        <a class="nav-link<?= nav_active('') ?>" href="<?= site_url('/') ?>"><?= icon('home') ?>Home</a>
        <?php foreach ($links as [$path, $label, $ic]) : ?>
            <a class="nav-link<?= nav_active($path) ?>" href="<?= site_url($path) ?>"><?= icon($ic) ?><?= esc($label) ?></a>
        <?php endforeach ?>
        <a class="nav-link" href="<?= site_url('pricing') ?>"><?= icon('sparkles') ?>Membership</a>
        <div class="menu-sep"></div>
        <?php if ($loggedIn) : ?>
            <a class="nav-link" href="<?= site_url('dashboard') ?>"><?= icon('layout') ?>Dashboard</a>
            <a class="nav-link" href="<?= site_url('my-learning') ?>"><?= icon('graduation') ?>My learning</a>
            <a class="nav-link" href="<?= site_url('account') ?>"><?= icon('settings') ?>Account settings</a>
            <?php if (is_staff()) : ?><a class="nav-link" href="<?= site_url('studio') ?>"><?= icon('sliders') ?>Studio</a><?php endif ?>
            <a class="nav-link" href="<?= site_url('logout') ?>"><?= icon('log-out') ?>Sign out</a>
        <?php else : ?>
            <a class="btn btn-primary btn-block mb-1" href="<?= site_url('register') ?>">Create a free account</a>
            <a class="btn btn-block" href="<?= site_url('login') ?>">Sign in</a>
        <?php endif ?>
    </div>
</aside>
