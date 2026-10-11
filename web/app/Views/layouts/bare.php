<?= $this->include('partials/html_open') ?>
<head>
    <?= $this->include('partials/head') ?>
</head>
<body class="<?= esc($bodyClass ?? '', 'attr') ?>">
    <?= $this->include('partials/header') ?>
    <?= $this->include('partials/flash') ?>
    <main id="main">
        <?= $this->renderSection('content') ?>
    </main>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
