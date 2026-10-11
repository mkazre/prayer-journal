<?php
/** @var array $product row from ProductModel::storefront() */
$p       = $product;
$onSale  = ! empty($p['compare_at_cents']) && (int) $p['compare_at_cents'] > (int) $p['price_cents'];
$soldOut = (int) ($p['track_inventory'] ?? 1) === 1 && (int) ($p['allow_backorder'] ?? 0) === 0 && (int) ($p['stock'] ?? 0) <= 0 && ($p['product_type'] ?? 'physical') !== 'digital';
$wished  = ! empty($wishlisted) && in_array((int) $p['id'], $wishlisted, true);
?>
<article class="product-card">
    <div class="product-media">
        <a href="<?= site_url('shop/' . $p['slug']) ?>" aria-label="<?= esc($p['title'], 'attr') ?>">
            <?php if (! empty($p['image'])) : ?>
                <img src="<?= base_url($p['image']) ?>" alt="<?= esc($p['title'], 'attr') ?>" loading="lazy">
                <?php if (! empty($p['image2'])) : ?><img class="alt" src="<?= base_url($p['image2']) ?>" alt="" loading="lazy"><?php endif ?>
            <?php else : ?>
                <div class="placeholder"><?= icon('book', 'icon-xl') ?></div>
            <?php endif ?>
        </a>
        <div class="badges">
            <?php if ($soldOut) : ?><span class="badge">Sold out</span><?php elseif ($onSale) : ?><span class="badge badge-danger">Sale</span><?php endif ?>
            <?php if (($p['product_type'] ?? '') === 'digital') : ?><span class="badge badge-info"><?= icon('download') ?>Digital</span><?php endif ?>
        </div>
        <?php if (auth()->loggedIn()) : ?>
            <form method="post" action="<?= site_url('wishlist/toggle/' . $p['id']) ?>" data-ajax data-wish>
                <?= csrf_field() ?>
                <button class="wish <?= $wished ? 'is-on' : '' ?>" type="submit" aria-label="Save to wishlist" aria-pressed="<?= $wished ? 'true' : 'false' ?>"><?= icon('heart') ?></button>
            </form>
        <?php endif ?>
        <?php if (! $soldOut && ! empty($p['default_variant_id']) && (int) ($p['variant_count'] ?? 1) === 1) : ?>
            <form class="quick" method="post" action="<?= site_url('cart/add/' . $p['default_variant_id']) ?>" data-ajax data-cart-add>
                <?= csrf_field() ?><input type="hidden" name="quantity" value="1">
                <button class="btn btn-primary btn-block btn-sm" type="submit"><?= icon('plus') ?>Add to cart</button>
            </form>
        <?php endif ?>
    </div>
    <div class="product-info">
        <?php if (! empty($p['brand'])) : ?><span class="vendor"><?= esc($p['brand']) ?></span><?php endif ?>
        <h3><a href="<?= site_url('shop/' . $p['slug']) ?>"><?= esc($p['title']) ?></a></h3>
        <div class="row gap-sm">
            <span class="price"><?= (int) ($p['variant_count'] ?? 1) > 1 && (int) $p['price_cents'] !== (int) ($p['max_price_cents'] ?? $p['price_cents']) ? 'From ' : '' ?><?= money((int) $p['price_cents']) ?></span>
            <?php if ($onSale) : ?><span class="price-compare"><?= money((int) $p['compare_at_cents']) ?></span><?php endif ?>
            <?php if (! empty($p['avg_rating'])) : ?><span class="text-xs text-muted" style="margin-left:auto"><?= stars((float) $p['avg_rating']) ?></span><?php endif ?>
        </div>
    </div>
</article>
