<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Products</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $q = static fn (array $o) => site_url('studio/products') . '?' . http_build_query(array_filter(array_merge(['q' => $filters['q'], 'status' => $filters['status'], 'category' => $filters['category'], 'type' => $filters['type']], $o))); ?>
<div class="studio-head">
    <div><h1>Products</h1><p><?= number_format($page['total']) ?> products in your catalogue.</p></div>
    <div class="row"><a class="btn" href="<?= site_url('studio/inventory') ?>"><?= icon('boxes') ?>Inventory</a><a class="btn btn-primary" href="<?= site_url('studio/products/new') ?>"><?= icon('plus') ?>Add product</a></div>
</div>
<div class="toolbar">
    <div class="chips">
        <?php foreach (['' => 'All', 'published' => 'Active', 'draft' => 'Drafts'] as $k => $l) : ?><a class="chip <?= (string) $filters['status'] === $k ? 'is-active' : '' ?>" href="<?= $q(['status' => $k ?: null, 'page' => null]) ?>"><?= $l ?></a><?php endforeach ?>
    </div>
    <form method="get" class="row gap-sm">
        <?php if ($filters['status']) : ?><input type="hidden" name="status" value="<?= esc($filters['status'], 'attr') ?>"><?php endif ?>
        <select class="select input-sm" name="category" data-autosubmit style="width:auto"><option value="">All categories</option><?php foreach ($categories as $c) : ?><option value="<?= esc($c['slug'], 'attr') ?>" <?= $filters['category'] === $c['slug'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option><?php endforeach ?></select>
        <select class="select input-sm" name="type" data-autosubmit style="width:auto"><option value="">Any type</option><option value="physical" <?= $filters['type'] === 'physical' ? 'selected' : '' ?>>Physical</option><option value="digital" <?= $filters['type'] === 'digital' ? 'selected' : '' ?>>Digital</option></select>
        <div class="input-icon"><?= icon('search') ?><input class="input input-sm" type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Search products" style="width:220px"></div>
    </form>
</div>
<?php if ($page['items'] === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('package', 'icon-lg') ?></div><h3>No products found</h3><p>Add books, journals, gifts or digital downloads.</p><a class="btn btn-primary" href="<?= site_url('studio/products/new') ?>"><?= icon('plus') ?>Add product</a></div>
<?php else : ?>
<form method="post" action="<?= site_url('studio/products/bulk') ?>" id="bulk-form">
    <?= csrf_field() ?>
    <div class="row mb-1 gap-sm" id="bulk-bar" hidden>
        <span class="text-sm fw-600" id="bulk-count"></span>
        <select class="select input-sm" name="action" style="width:auto"><option value="publish">Set active</option><option value="draft">Set as draft</option><option value="feature">Feature</option><option value="unfeature">Unfeature</option><option value="delete">Delete</option></select>
        <button class="btn btn-sm" type="submit" data-confirm="Apply to the selected products?">Apply</button>
    </div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th style="width:36px"><input type="checkbox" id="check-all" aria-label="Select all"></th><th>Product</th><th>Status</th><th>Inventory</th><th>Type</th><th class="num">Price</th><th class="num">Sold</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($page['items'] as $p) : $tracked = (int) $p['track_inventory'] && $p['product_type'] !== 'digital'; ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" class="row-check" aria-label="Select"></td>
                    <td><a class="row row-nowrap" href="<?= site_url('studio/products/' . $p['id']) ?>" style="color:inherit"><span class="cart-thumb" style="width:44px"><?= $p['image'] ? '<img src="' . base_url($p['image']) . '" alt="">' : icon('package') ?></span><span><span class="fw-600"><?= esc($p['title']) ?></span><?= $p['is_featured'] ? ' <span class="badge badge-accent">Featured</span>' : '' ?><br><span class="text-xs text-muted"><?= esc((string) $p['category_name']) ?><?= (int) $p['variant_count'] > 1 ? ' · ' . (int) $p['variant_count'] . ' variants' : '' ?></span></span></a></td>
                    <td><span class="badge badge-dot <?= $p['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>"><?= $p['status'] === 'published' ? 'Active' : 'Draft' ?></span></td>
                    <td class="text-sm"><?= $tracked ? '<span class="' . ((int) $p['stock'] <= 0 ? 'text-danger' : ((int) $p['stock'] <= 5 ? 'text-accent' : '')) . '">' . (int) $p['stock'] . ' in stock</span>' : '<span class="text-muted">Not tracked</span>' ?></td>
                    <td class="text-sm"><?= $p['product_type'] === 'digital' ? icon('download') . ' Digital' : icon('package') . ' Physical' ?></td>
                    <td class="num"><?= money((int) $p['price_cents']) ?><?= (int) $p['max_price_cents'] > (int) $p['price_cents'] ? '–' . money((int) $p['max_price_cents']) : '' ?></td>
                    <td class="num"><?= (int) $p['units_sold'] ?></td>
                    <td class="text-right nowrap"><a class="btn btn-sm btn-ghost btn-icon" href="<?= site_url('shop/' . $p['slug']) ?>" target="_blank" rel="noopener" aria-label="View"><?= icon('external') ?></a><a class="btn btn-sm btn-ghost btn-icon" href="<?= site_url('studio/products/' . $p['id']) ?>" aria-label="Edit"><?= icon('edit') ?></a></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table></div>
</form>
<?php if ($page['pages'] > 1) : ?><nav class="pagination"><?php for ($i = 1; $i <= $page['pages']; $i++) : ?><a class="<?= $i === $page['page'] ? 'is-active' : '' ?>" href="<?= $q(['page' => $i]) ?>"><?= $i ?></a><?php endfor ?></nav><?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
    var all = document.getElementById('check-all'); if (!all) { return; }
    var boxes = document.querySelectorAll('.row-check'), bar = document.getElementById('bulk-bar');
    function upd() { var n = Array.prototype.filter.call(boxes, function (b) { return b.checked; }).length; bar.hidden = !n; document.getElementById('bulk-count').textContent = n + ' selected'; }
    all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); upd(); });
    boxes.forEach(function (b) { b.addEventListener('change', upd); });
})();
</script>
<?= $this->endSection() ?>
