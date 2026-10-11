<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/products') ?>">Products</a><?= icon('chevron-right') ?><span>Collections &amp; categories</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$close = '<button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close">' . icon('x') . '</button>';
$collectionModal = static function (array $c) use ($close): string {
    $id = $c['id'] ?? 0;
    ob_start(); ?>
    <dialog class="modal" id="collection-<?= $id ?>">
        <form method="post" action="<?= site_url('studio/collections') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <div class="modal-head"><h3><?= $id ? 'Edit collection' : 'New collection' ?></h3><?= $close ?></div>
            <div class="modal-body">
                <div class="field"><label class="label">Title</label><input class="input" name="title" value="<?= esc($c['title'] ?? '', 'attr') ?>" required maxlength="150" placeholder="e.g. Gifts for new believers"></div>
                <div class="field"><label class="label">URL handle <span class="opt">(optional)</span></label><input class="input" name="slug" value="<?= esc($c['slug'] ?? '', 'attr') ?>"></div>
                <div class="field"><label class="label">Description</label><textarea class="textarea" name="description" rows="3"><?= esc((string) ($c['description'] ?? '')) ?></textarea></div>
                <div class="grid grid-2">
                    <div class="field"><label class="label">Banner image</label><input class="input" type="file" name="image" accept="image/*"></div>
                    <div class="field"><label class="label">Sort order</label><input class="input" type="number" name="position" value="<?= (int) ($c['position'] ?? 0) ?>"></div>
                </div>
                <label class="switch"><input type="checkbox" name="is_featured" value="1" <?= ! empty($c['is_featured']) ? 'checked' : '' ?>><span class="track"></span>Feature on the shop home</label>
            </div>
            <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save collection</button></div>
        </form>
    </dialog>
    <?php return (string) ob_get_clean();
};
$categoryModal = static function (array $c) use ($close, $categories): string {
    $id = $c['id'] ?? 0;
    ob_start(); ?>
    <dialog class="modal" id="category-<?= $id ?>">
        <form method="post" action="<?= site_url('studio/categories') ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <div class="modal-head"><h3><?= $id ? 'Edit category' : 'New category' ?></h3><?= $close ?></div>
            <div class="modal-body">
                <div class="field"><label class="label">Name</label><input class="input" name="name" value="<?= esc($c['name'] ?? '', 'attr') ?>" required maxlength="100"></div>
                <div class="grid grid-2">
                    <div class="field"><label class="label">Parent</label><select class="select" name="parent_id"><option value="">None (top level)</option><?php foreach ($categories as $p) : if ((int) $p['id'] === (int) $id) { continue; } ?><option value="<?= $p['id'] ?>" <?= (int) ($c['parent_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option><?php endforeach ?></select></div>
                    <div class="field"><label class="label">Sort order</label><input class="input" type="number" name="position" value="<?= (int) ($c['position'] ?? 0) ?>"></div>
                </div>
                <div class="field"><label class="label">URL handle <span class="opt">(optional)</span></label><input class="input" name="slug" value="<?= esc($c['slug'] ?? '', 'attr') ?>"></div>
                <div class="field"><label class="label">Description</label><textarea class="textarea" name="description" rows="2"><?= esc((string) ($c['description'] ?? '')) ?></textarea></div>
            </div>
            <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save category</button></div>
        </form>
    </dialog>
    <?php return (string) ob_get_clean();
};
$parents = array_column($categories, 'name', 'id');
?>
<div class="studio-head">
    <div><h1>Collections &amp; categories</h1><p>Collections are hand-picked sets for merchandising. Categories organise your whole catalogue.</p></div>
</div>

<section class="mb-4">
    <div class="row between mb-2"><h2 class="h-sm"><?= icon('layers') ?> Collections</h2><button class="btn btn-primary btn-sm" type="button" data-modal-open="collection-0"><?= icon('plus') ?>New collection</button></div>
    <?php if ($collections === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('layers', 'icon-lg') ?></div><h3>No collections yet</h3><p>Group products into sets like “Journals” or “Christmas gifts”.</p></div>
    <?php else : ?>
    <div class="grid grid-3">
        <?php foreach ($collections as $c) : ?>
            <article class="card">
                <div style="aspect-ratio:16/7;background:<?= $c['image_path'] ? 'center/cover url(' . esc(base_url($c['image_path']), 'attr') . ')' : 'var(--brand-soft)' ?>;border-radius:var(--radius) var(--radius) 0 0;display:grid;place-items:center;color:var(--brand)"><?= $c['image_path'] ? '' : icon('layers', 'icon-lg') ?></div>
                <div class="card-body-sm">
                    <div class="row between"><strong><?= esc($c['title']) ?></strong><?= $c['is_featured'] ? '<span class="badge badge-accent">Featured</span>' : '' ?></div>
                    <p class="text-sm text-muted mb-1"><?= (int) $c['product_count'] ?> product<?= (int) $c['product_count'] === 1 ? '' : 's' ?> · /shop?collection=<?= esc($c['slug']) ?></p>
                    <div class="row gap-xs">
                        <button class="btn btn-sm" type="button" data-modal-open="collection-<?= $c['id'] ?>"><?= icon('edit') ?>Edit</button>
                        <a class="btn btn-sm btn-ghost" href="<?= site_url('shop?collection=' . $c['slug']) ?>" target="_blank" rel="noopener"><?= icon('external') ?>View</a>
                        <form method="post" action="<?= site_url('studio/collections/' . $c['id'] . '/delete') ?>" class="ml-auto"><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" data-confirm="Delete this collection? Products are kept." aria-label="Delete"><?= icon('trash') ?></button></form>
                    </div>
                </div>
            </article>
            <?= $collectionModal($c) ?>
        <?php endforeach ?>
    </div>
    <?php endif ?>
    <p class="text-sm text-muted mt-2"><?= icon('info') ?> Add products to collections from each product’s <em>Organisation</em> panel.</p>
</section>

<section id="categories">
    <div class="row between mb-2"><h2 class="h-sm"><?= icon('tag') ?> Categories</h2><button class="btn btn-primary btn-sm" type="button" data-modal-open="category-0"><?= icon('plus') ?>New category</button></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Name</th><th>Parent</th><th>Handle</th><th class="num">Products</th><th></th></tr></thead>
        <tbody>
            <?php if ($categories === []) : ?><tr><td colspan="5" class="text-muted">No categories yet.</td></tr><?php endif ?>
            <?php foreach ($categories as $c) : ?>
                <tr>
                    <td class="fw-600"><?= esc($c['name']) ?><?= $c['description'] ? '<div class="text-xs text-muted">' . esc(excerpt($c['description'], 80)) . '</div>' : '' ?></td>
                    <td class="text-sm"><?= esc($parents[$c['parent_id']] ?? '—') ?></td>
                    <td class="text-sm text-muted"><?= esc($c['slug']) ?></td>
                    <td class="num"><?= (int) $c['product_count'] ?></td>
                    <td class="text-right nowrap">
                        <button class="btn btn-sm btn-ghost btn-icon" type="button" data-modal-open="category-<?= $c['id'] ?>" aria-label="Edit"><?= icon('edit') ?></button>
                        <form method="post" action="<?= site_url('studio/categories/' . $c['id'] . '/delete') ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" data-confirm="Delete this category? Its products become uncategorised." aria-label="Delete"><?= icon('trash') ?></button></form>
                        <?= $categoryModal($c) ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table></div>
</section>
<?= $collectionModal([]) ?>
<?= $categoryModal([]) ?>
<?= $this->endSection() ?>
