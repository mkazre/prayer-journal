<?= $this->extend('layouts/main') ?>
<?= $this->section('hero') ?>
<section class="hero blog-hero">
    <div class="container">
        <nav class="breadcrumbs"><a href="<?= site_url('blog') ?>">Blog</a><?= icon('chevron-right') ?><span><?= esc($archive['title']) ?></span></nav>
        <span class="eyebrow"><?= icon('map') ?>Series</span>
        <h1><?= esc($archive['title']) ?></h1>
        <?php if ($archive['description']) : ?><p class="lead"><?= esc($archive['description']) ?></p><?php endif ?>
        <?php if (! auth()->loggedIn()) : ?>
            <div class="row mt-2"><a class="btn btn-accent btn-lg" href="<?= site_url('register') ?>"><?= icon('sparkles') ?>Create a free account</a><a class="btn btn-light btn-lg" href="<?= site_url('login') ?>">Sign in</a></div>
        <?php endif ?>
    </div>
</section>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container">
    <?php if ($archive['status'] !== 'published') : ?><div class="alert alert-warning mb-2"><?= icon('eye-off') ?><div>This archive is a draft — only staff can see it.</div></div><?php endif ?>
    <?php if ($posts === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('feather', 'icon-lg') ?></div><h3>The first post is coming soon</h3><p>Check back shortly.</p></div>
    <?php else : ?>
        <div class="grid grid-3">
            <?php foreach ($posts as $p) : $n = $counts[(int) $p['id']] ?? 0; ?>
                <a class="card card-hover blog-card" href="<?= site_url('blog/' . $archive['slug'] . '/' . $p['slug']) ?>">
                    <div class="course-cover" style="<?= cover_gradient($p['title']) ?>"><?php if ($p['featured_image']) : ?><img src="<?= base_url($p['featured_image']) ?>" alt="" loading="lazy"><?php else : ?><div class="cover-art"><span><?= esc($p['title']) ?></span></div><?php endif ?></div>
                    <div class="card-body">
                        <div class="text-xs text-muted mb-1"><?= nice_date($p['published_at']) ?><?= $n ? ' · ' . icon('download') . ' ' . $n . ' resource' . ($n === 1 ? '' : 's') : '' ?></div>
                        <h3><?= esc($p['title']) ?></h3>
                        <?php if ($p['excerpt']) : ?><p class="text-2 clamp-3 mb-0"><?= esc($p['excerpt']) ?></p><?php endif ?>
                    </div>
                </a>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
