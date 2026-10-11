<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Models\ProductModel;

$q = static fn (array $over) => current_url() . '?' . http_build_query(array_filter(array_merge(array_diff_key($filters, ['collection' => 1]), ['page' => null], $over), static fn ($v) => $v !== null && $v !== ''));
$activeFilters = array_filter(array_diff_key($filters, ['sort' => 1, 'collection' => 1]), static fn ($v) => $v !== '');
?>
<div class="page-head-band">
    <div class="container page-head">
        <nav class="breadcrumbs"><a href="<?= site_url('shop') ?>">Shop</a><?php if ($collection) : ?><?= icon('chevron-right') ?><span><?= esc($collection['title']) ?></span><?php endif ?></nav>
        <h1><?= esc($collection['title'] ?? site_setting('shop_title', 'Resources for the journey')) ?></h1>
        <p><?= esc($collection['description'] ?? site_setting('shop_subtitle', 'Journals, books and gifts to help you pray, study and grow — digital downloads arrive instantly.')) ?></p>
    </div>
</div>
<div class="container">
    <?php if ($featured) : ?>
        <div class="grid grid-3 mb-4">
            <?php foreach (array_slice($featured, 0, 3) as $c) : ?>
                <a class="card card-hover" href="<?= site_url('shop/collections/' . $c['slug']) ?>" style="overflow:hidden;color:inherit">
                    <div class="course-cover" style="aspect-ratio:21/9;<?= cover_gradient($c['slug']) ?>">
                        <?php if ($c['image_path']) : ?><img src="<?= base_url($c['image_path']) ?>" alt=""><?php else : ?><div class="cover-art"><span><?= esc($c['title']) ?></span></div><?php endif ?>
                    </div>
                    <div class="card-body-sm row between"><strong><?= esc($c['title']) ?></strong><span class="text-sm text-muted"><?= (int) $c['product_count'] ?> items <?= icon('arrow-right') ?></span></div>
                </a>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <div class="split-left">
        <aside>
            <button class="btn btn-block show-sm mb-2" type="button" data-toggle-class="is-open@#shop-filters"><?= icon('filter') ?>Filters<?= $activeFilters ? ' (' . count($activeFilters) . ')' : '' ?></button>
            <form id="shop-filters" class="card card-body sticky shop-filters" method="get" action="<?= current_url() ?>">
                <input type="hidden" name="sort" value="<?= esc($filters['sort'], 'attr') ?>">
                <div class="filter-group">
                    <div class="input-icon"><?= icon('search') ?><input class="input input-sm" type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Search products"></div>
                </div>
                <?php if ($categories) : ?>
                    <div class="filter-group">
                        <h4>Category</h4>
                        <label class="check"><input type="radio" name="category" value="" <?= $filters['category'] === '' ? 'checked' : '' ?> data-autosubmit>All</label>
                        <?php foreach ($categories as $cat) : ?>
                            <label class="check"><input type="radio" name="category" value="<?= esc($cat['slug'], 'attr') ?>" <?= $filters['category'] === $cat['slug'] ? 'checked' : '' ?> data-autosubmit><span class="grow"><?= esc($cat['name']) ?></span><span class="text-xs text-muted"><?= (int) $cat['product_count'] ?></span></label>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
                <?php if (! $collection && $collections) : ?>
                    <div class="filter-group">
                        <h4>Collections</h4>
                        <?php foreach ($collections as $c) : ?><a class="row between text-sm" style="padding:.2rem 0" href="<?= site_url('shop/collections/' . $c['slug']) ?>"><span><?= esc($c['title']) ?></span><span class="text-muted"><?= (int) $c['product_count'] ?></span></a><?php endforeach ?>
                    </div>
                <?php endif ?>
                <div class="filter-group">
                    <h4>Price (<?= esc(store_currency()) ?>)</h4>
                    <div class="row row-nowrap gap-sm"><input class="input input-sm" type="number" min="0" step="1" name="min" value="<?= esc($filters['min'], 'attr') ?>" placeholder="Min"><span class="text-muted">–</span><input class="input input-sm" type="number" min="0" step="1" name="max" value="<?= esc($filters['max'], 'attr') ?>" placeholder="Max"></div>
                </div>
                <div class="filter-group">
                    <h4>Availability</h4>
                    <label class="check"><input type="checkbox" name="in_stock" value="1" <?= $filters['in_stock'] ? 'checked' : '' ?> data-autosubmit>In stock</label>
                    <label class="check"><input type="checkbox" name="on_sale" value="1" <?= $filters['on_sale'] ? 'checked' : '' ?> data-autosubmit>On sale</label>
                </div>
                <div class="filter-group" style="border:0">
                    <h4>Format</h4>
                    <label class="check"><input type="radio" name="type" value="" <?= $filters['type'] === '' ? 'checked' : '' ?> data-autosubmit>Any</label>
                    <label class="check"><input type="radio" name="type" value="physical" <?= $filters['type'] === 'physical' ? 'checked' : '' ?> data-autosubmit>Physical</label>
                    <label class="check"><input type="radio" name="type" value="digital" <?= $filters['type'] === 'digital' ? 'checked' : '' ?> data-autosubmit>Digital download</label>
                </div>
                <div class="row"><button class="btn btn-primary btn-sm grow" type="submit">Apply</button><?php if ($activeFilters) : ?><a class="btn btn-ghost btn-sm" href="<?= current_url() ?>">Clear</a><?php endif ?></div>
            </form>
        </aside>
        <div>
            <div class="toolbar">
                <span class="text-sm text-muted"><?= number_format($page['total']) ?> product<?= $page['total'] === 1 ? '' : 's' ?></span>
                <form method="get" action="<?= current_url() ?>" class="row gap-sm">
                    <?php foreach (array_diff_key($filters, ['sort' => 1, 'collection' => 1]) as $k => $v) : if ($v !== '') : ?><input type="hidden" name="<?= $k ?>" value="<?= esc($v, 'attr') ?>"><?php endif; endforeach ?>
                    <label class="text-sm text-muted" for="sort">Sort</label>
                    <select class="select input-sm" id="sort" name="sort" data-autosubmit style="width:auto">
                        <?php foreach (ProductModel::SORTS as $k => $l) : ?><option value="<?= $k ?>" <?= $filters['sort'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?>
                    </select>
                </form>
            </div>
            <?php if ($page['items'] === []) : ?>
                <div class="card empty"><div class="feature-icon"><?= icon('bag', 'icon-lg') ?></div><h3>No products found</h3><p>Try clearing a filter or searching for something else.</p><a class="btn" href="<?= site_url('shop') ?>">View all products</a></div>
            <?php else : ?>
                <div class="grid grid-3">
                    <?php foreach ($page['items'] as $product) : ?><?= view('partials/product_card', ['product' => $product, 'wishlisted' => $wishlisted]) ?><?php endforeach ?>
                </div>
                <?php if ($page['pages'] > 1) : ?>
                    <nav class="pagination" aria-label="Pages">
                        <?php for ($i = 1; $i <= $page['pages']; $i++) : ?><a class="<?= $i === $page['page'] ? 'is-active' : '' ?>" href="<?= $q(['page' => $i]) ?>"><?= $i ?></a><?php endfor ?>
                    </nav>
                <?php endif ?>
            <?php endif ?>
        </div>
    </div>
</div>
<style>@media (max-width: 1000px) { .shop-filters:not(.is-open) { display: none; } }</style>
<?= $this->endSection() ?>
