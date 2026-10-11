<?= $this->include('partials/html_open') ?>
<head>
    <?= $this->include('partials/head') ?>
</head>
<body class="<?= esc($bodyClass ?? '', 'attr') ?>">
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
