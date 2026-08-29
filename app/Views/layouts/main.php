<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Prayer Journal') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
    <header class="pj-header">
        <a class="pj-brand" href="<?= site_url('/') ?>">
            <!-- Logo placeholder: drop the provided logo file at public/assets/img/logo.png and swap the line below -->
            <span>Prayer Journal</span>
        </a>
        <nav>
            <?php if (auth()->loggedIn()) : ?>
                <a href="<?= site_url('dashboard') ?>">Dashboard</a>
                <a href="<?= site_url('logout') ?>">Logout</a>
            <?php else : ?>
                <a href="<?= site_url('login') ?>">Login</a>
                <a href="<?= site_url('register') ?>">Register</a>
            <?php endif ?>
        </nav>
    </header>

    <main class="pj-container">
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="pj-footer">
        &copy; <?= date('Y') ?> Prayer Journal
    </footer>
</body>
</html>
