<?= $this->include('partials/html_open') ?>
<head>
    <?= $this->include('partials/head') ?>
</head>
<body class="<?= esc(trim(($bodyClass ?? '') . (! empty($protect) ? ' protect' : '')), 'attr') ?>"<?= ! empty($protect) ? ' oncopy="return false"' : '' ?>>
    <?php if (! empty($protect) && ! empty($watermark)) : ?><div class="protect-watermark" aria-hidden="true" data-text="<?= esc($watermark, 'attr') ?>"></div><?php endif ?>
    <?= $this->include('partials/header') ?>
    <?= $this->include('partials/flash') ?>
    <main id="main">
        <?= $this->renderSection('content') ?>
    </main>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
