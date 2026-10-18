<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head-band">
    <div class="container page-head">
        <span class="eyebrow"><?= icon('file-text') ?>Blog</span>
        <h1 class="mt-1">Series &amp; resources</h1>
        <p>Guided series, reflections and free resources for your walk with God.</p>
    </div>
</div>
<div class="container">
    <?php if ($archives === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('feather', 'icon-lg') ?></div><h3>Nothing here yet</h3><p>New series are on their way.</p></div>
    <?php else : ?>
        <div class="grid grid-3">
            <?php foreach ($archives as $a) : ?>
                <a class="card card-hover blog-card" href="<?= site_url('blog/' . $a['slug']) ?>">
                    <div class="course-cover" style="<?= cover_gradient($a['title']) ?>"><?php if ($a['cover_path']) : ?><img src="<?= base_url($a['cover_path']) ?>" alt="" loading="lazy"><?php else : ?><div class="cover-art"><span><?= esc($a['title']) ?></span></div><?php endif ?></div>
                    <div class="card-body"><h3><?= esc($a['title']) ?></h3><?php if ($a['description']) : ?><p class="text-2 clamp-3 mb-0"><?= esc($a['description']) ?></p><?php endif ?></div>
                </a>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
