<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $this->renderSection('title') ?> - Prayer Journal</title>
    <link rel="icon" type="image/avif" href="<?= base_url('assets/img/logo.avif') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <style>
        body.bg-light { background: var(--pj-bg) !important; font-family: var(--font-body); }
        .btn-primary { background: var(--pj-purple); border-color: var(--pj-purple); }
        .btn-primary:hover, .btn-primary:focus { background: var(--pj-purple-dark); border-color: var(--pj-purple-dark); }
        .card { border-radius: var(--pj-radius); border-color: var(--pj-border); box-shadow: var(--pj-shadow); }
        .card-title { font-family: var(--font-display); color: var(--pj-purple-dark); }
        a { color: var(--pj-purple); }
        .form-control:focus { border-color: var(--pj-purple-light); box-shadow: 0 0 0 3px rgba(124,77,189,.15); }
        .pj-auth-header { text-align: center; padding: 2rem 0 0; }
        .pj-auth-header img { height: 56px; }
    </style>
    <?= $this->renderSection('pageStyles') ?>
</head>

<body class="bg-light">
    <div class="pj-auth-header">
        <a href="<?= site_url('/') ?>"><img src="<?= base_url('assets/img/logo.avif') ?>" alt="Prayer Journal"></a>
    </div>

    <main role="main" class="container">
        <?= $this->renderSection('main') ?>
    </main>

<?= $this->renderSection('pageScripts') ?>
</body>
</html>
