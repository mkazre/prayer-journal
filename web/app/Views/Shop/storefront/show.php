<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Models\ProductModel;

$p        = $product;
$variants = array_values(array_filter($p['variants'], static fn ($v) => (int) $v['is_active'] === 1));
$first    = null;
foreach ($variants as $v) {
    if (ProductModel::isPurchasable($p, $v)) { $first = $v; break; }
}
$first ??= $variants[0] ?? null;
$digital  = $p['product_type'] === 'digital';
$vJson    = array_map(static fn ($v) => [
    'id' => (int) $v['id'], 'o' => [$v['option1'], $v['option2'], $v['option3']], 'price' => money((int) $v['price_cents']),
    'compare' => ! empty($v['compare_at_cents']) && (int) $v['compare_at_cents'] > (int) $v['price_cents'] ? money((int) $v['compare_at_cents']) : null,
    'save' => ! empty($v['compare_at_cents']) && (int) $v['compare_at_cents'] > (int) $v['price_cents'] ? (int) round((1 - $v['price_cents'] / $v['compare_at_cents']) * 100) : 0,
    'stock' => (int) $v['stock'], 'ok' => ProductModel::isPurchasable($p, $v), 'sku' => $v['sku'], 'image' => $v['image_path'] ? base_url($v['image_path']) : null,
], $variants);
$tracked = (int) $p['track_inventory'] && ! $digital;
?>
<div class="container mt-3">
    <nav class="breadcrumbs"><a href="<?= site_url('shop') ?>">Shop</a><?= icon('chevron-right') ?><span><?= esc($p['title']) ?></span></nav>
    <?php if ($p['status'] !== 'published') : ?><div class="alert alert-warning mb-2"><?= icon('eye-off') ?><div>This product is a <strong>draft</strong> — only staff can see it. <a href="<?= site_url('studio/products/' . $p['id']) ?>">Edit in Studio</a></div></div><?php endif ?>

    <div class="grid grid-2 gap-xl" style="align-items:start">
        <div class="gallery">
            <div class="gallery-thumbs">
                <?php foreach ($p['images'] as $i => $img) : ?>
                    <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-src="<?= base_url($img['path']) ?>" aria-label="Image <?= $i + 1 ?>"><img src="<?= base_url($img['path']) ?>" alt="<?= esc((string) $img['alt'], 'attr') ?>" loading="lazy"></button>
                <?php endforeach ?>
            </div>
            <div class="gallery-main" id="gallery-main">
                <?php if ($p['images']) : ?>
                    <img id="main-image" src="<?= base_url($p['images'][0]['path']) ?>" alt="<?= esc($p['images'][0]['alt'] ?: $p['title'], 'attr') ?>">
                <?php else : ?>
                    <div class="placeholder" style="position:absolute;inset:0;display:grid;place-items:center;color:var(--brand-500)"><?= icon('book', 'icon-xl') ?></div>
                <?php endif ?>
            </div>
        </div>

        <div class="sticky">
            <?php if ($p['brand']) : ?><span class="eyebrow"><?= esc($p['brand']) ?></span><?php endif ?>
            <h1 class="mt-1" style="font-size:clamp(1.7rem,2.5vw,2.3rem)"><?= esc($p['title']) ?></h1>
            <?php if ($avgRating) : ?><a class="row gap-sm text-sm mb-1" href="#reviews" style="color:var(--text-2)"><?= stars($avgRating) ?> <?= number_format($avgRating, 1) ?> · <?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?></a><?php endif ?>
            <div class="row gap-sm mt-2" style="align-items:baseline">
                <span class="price" style="font-size:1.9rem" id="price"><?= $first ? money((int) $first['price_cents']) : '—' ?></span>
                <span class="price-compare" id="compare" style="font-size:1.1rem" hidden></span>
                <span class="badge badge-danger" id="save" hidden></span>
            </div>
            <p class="text-xs text-muted"><?= \App\Modules\Shop\Services\Pricing::pricesIncludeTax() ? 'Price includes ' . esc(site_setting('tax_label', 'VAT')) . '.' : 'Tax calculated at checkout.' ?><?= $digital ? '' : ' Shipping calculated at checkout.' ?></p>
            <?php if ($p['short_description']) : ?><p class="text-2 text-lg"><?= esc($p['short_description']) ?></p><?php endif ?>

            <form method="post" id="buy-form" action="<?= $first ? site_url('cart/add/' . $first['id']) : '#' ?>" data-ajax class="stack mt-3">
                <?= csrf_field() ?>
                <?php foreach ($p['option_defs'] as $oi => $opt) : ?>
                    <div class="option-group">
                        <span class="label"><?= esc($opt['name']) ?>: <span class="fw-500 text-2" data-opt-label="<?= $oi ?>"><?= esc((string) ($first['option' . ($oi + 1)] ?? '')) ?></span></span>
                        <div class="option-values">
                            <?php foreach ($opt['values'] as $val) : ?>
                                <label><input type="radio" name="opt<?= $oi ?>" value="<?= esc($val, 'attr') ?>" data-opt="<?= $oi ?>" <?= ($first['option' . ($oi + 1)] ?? null) === $val ? 'checked' : '' ?>><span><?= esc($val) ?></span></label>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endforeach ?>
                <div class="row">
                    <?php if (! $digital) : ?>
                        <div class="qty" data-qty><button type="button" data-step="-" aria-label="Decrease"><?= icon('minus') ?></button><input type="number" name="quantity" value="1" min="1" max="99" aria-label="Quantity"><button type="button" data-step="+" aria-label="Increase"><?= icon('plus') ?></button></div>
                    <?php else : ?><input type="hidden" name="quantity" value="1"><?php endif ?>
                    <button class="btn btn-primary btn-lg grow" type="submit" id="add-btn"><?= icon('bag') ?>Add to cart</button>
                    <?php if (auth()->loggedIn()) : ?>
                        <button class="btn btn-lg btn-icon" type="submit" form="wish-form" aria-label="Save to wishlist" title="Wishlist" style="<?= $wishlisted ? 'color:#e0245e' : '' ?>"><?= icon('heart', $wishlisted ? 'icon-fill' : '') ?></button>
                    <?php endif ?>
                </div>
                <button class="btn btn-accent btn-lg btn-block" type="submit" name="buy_now" value="1" id="buy-now" formnovalidate onclick="this.form.removeAttribute('data-ajax')">Buy it now</button>
                <div class="row between">
                    <span class="stock-note" id="stock-note"></span>
                    <span class="text-xs text-muted" id="sku"><?= $first && $first['sku'] ? 'SKU ' . esc($first['sku']) : '' ?></span>
                </div>
            </form>
            <?php if (auth()->loggedIn()) : ?><form id="wish-form" method="post" action="<?= site_url('wishlist/toggle/' . $p['id']) ?>" data-ajax data-ajax-reload><?= csrf_field() ?></form><?php endif ?>

            <div class="trust-row">
                <?php $flipOnly = $digital && (int) ($p['flipbook'] ?? 0) && ! (int) ($p['allow_download'] ?? 1); $flipAlso = $digital && (int) ($p['flipbook'] ?? 0) && ! $flipOnly; ?>
                <div><?= icon($digital ? ($flipOnly ? 'book-open' : 'download') : 'truck') ?><?= $digital ? ($flipOnly ? 'Read online instantly after payment' : ($flipAlso ? 'Read online or download after payment' : 'Instant download after payment')) : esc(site_setting('shipping_promise', 'Tracked delivery')) ?></div>
                <div><?= icon('shield-check') ?>Secure checkout</div>
                <div><?= icon('refresh') ?><?= esc(site_setting('returns_promise', $digital ? ($flipOnly ? 'Read any time from your orders' : 'Re-download any time') : '30-day returns')) ?></div>
            </div>
            <div class="row gap-sm mt-2">
                <button class="btn btn-sm btn-ghost" type="button" data-share="<?= esc($p['title'], 'attr') ?>" data-share-title="<?= esc($p['title'], 'attr') ?>"><?= icon('share') ?>Share</button>
                <?php if (is_staff('superadmin', 'admin', 'vendor')) : ?><a class="btn btn-sm btn-ghost" href="<?= site_url('studio/products/' . $p['id']) ?>"><?= icon('edit') ?>Edit</a><?php endif ?>
            </div>
        </div>
    </div>

    <section class="mt-4">
        <div class="tabs" role="tablist">
            <button class="tab is-active" data-tab="tab-desc" role="tab">Description</button>
            <button class="tab" data-tab="tab-details" role="tab">Details</button>
            <button class="tab" data-tab="reviews" role="tab">Reviews <span class="badge"><?= count($reviews) ?></span></button>
        </div>
        <div class="tab-panel is-active" id="tab-desc"><div class="prose" style="max-width:760px;font-size:1.02rem"><?= $p['description'] ? scripture_links(clean_html($p['description'])) : '<p class="text-muted">No description yet.</p>' ?></div></div>
        <div class="tab-panel" id="tab-details">
            <div class="table-wrap" style="max-width:640px"><table class="table">
                <tr><th style="width:40%">Format</th><td><?= $digital ? ($flipOnly ? 'Read online (flipbook)' : ($flipAlso ? 'Read online (flipbook) + PDF download' : 'Digital download')) : 'Physical product' ?></td></tr>
                <?php if ($p['brand']) : ?><tr><th>Brand / author</th><td><?= esc($p['brand']) ?></td></tr><?php endif ?>
                <?php if (! $digital && $first && $first['weight_grams']) : ?><tr><th>Weight</th><td><?= (int) $first['weight_grams'] ?> g</td></tr><?php endif ?>
                <?php if ($p['tags']) : ?><tr><th>Tags</th><td><div class="chips"><?php foreach (array_filter(array_map('trim', explode(',', $p['tags']))) as $t) : ?><a class="chip" style="padding:.15rem .6rem;font-size:.78rem" href="<?= site_url('shop?q=' . urlencode($t)) ?>"><?= esc($t) ?></a><?php endforeach ?></div></td></tr><?php endif ?>
                <tr><th>Options</th><td><?= count($variants) ?> variant<?= count($variants) === 1 ? '' : 's' ?></td></tr>
            </table></div>
        </div>
        <div class="tab-panel" id="reviews">
            <div class="grid grid-2 gap-xl" style="align-items:start">
                <div class="card card-body">
                    <div class="row gap-xl">
                        <div class="text-center"><div class="result-score" style="font-size:3rem"><?= $avgRating ? number_format($avgRating, 1) : '—' ?></div><?= stars($avgRating ?? 0) ?><div class="text-sm text-muted"><?= count($reviews) ?> reviews</div></div>
                        <div class="grow stack stack-sm"><?php for ($s = 5; $s >= 1; $s--) : $pct = $reviews ? round($breakdown[$s] / count($reviews) * 100) : 0; ?><div class="rating-bar"><span><?= $s ?> star</span><div class="progress"><span style="width:<?= $pct ?>%"></span></div><span class="text-right"><?= $pct ?>%</span></div><?php endfor ?></div>
                    </div>
                    <hr>
                    <?php if (auth()->loggedIn()) : ?>
                        <form method="post" action="<?= site_url('shop/' . $p['slug'] . '/review') ?>">
                            <?= csrf_field() ?>
                            <div class="field"><span class="label"><?= $myReview ? 'Update your review' : 'Write a review' ?></span><div class="rating-input"><?php for ($s = 5; $s >= 1; $s--) : ?><input type="radio" id="pr<?= $s ?>" name="rating" value="<?= $s ?>" <?= (int) ($myReview['rating'] ?? 0) === $s ? 'checked' : '' ?> required><label for="pr<?= $s ?>" title="<?= $s ?> stars"><?= icon('star', 'icon-fill') ?></label><?php endfor ?></div></div>
                            <div class="field"><input class="input" name="title" maxlength="150" placeholder="Headline" value="<?= esc((string) ($myReview['title'] ?? ''), 'attr') ?>"></div>
                            <div class="field"><textarea class="textarea" name="body" rows="3" placeholder="What did you think?"><?= esc((string) ($myReview['body'] ?? '')) ?></textarea></div>
                            <button class="btn btn-primary" type="submit"><?= icon('send') ?>Submit review</button>
                        </form>
                    <?php else : ?>
                        <p class="text-sm text-muted mb-0"><a href="<?= site_url('login') ?>">Sign in</a> to write a review.</p>
                    <?php endif ?>
                </div>
                <div class="stack">
                    <?php if ($reviews === []) : ?><p class="text-muted">No reviews yet. Be the first!</p><?php endif ?>
                    <?php foreach ($reviews as $r) : $n = display_name(['id' => $r['user_id'], 'username' => $r['username']]); ?>
                        <div class="card card-body">
                            <div class="row between"><div class="row gap-sm"><?= avatar((int) $r['user_id'], $n, 'avatar-sm') ?><strong class="text-sm"><?= esc($n) ?></strong></div><span class="text-xs text-muted"><?= time_ago($r['created_at']) ?></span></div>
                            <div class="mt-1"><?= stars((float) $r['rating']) ?> <?php if ($r['title']) : ?><strong><?= esc($r['title']) ?></strong><?php endif ?></div>
                            <?php if ($r['body']) : ?><p class="text-2 mb-0 mt-1"><?= esc($r['body']) ?></p><?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </section>

    <?php if ($related) : ?>
        <section class="section-sm">
            <h2 style="font-size:1.4rem">You may also like</h2>
            <div class="grid grid-4"><?php foreach ($related as $rp) : ?><?= view('partials/product_card', ['product' => $rp, 'wishlisted' => $wishlistIds]) ?><?php endforeach ?></div>
        </section>
    <?php endif ?>
    <section class="section-sm" id="recent-wrap" hidden>
        <h2 style="font-size:1.4rem">Recently viewed</h2>
        <div class="chips" id="recent"></div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var variants = <?= json_encode($vJson, JSON_UNESCAPED_UNICODE) ?>;
    var optCount = <?= count($p['option_defs']) ?>;
    var tracked = <?= $tracked ? 'true' : 'false' ?>;
    var form = document.getElementById('buy-form'), addBtn = document.getElementById('add-btn'), buyNow = document.getElementById('buy-now');
    var cartBase = <?= json_encode(site_url('cart/add/')) ?>;

    function selected() { var s = []; for (var i = 0; i < optCount; i++) { var r = form.querySelector('input[data-opt="' + i + '"]:checked'); s.push(r ? r.value : null); } return s; }
    function match(sel) { return variants.filter(function (v) { for (var i = 0; i < optCount; i++) { if (v.o[i] !== sel[i]) { return false; } } return true; })[0] || null; }
    function update() {
        var sel = selected(), v = optCount ? match(sel) : variants[0];
        for (var i = 0; i < optCount; i++) {
            var lbl = form.querySelector('[data-opt-label="' + i + '"]'); if (lbl) { lbl.textContent = sel[i] || ''; }
            form.querySelectorAll('input[data-opt="' + i + '"]').forEach(function (inp) {
                var test = sel.slice(); test[i] = inp.value; var m = match(test);
                inp.disabled = !m;
                inp.nextElementSibling.title = m && !m.ok ? 'Sold out' : '';
                inp.nextElementSibling.style.opacity = m && !m.ok ? '.55' : '';
            });
        }
        var price = document.getElementById('price'), compare = document.getElementById('compare'), save = document.getElementById('save'), note = document.getElementById('stock-note'), sku = document.getElementById('sku');
        if (!v) { price.textContent = 'Unavailable'; addBtn.disabled = buyNow.disabled = true; note.className = 'stock-note out'; note.innerHTML = PJ.icon('x') + 'This combination is not available'; return; }
        form.action = cartBase + v.id;
        price.textContent = v.price;
        compare.hidden = !v.compare; compare.textContent = v.compare || '';
        save.hidden = !v.save; save.textContent = 'Save ' + v.save + '%';
        sku.textContent = v.sku ? 'SKU ' + v.sku : '';
        addBtn.disabled = buyNow.disabled = !v.ok;
        addBtn.innerHTML = v.ok ? PJ.icon('bag') + 'Add to cart' : 'Sold out';
        if (!tracked) { note.className = 'stock-note in'; note.innerHTML = PJ.icon('check-circle') + <?= json_encode($digital ? 'Available — instant download' : 'In stock') ?>; }
        else if (!v.ok) { note.className = 'stock-note out'; note.innerHTML = PJ.icon('x') + 'Out of stock'; }
        else if (v.stock > 0 && v.stock <= 5) { note.className = 'stock-note low'; note.innerHTML = PJ.icon('alert') + 'Only ' + v.stock + ' left'; }
        else { note.className = 'stock-note in'; note.innerHTML = PJ.icon('check-circle') + (v.stock > 0 ? 'In stock, ready to ship' : 'Available on backorder'); }
        if (v.image) { setImage(v.image); }
    }
    form.addEventListener('change', function (e) { if (e.target.hasAttribute('data-opt')) { update(); } });
    update();

    /* Gallery */
    var main = document.getElementById('gallery-main');
    function setImage(src) {
        var img = document.getElementById('main-image'); if (!img) { return; }
        img.src = src;
        document.querySelectorAll('.gallery-thumbs button').forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-src') === src); });
    }
    document.querySelectorAll('.gallery-thumbs button').forEach(function (b) { b.addEventListener('click', function () { setImage(b.getAttribute('data-src')); }); });
    main.addEventListener('click', function () { if (document.getElementById('main-image')) { main.classList.toggle('is-zoomed'); } });
    main.addEventListener('mousemove', function (e) {
        var img = document.getElementById('main-image'); if (!img || !main.classList.contains('is-zoomed')) { return; }
        var r = main.getBoundingClientRect(); img.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%';
    });

    /* Cart count after AJAX add */
    form.addEventListener('pj:success', function (e) {
        var btn = document.querySelector('.nav-actions a[href$="/cart"]');
        if (btn && e.detail.count !== undefined) { var dot = btn.querySelector('.count-dot'); if (!dot) { dot = document.createElement('span'); dot.className = 'count-dot'; btn.appendChild(dot); } dot.textContent = e.detail.count; }
    });

    /* Recently viewed (stored in this browser only) */
    try {
        var key = 'pj-recent', me = { s: <?= json_encode($p['slug']) ?>, t: <?= json_encode($p['title'], JSON_UNESCAPED_UNICODE) ?> };
        var list = JSON.parse(localStorage.getItem(key) || '[]').filter(function (x) { return x.s !== me.s; });
        if (list.length) {
            document.getElementById('recent-wrap').hidden = false;
            document.getElementById('recent').innerHTML = list.slice(0, 6).map(function (x) { var a = document.createElement('a'); a.className = 'chip'; a.href = <?= json_encode(site_url('shop/')) ?> + x.s; a.textContent = x.t; return a.outerHTML; }).join('');
        }
        list.unshift(me); localStorage.setItem(key, JSON.stringify(list.slice(0, 8)));
    } catch (e) {}
})();
</script>
<?= $this->endSection() ?>
