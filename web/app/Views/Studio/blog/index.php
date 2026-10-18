<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Blog</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$levels = \App\Modules\Blog\Services\BlogAccess::LEVELS;
$arch   = null;
foreach ($archives as $a) {
    if ((int) $a['id'] === $current) {
        $arch = $a;
    }
}
?>
<div class="studio-head">
    <div><h1>Blog</h1><p>Archives group posts into a series (such as Prayerthon). Each post can carry downloadable resources.</p></div>
    <div class="row"><a class="btn" href="<?= site_url('studio/blog/archives/new') ?>"><?= icon('plus') ?>New archive</a><?php if ($arch) : ?><a class="btn btn-primary" href="<?= site_url('studio/blog/archives/' . $arch['id'] . '/posts/new') ?>"><?= icon('plus') ?>New post</a><?php endif ?></div>
</div>

<?php if ($archives === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('file-text', 'icon-lg') ?></div><h3>No archives yet</h3><p>Create an archive first, then add posts to it.</p><a class="btn btn-primary" href="<?= site_url('studio/blog/archives/new') ?>"><?= icon('plus') ?>New archive</a></div>
<?php else : ?>
    <div class="toolbar">
        <div class="chips">
            <?php foreach ($archives as $a) : ?>
                <a class="chip <?= (int) $a['id'] === $current ? 'is-active' : '' ?>" href="<?= site_url('studio/blog?archive=' . $a['id']) ?>"><?= esc($a['title']) ?> <span class="text-xs">(<?= $counts[(int) $a['id']] ?? 0 ?>)</span></a>
            <?php endforeach ?>
        </div>
    </div>

    <?php if ($arch) : ?>
        <section class="card card-body mb-2">
            <div class="row between items-start">
                <div>
                    <h3 class="mb-1"><?= esc($arch['title']) ?>
                        <span class="badge badge-dot <?= $arch['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>"><?= $arch['status'] === 'published' ? 'Published' : 'Draft' ?></span>
                        <?php if ($arch['show_in_menu']) : ?><span class="badge"><?= icon('menu') ?>In menus</span><?php endif ?>
                    </h3>
                    <p class="text-sm text-muted mb-0"><?= icon('lock') ?> <?= esc($levels[$arch['access']] ?? $arch['access']) ?> · <a href="<?= site_url('blog/' . $arch['slug']) ?>" target="_blank" rel="noopener"><?= esc(site_url('blog/' . $arch['slug'])) ?> <?= icon('external') ?></a></p>
                </div>
                <a class="btn btn-sm" href="<?= site_url('studio/blog/archives/' . $arch['id']) ?>"><?= icon('edit') ?>Edit archive</a>
            </div>
        </section>

        <?php if ($posts === []) : ?>
            <div class="card empty"><div class="feature-icon"><?= icon('feather', 'icon-lg') ?></div><h3>No posts in <?= esc($arch['title']) ?> yet</h3><a class="btn btn-primary" href="<?= site_url('studio/blog/archives/' . $arch['id'] . '/posts/new') ?>"><?= icon('plus') ?>Write the first post</a></div>
        <?php else : ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Post</th><th>Status</th><th>Access</th><th class="num">Resources</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($posts as $p) : $live = $p['status'] === 'published'; $scheduled = $live && strtotime((string) $p['published_at']) > time(); ?>
                        <tr>
                            <td><a class="row row-nowrap" href="<?= site_url('studio/blog/posts/' . $p['id']) ?>" style="color:inherit"><span class="cart-thumb" style="width:56px;aspect-ratio:16/10"><?= $p['featured_image'] ? '<img src="' . base_url($p['featured_image']) . '" alt="">' : icon('image') ?></span><span class="fw-600"><?= esc($p['title']) ?></span></a></td>
                            <td><span class="badge badge-dot <?= $scheduled ? 'badge-info' : ($live ? 'badge-success' : 'badge-warning') ?>"><?= $scheduled ? 'Scheduled' : ($live ? 'Published' : 'Draft') ?></span></td>
                            <td class="text-sm"><?= $p['access'] === 'inherit' ? '<span class="text-muted">Same as archive</span>' : esc($p['access'] === 'customers' ? 'Customers only' : 'Signed-in members') ?></td>
                            <td class="num"><?= $resourceCounts[(int) $p['id']] ?? 0 ?></td>
                            <td class="text-sm nowrap"><?= $p['published_at'] ? nice_date($p['published_at']) : '—' ?></td>
                            <td class="text-right nowrap"><a class="btn btn-sm btn-ghost btn-icon" href="<?= site_url('blog/' . $arch['slug'] . '/' . $p['slug']) ?>" target="_blank" rel="noopener" aria-label="View"><?= icon('external') ?></a><a class="btn btn-sm btn-ghost btn-icon" href="<?= site_url('studio/blog/posts/' . $p['id']) ?>" aria-label="Edit"><?= icon('edit') ?></a></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table></div>
        <?php endif ?>
    <?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
