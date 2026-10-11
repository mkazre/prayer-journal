<?php
$quotes = [
    ['Call unto me, and I will answer thee, and shew thee great and mighty things, which thou knowest not.', 'Jeremiah 33:3'],
    ['But thou, when thou prayest, enter into thy closet, and when thou hast shut thy door, pray to thy Father which is in secret.', 'Matthew 6:6'],
    ['Be careful for nothing; but in every thing by prayer and supplication with thanksgiving let your requests be made known unto God.', 'Philippians 4:6'],
    ['Draw nigh to God, and he will draw nigh to you.', 'James 4:8'],
];
[$quote, $cite] = $quotes[(int) date('z') % count($quotes)];
?>
<?= $this->include('partials/html_open') ?>
<head>
    <?= $this->include('partials/head') ?>
    <?= $this->renderSection('pageStyles') ?>
</head>
<body>
    <?= $this->include('partials/flash') ?>
    <div class="auth-shell">
        <aside class="auth-art hero">
            <a class="brand" href="<?= site_url('/') ?>" style="position:relative;z-index:1">
                <img src="<?= logo_url() ?>" alt="<?= esc(site_name()) ?> logo" width="44" height="44"><span><?= esc(site_name()) ?></span>
            </a>
            <div style="position:relative;z-index:1">
                <?= icon('quote', 'icon-xl') ?>
                <blockquote class="mt-2">&ldquo;<?= esc($quote) ?>&rdquo;<cite><?= esc($cite) ?></cite></blockquote>
            </div>
            <div class="row gap-lg text-sm" style="position:relative;z-index:1;color:rgba(255,255,255,.75)">
                <span><?= icon('graduation') ?> Guided courses</span><span><?= icon('notebook') ?> Private journal</span><span><?= icon('book-open') ?> Whole Bible</span>
            </div>
        </aside>
        <main class="auth-form" id="main">
            <div class="auth-card">
                <a class="brand mb-4 show-sm" href="<?= site_url('/') ?>"><img src="<?= logo_url() ?>" alt="<?= esc(site_name()) ?> logo" width="40" height="40"><span><?= esc(site_name()) ?></span></a>
                <?= $this->renderSection('main') ?>
            </div>
        </main>
    </div>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
    <?= $this->renderSection('pageScripts') ?>
</body>
</html>
