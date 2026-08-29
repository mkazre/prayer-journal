<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Prayer Journal') ?></title>
    <link rel="icon" type="image/avif" href="<?= base_url('assets/img/logo.avif') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/logo.avif') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
    <header class="pj-header">
        <a class="pj-brand" href="<?= site_url('/') ?>">
            <img src="<?= base_url('assets/img/logo.avif') ?>" alt="Prayer Journal">
            <span>Prayer Journal</span>
        </a>
        <nav>
            <form method="get" action="<?= site_url('search') ?>" style="display:inline">
                <input type="text" name="q" placeholder="Search..." style="width:140px;padding:.35rem .6rem">
            </form>
            <a href="<?= site_url('courses') ?>">Courses</a>
            <a href="<?= site_url('bible') ?>">Bible</a>
            <a href="<?= site_url('shop') ?>">Shop</a>
            <a href="<?= site_url('pricing') ?>">Pricing</a>
            <a href="<?= site_url('prayer-requests') ?>">Prayer</a>
            <?php if (auth()->loggedIn()) : ?>
                <a href="<?= site_url('cart') ?>">Cart</a>
                <a href="<?= site_url('dashboard') ?>">Dashboard</a>
                <a href="<?= site_url('logout') ?>">Logout</a>
            <?php else : ?>
                <a href="<?= site_url('login') ?>">Login</a>
                <a href="<?= site_url('register') ?>">Register</a>
            <?php endif ?>
            <button id="pj-theme-toggle" class="pj-btn-outline pj-btn" type="button" title="Toggle dark mode" style="padding:.4rem .7rem">🌙</button>
        </nav>
    </header>

    <?php if ($announcement = site_announcement()) : ?>
        <div style="background:var(--pj-gold);color:var(--pj-black);text-align:center;padding:.5rem 1rem;font-weight:600">
            <?= esc($announcement) ?>
        </div>
    <?php endif ?>

    <?= $this->renderSection('hero') ?>

    <main class="pj-container">
        <?php if (session()->getFlashdata('message')) : ?>
            <div class="pj-flash"><?= esc(session()->getFlashdata('message')) ?></div>
        <?php endif ?>
        <?php if (session()->getFlashdata('error')) : ?>
            <div class="pj-flash pj-flash-error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif ?>

        <?= $this->renderSection('content') ?>
    </main>

    <footer class="pj-footer">
        &copy; <?= date('Y') ?> Prayer Journal
    </footer>

    <script>
        (function () {
            var pref = localStorage.getItem('pj-theme');
            if (pref === 'dark') { document.body.classList.add('pj-dark'); }
            document.getElementById('pj-theme-toggle').addEventListener('click', function () {
                document.body.classList.toggle('pj-dark');
                localStorage.setItem('pj-theme', document.body.classList.contains('pj-dark') ? 'dark' : 'light');
            });
        })();
    </script>
</body>
</html>
