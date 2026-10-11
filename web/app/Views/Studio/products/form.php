<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/products') ?>">Products</a><?= icon('chevron-right') ?><span><?= $product['id'] ? esc($product['title']) : 'New product' ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$p        = $product;
$v        = static fn ($k) => esc((string) old($k, $p[$k] ?? ''), 'attr');
$digital  = ($p['product_type'] ?? 'physical') === 'digital';
$variants = array_values(array_filter($p['variants'], static fn ($x) => (int) $x['is_active'] === 1));
$vJson    = array_map(static fn ($x) => ['id' => (int) $x['id'], 'o' => [$x['option1'], $x['option2'], $x['option3']], 'price' => number_format($x['price_cents'] / 100, 2, '.', ''),
    'compare' => $x['compare_at_cents'] ? number_format($x['compare_at_cents'] / 100, 2, '.', '') : '', 'sku' => $x['sku'], 'stock' => (int) $x['stock'], 'weight' => $x['weight_grams'], 'file' => (bool) $x['digital_file_path']], $variants);
?>
<form method="post" enctype="multipart/form-data" id="product-form">
    <?= csrf_field() ?>
    <div class="studio-head">
        <div><h1><?= $p['id'] ? esc($p['title']) : 'Add product' ?></h1><?php if ($p['id']) : ?><p><?= (int) $sales ?> sold · <a href="<?= site_url('shop/' . $p['slug']) ?>" target="_blank" rel="noopener">View in shop <?= icon('external') ?></a></p><?php endif ?></div>
        <div class="row">
            <?php if ($p['id']) : ?>
                <div class="dropdown" data-dropdown><button class="btn btn-icon" type="button" data-dropdown-toggle aria-label="More"><?= icon('more') ?></button>
                    <div class="dropdown-menu"><button class="menu-item" type="submit" form="dup-form"><?= icon('copy-plus') ?>Duplicate</button><button class="menu-item danger" type="submit" form="del-form"><?= icon('trash') ?>Delete</button></div></div>
            <?php endif ?>
            <button class="btn btn-primary" type="submit"><?= icon('save') ?>Save</button>
        </div>
    </div>
    <div class="split">
        <div class="stack stack-lg">
            <section class="card card-body">
                <div class="field"><label class="label" for="title">Title</label><input class="input input-lg" id="title" name="title" value="<?= $v('title') ?>" required maxlength="200" placeholder="e.g. Leather prayer journal"></div>
                <div class="field"><label class="label" for="short">Short description</label><input class="input" id="short" name="short_description" value="<?= $v('short_description') ?>" maxlength="500" placeholder="One or two lines shown near the price"></div>
                <div class="field"><label class="label">Description</label><textarea name="description" data-editor placeholder="Tell customers about this product…"><?= esc((string) old('description', $p['description'] ?? '')) ?></textarea></div>
            </section>

            <section class="card card-body">
                <div class="row between mb-1"><h3 class="mb-0"><?= icon('image') ?> Media</h3><span class="text-xs text-muted">Drag to reorder · first image is the cover</span></div>
                <div class="thumb-grid mb-2" data-sortable="images">
                    <?php foreach ($p['images'] as $img) : ?>
                        <div class="thumb" data-id="<?= $img['id'] ?>" draggable="true" data-drag-anywhere>
                            <img src="<?= base_url($img['path']) ?>" alt="">
                            <input type="hidden" name="image_order[]" value="<?= $img['id'] ?>">
                            <div class="thumb-actions"><button class="btn btn-xs btn-light" type="button" style="background:rgba(0,0,0,.55)" data-del-img="<?= site_url('studio/product-images/' . $img['id'] . '/delete') ?>" aria-label="Remove image"><?= icon('trash') ?></button></div>
                            <input class="input input-sm" name="image_alt[<?= $img['id'] ?>]" value="<?= esc((string) $img['alt'], 'attr') ?>" placeholder="Alt text" style="position:absolute;left:4px;right:4px;bottom:4px;font-size:.72rem;min-height:1.6rem;padding:.15rem .4rem">
                        </div>
                    <?php endforeach ?>
                </div>
                <input class="sr-only" type="file" id="images" name="images[]" accept="image/*" multiple>
                <div class="dropzone" data-for="images"><?= icon('upload') ?><span data-file-label data-default="Drop images or click to upload">Drop images or click to upload</span><span class="text-xs">JPG, PNG or WebP · up to 8 MB each</span></div>
            </section>

            <section class="card card-body">
                <h3><?= icon('layers') ?> Options &amp; variants</h3>
                <p class="help mb-2">Add options like Size or Colour (comma-separated values) and generate a variant for each combination. No options = a single variant.</p>
                <div id="options">
                    <?php for ($i = 0; $i < 3; $i++) : $o = $p['option_defs'][$i] ?? null; ?>
                        <div class="row row-nowrap gap-sm mb-1 option-def" <?= $o || $i === 0 ? '' : 'hidden' ?>>
                            <input class="input input-sm" name="options[<?= $i ?>][name]" value="<?= esc((string) ($o['name'] ?? ''), 'attr') ?>" placeholder="Option name (e.g. Size)" style="max-width:200px">
                            <input class="input input-sm grow" name="options[<?= $i ?>][values]" value="<?= esc(implode(', ', $o['values'] ?? []), 'attr') ?>" placeholder="Values, comma-separated (e.g. A5, A4)">
                        </div>
                    <?php endfor ?>
                </div>
                <div class="row gap-sm mb-2"><button class="btn btn-sm btn-ghost" type="button" id="add-option"><?= icon('plus') ?>Add another option</button><button class="btn btn-sm" type="button" id="gen-variants"><?= icon('zap') ?>Generate variants</button></div>
                <div class="table-wrap"><table class="table table-compact" id="variants-table">
                    <thead><tr><th>Variant</th><th>Price</th><th>Compare at</th><th>SKU</th><th class="phys">Stock</th><th class="phys">Weight (g)</th><th class="digi">File</th><th></th></tr></thead>
                    <tbody></tbody>
                </table></div>
                <p class="help mt-1">"Compare at" shows a sale price with the original struck through.</p>
            </section>

            <section class="card card-body">
                <h3><?= icon('globe') ?> Search engine listing</h3>
                <div class="field"><label class="label" for="slug">URL handle</label><div class="input-group"><span class="input-addon"><?= esc(site_url('shop/')) ?></span><input class="input" id="slug" name="slug" value="<?= $v('slug') ?>" data-slug-from="#title"></div></div>
                <div class="field"><label class="label" for="seo_title">Page title</label><input class="input" id="seo_title" name="seo_title" value="<?= $v('seo_title') ?>" maxlength="200" data-count="seo-t-count"><span class="help" id="seo-t-count"></span></div>
                <div class="field"><label class="label" for="seo_desc">Meta description</label><textarea class="textarea" id="seo_desc" name="seo_description" rows="2" maxlength="320" data-count="seo-d-count"><?= esc((string) old('seo_description', $p['seo_description'] ?? '')) ?></textarea><span class="help" id="seo-d-count"></span></div>
            </section>
        </div>

        <aside class="stack stack-lg sticky">
            <section class="card card-body stack">
                <div class="field"><span class="label">Status</span><div class="segmented" style="width:100%"><label class="grow"><input type="radio" name="status" value="published" <?= ($p['status'] ?? '') === 'published' ? 'checked' : '' ?>><span style="justify-content:center">Active</span></label><label class="grow"><input type="radio" name="status" value="draft" <?= ($p['status'] ?? 'draft') === 'draft' ? 'checked' : '' ?>><span style="justify-content:center">Draft</span></label></div></div>
                <div class="field"><span class="label">Product type</span><div class="segmented" style="width:100%"><label class="grow"><input type="radio" name="product_type" value="physical" <?= ! $digital ? 'checked' : '' ?>><span style="justify-content:center"><?= icon('package') ?>Physical</span></label><label class="grow"><input type="radio" name="product_type" value="digital" <?= $digital ? 'checked' : '' ?>><span style="justify-content:center"><?= icon('download') ?>Digital</span></label></div></div>
                <label class="switch phys-only"><input type="checkbox" name="track_inventory" value="1" <?= (int) ($p['track_inventory'] ?? 1) ? 'checked' : '' ?>><span class="track"></span>Track stock</label>
                <label class="switch phys-only"><input type="checkbox" name="allow_backorder" value="1" <?= ! empty($p['allow_backorder']) ? 'checked' : '' ?>><span class="track"></span>Sell when out of stock</label>
                <?php if (is_staff('superadmin', 'admin')) : ?><label class="switch"><input type="checkbox" name="is_featured" value="1" <?= ! empty($p['is_featured']) ? 'checked' : '' ?>><span class="track"></span>Featured</label><?php endif ?>
            </section>
            <section class="card card-body stack">
                <h3 class="mb-0">Organisation</h3>
                <div class="field"><label class="label" for="cat">Category</label><select class="select" id="cat" name="category_id"><option value="">None</option><?php foreach ($categories as $c) : ?><option value="<?= $c['id'] ?>" <?= (int) ($p['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option><?php endforeach ?></select></div>
                <?php if ($collections) : ?>
                    <div class="field"><span class="label">Collections</span><?php foreach ($collections as $c) : ?><label class="check"><input type="checkbox" name="collections[]" value="<?= $c['id'] ?>" <?= in_array((int) $c['id'], $inCollections, true) ? 'checked' : '' ?>><?= esc($c['title']) ?></label><?php endforeach ?></div>
                <?php endif ?>
                <div class="field"><label class="label" for="brand">Brand / author</label><input class="input" id="brand" name="brand" value="<?= $v('brand') ?>"></div>
                <div class="field"><label class="label" for="tags">Tags</label><input class="input" id="tags" name="tags" value="<?= $v('tags') ?>" placeholder="gift, journal"></div>
            </section>
            <button class="btn btn-primary btn-lg btn-block" type="submit"><?= icon('save') ?>Save product</button>
        </aside>
    </div>
</form>
<?php if ($p['id']) : ?>
    <form id="dup-form" method="post" action="<?= site_url('studio/products/' . $p['id'] . '/duplicate') ?>"><?= csrf_field() ?></form>
    <form id="del-form" method="post" action="<?= site_url('studio/products/' . $p['id'] . '/delete') ?>" data-confirm="Delete this product? Past orders keep their details."><?= csrf_field() ?></form>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var existing = <?= json_encode($vJson, JSON_UNESCAPED_UNICODE) ?>;
    var tbody = document.querySelector('#variants-table tbody');
    var form = document.getElementById('product-form');
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML.replace(/"/g, '&quot;'); }
    function isDigital() { return form.querySelector('input[name=product_type]:checked').value === 'digital'; }
    function applyType() {
        var dig = isDigital();
        document.querySelectorAll('#variants-table .phys').forEach(function (el) { el.hidden = dig; });
        document.querySelectorAll('#variants-table .digi').forEach(function (el) { el.hidden = !dig; });
        document.querySelectorAll('.phys-only').forEach(function (el) { el.hidden = dig; });
    }
    function row(v, i) {
        var label = v.o.filter(Boolean).join(' / ') || 'Default';
        var tr = document.createElement('tr');
        tr.innerHTML = '<td class="fw-600">' + esc(label) +
            '<input type="hidden" name="variants[' + i + '][id]" value="' + (v.id || '') + '">' +
            [0, 1, 2].map(function (k) { return '<input type="hidden" name="variants[' + i + '][option' + (k + 1) + ']" value="' + esc(v.o[k] || '') + '">'; }).join('') + '</td>' +
            '<td><input class="input input-sm" type="number" min="0" step="0.01" name="variants[' + i + '][price]" value="' + esc(v.price) + '" required style="width:110px"></td>' +
            '<td><input class="input input-sm" type="number" min="0" step="0.01" name="variants[' + i + '][compare_at]" value="' + esc(v.compare) + '" style="width:110px"></td>' +
            '<td><input class="input input-sm" name="variants[' + i + '][sku]" value="' + esc(v.sku) + '" style="width:130px"></td>' +
            '<td class="phys"><input class="input input-sm" type="number" name="variants[' + i + '][stock]" value="' + (v.stock == null ? '' : v.stock) + '" style="width:90px"></td>' +
            '<td class="phys"><input class="input input-sm" type="number" min="0" name="variants[' + i + '][weight]" value="' + esc(v.weight) + '" style="width:90px"></td>' +
            '<td class="digi"><input type="file" name="variant_file_' + i + '" style="max-width:190px;font-size:.8rem">' + (v.file ? '<div class="text-xs text-success">' + PJ.icon('check') + ' File uploaded</div>' : '') + '</td>' +
            '<td><button class="btn btn-ghost btn-icon btn-sm" type="button" data-del-variant aria-label="Remove variant">' + PJ.icon('x') + '</button></td>';
        return tr;
    }
    function draw(list) { tbody.innerHTML = ''; list.forEach(function (v, i) { tbody.appendChild(row(v, i)); }); applyType(); }
    function defs() {
        return Array.prototype.map.call(document.querySelectorAll('.option-def'), function (d) {
            var inputs = d.querySelectorAll('input');
            return { name: inputs[0].value.trim(), values: inputs[1].value.split(',').map(function (s) { return s.trim(); }).filter(Boolean) };
        }).filter(function (o) { return o.name && o.values.length; });
    }
    document.getElementById('gen-variants').addEventListener('click', function () {
        var ds = defs(), combos = [[]];
        ds.forEach(function (d) { var next = []; combos.forEach(function (c) { d.values.forEach(function (v) { next.push(c.concat([v])); }); }); combos = next; });
        var base = existing[0] || { price: '', compare: '', sku: '', stock: 0, weight: '' };
        var list = combos.map(function (c) {
            var match = existing.filter(function (e) { return [0, 1, 2].every(function (k) { return (e.o[k] || '') === (c[k] || ''); }); })[0];
            return match || { id: null, o: [c[0] || null, c[1] || null, c[2] || null], price: base.price, compare: base.compare, sku: '', stock: 0, weight: base.weight };
        });
        draw(list);
        PJ.toast(list.length + ' variant' + (list.length === 1 ? '' : 's') + ' ready — set prices and stock, then save', 'info');
    });
    document.getElementById('add-option').addEventListener('click', function () { var hid = document.querySelector('.option-def[hidden]'); if (hid) { hid.hidden = false; hid.querySelector('input').focus(); } });
    tbody.addEventListener('click', function (e) { if (e.target.closest('[data-del-variant]') && tbody.children.length > 1) { e.target.closest('tr').remove(); } });
    form.querySelectorAll('input[name=product_type]').forEach(function (r) { r.addEventListener('change', applyType); });
    draw(existing.length ? existing : [{ id: null, o: [null, null, null], price: '', compare: '', sku: '', stock: 0, weight: '' }]);

    document.querySelectorAll('[data-del-img]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!confirm('Remove this image?')) { return; }
            PJ.post(b.getAttribute('data-del-img'), {}).then(function () { b.closest('.thumb').remove(); PJ.toast('Image removed'); });
        });
    });
})();
</script>
<?= $this->endSection() ?>
