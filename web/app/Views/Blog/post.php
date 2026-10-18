<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$kindIcon  = ['pdf' => 'file-text', 'image' => 'image', 'video' => 'video', 'audio' => 'headphones', 'file' => 'paperclip'];
$kindLabel = ['pdf' => 'PDF', 'image' => 'Image', 'video' => 'Video', 'audio' => 'Audio', 'file' => 'File'];
$url       = static fn (array $r, bool $dl = false) => site_url('blog/resources/' . $r['id']) . ($dl ? '?download=1' : '');
?>
<article class="container" style="max-width:860px">
    <nav class="breadcrumbs"><a href="<?= site_url('blog') ?>">Blog</a><?= icon('chevron-right') ?><a href="<?= site_url('blog/' . $archive['slug']) ?>"><?= esc($archive['title']) ?></a><?= icon('chevron-right') ?><span><?= esc($post['title']) ?></span></nav>
    <?php if ($post['status'] !== 'published' || strtotime((string) $post['published_at']) > time()) : ?><div class="alert alert-warning mb-2"><?= icon('eye-off') ?><div>Not live yet — only staff can see this post.</div></div><?php endif ?>
    <header class="mb-3">
        <span class="eyebrow"><?= icon('map') ?><?= esc($archive['title']) ?></span>
        <h1 class="mt-1"><?= esc($post['title']) ?></h1>
        <div class="text-sm text-muted"><?= $post['published_at'] ? nice_date($post['published_at']) : '' ?></div>
    </header>
    <?php if ($post['featured_image']) : ?><img class="blog-feature" src="<?= base_url($post['featured_image']) ?>" alt=""><?php endif ?>
    <?php if ($post['excerpt']) : ?><p class="lead text-2"><?= esc($post['excerpt']) ?></p><?php endif ?>

    <?php if ($canRead) : ?>
        <div class="prose" style="max-width:none"><?= scripture_links(clean_html((string) $post['body'])) ?></div>

        <?php if ($resources) : ?>
            <section class="mt-3">
                <h2 style="font-size:1.4rem"><?= icon('gift') ?> Free resources</h2>
                <div class="stack">
                    <?php foreach ($resources as $r) : ?>
                        <div class="card card-body resource">
                            <div class="row row-nowrap items-start gap-lg">
                                <span class="feature-icon <?= $r['kind'] === 'pdf' || $r['kind'] === 'audio' ? 'gold' : '' ?>" style="margin:0"><?= icon($kindIcon[$r['kind']] ?? 'file') ?></span>
                                <div class="grow">
                                    <div class="row between"><strong><?= esc($r['title']) ?></strong><span class="text-xs text-muted"><?= $kindLabel[$r['kind']] ?? 'File' ?> · <?= number_format($r['size_bytes'] / 1048576, 1) ?> MB</span></div>
                                    <?php if ($r['kind'] === 'video') : ?>
                                        <video class="mt-1" controls preload="metadata" playsinline style="width:100%;border-radius:var(--radius-sm)" src="<?= $url($r) ?>"></video>
                                    <?php elseif ($r['kind'] === 'audio') : ?>
                                        <audio class="mt-1" controls preload="metadata" style="width:100%" src="<?= $url($r) ?>"></audio>
                                    <?php elseif ($r['kind'] === 'image') : ?>
                                        <img class="mt-1" src="<?= $url($r) ?>" alt="<?= esc($r['title'], 'attr') ?>" loading="lazy" style="max-width:100%;border-radius:var(--radius-sm)">
                                    <?php endif ?>
                                    <div class="row gap-sm mt-1">
                                        <a class="btn btn-sm btn-primary" href="<?= $url($r, true) ?>"><?= icon('download') ?>Download</a>
                                        <?php if ($r['kind'] === 'pdf') : ?><a class="btn btn-sm" href="<?= $url($r) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Open</a><?php endif ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endif ?>
    <?php else : ?>
        <div class="card card-body locked-panel text-center">
            <div class="feature-icon gold" style="margin:0 auto 1rem"><?= icon('lock', 'icon-lg') ?></div>
            <?php if (! auth()->loggedIn()) : ?>
                <h3>Sign in to read the rest<?= $resources ? ' and download the free resources' : '' ?></h3>
                <p class="text-2"><?= $level === 'customers' ? 'This post is free for our customers — anyone who has bought a product, a course or a membership.' : 'It\'s free — you just need an account.' ?></p>
                <div class="row center"><a class="btn btn-primary btn-lg" href="<?= site_url('register') ?>"><?= icon('sparkles') ?>Create a free account</a><a class="btn btn-lg" href="<?= site_url('login') ?>">Sign in</a></div>
            <?php else : ?>
                <h3>This post is free for our customers</h3>
                <p class="text-2">Buy any product, a course or a membership and you'll unlock it<?= $resources ? ' — with all ' . count($resources) . ' resource' . (count($resources) === 1 ? '' : 's') : '' ?>.</p>
                <div class="row center"><a class="btn btn-primary btn-lg" href="<?= site_url('pricing') ?>"><?= icon('sparkles') ?>See membership</a><a class="btn btn-lg" href="<?= site_url('shop') ?>"><?= icon('bag') ?>Visit the shop</a></div>
            <?php endif ?>
            <?php if ($resources) : ?>
                <ul class="locked-list mt-2">
                    <?php foreach ($resources as $r) : ?><li><?= icon($kindIcon[$r['kind']] ?? 'file') ?><?= esc($r['title']) ?> <span class="text-xs text-muted"><?= $kindLabel[$r['kind']] ?? 'File' ?></span></li><?php endforeach ?>
                </ul>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php if ($others) : ?>
        <section class="mt-4">
            <div class="row between mb-1"><h2 style="font-size:1.3rem" class="mb-0">More from <?= esc($archive['title']) ?></h2><a href="<?= site_url('blog/' . $archive['slug']) ?>">See all <?= icon('arrow-right') ?></a></div>
            <div class="grid grid-3">
                <?php foreach ($others as $o) : ?>
                    <a class="card card-hover blog-card" href="<?= site_url('blog/' . $archive['slug'] . '/' . $o['slug']) ?>">
                        <div class="course-cover" style="<?= cover_gradient($o['title']) ?>"><?php if ($o['featured_image']) : ?><img src="<?= base_url($o['featured_image']) ?>" alt="" loading="lazy"><?php else : ?><div class="cover-art"><span><?= esc($o['title']) ?></span></div><?php endif ?></div>
                        <div class="card-body"><h3 style="font-size:1.05rem" class="mb-0"><?= esc($o['title']) ?></h3></div>
                    </a>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>
</article>
<?= $this->endSection() ?>
