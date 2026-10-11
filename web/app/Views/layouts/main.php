<?= $this->include('partials/html_open') ?>
<head>
    <?= $this->include('partials/head') ?>
</head>
<body class="<?= esc(trim(($bodyClass ?? '') . (! empty($protect) ? ' protect' : '')), 'attr') ?>"<?= ! empty($protect) ? ' oncopy="return false"' : '' ?>>
    <?php if (! empty($protect) && ! empty($watermark)) : ?><div class="protect-watermark" aria-hidden="true" data-text="<?= esc($watermark, 'attr') ?>"></div><?php endif ?>
    <a class="sr-only" href="#main">Skip to content</a>
    <?= $this->include('partials/header') ?>
    <?= $this->include('partials/flash') ?>
    <?= $this->renderSection('hero') ?>
    <main id="main">
        <?= $this->renderSection('content') ?>
    </main>
    <?= $this->include('partials/footer') ?>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
