<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Models\CartModel;

$count = array_sum(array_column($items, 'quantity'));
$left  = $quote['free_shipping_over'] > 0 && $quote['needs_shipping'] ? $quote['free_shipping_over'] - ($quote['subtotal'] - $quote['discount']) : 0;
?>
<div class="container">
    <div class="page-head"><h1>Your cart</h1><p><?= $count ? $count . ' item' . ($count === 1 ? '' : 's') : 'Your cart is empty.' ?></p></div>
    <?php if ($items === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('bag', 'icon-lg') ?></div><h3>Nothing here yet</h3><p>Browse journals, books and gifts for your walk of faith.</p><a class="btn btn-primary" href="<?= site_url('shop') ?>">Start shopping</a></div>
    <?php else : ?>
        <div class="split">
            <div>
                <?php if ($quote['free_shipping_over'] > 0 && $quote['needs_shipping']) : ?>
                    <div class="card card-body mb-2">
                        <?php if ($left > 0) : ?>
                            <div class="text-sm mb-1"><?= icon('truck') ?> Spend <strong><?= money($left) ?></strong> more for free shipping.</div>
                        <?php else : ?>
                            <div class="text-sm mb-1 text-success"><?= icon('check-circle') ?> You've unlocked free shipping!</div>
                        <?php endif ?>
                        <div class="progress"><span style="width:<?= min(100, (int) round(($quote['subtotal'] - $quote['discount']) / $quote['free_shipping_over'] * 100)) ?>%"></span></div>
                    </div>
                <?php endif ?>
                <div class="card card-body" style="padding-top:.25rem;padding-bottom:.25rem">
                    <?php foreach ($items as $item) :
                        $label   = CartModel::variantLabel($item);
                        $tracked = (int) $item['track_inventory'] && ! (int) $item['allow_backorder'] && $item['product_type'] !== 'digital';
                        $short   = $tracked && (int) $item['quantity'] > (int) $item['stock']; ?>
                        <div class="cart-line">
                            <a class="cart-thumb" href="<?= site_url('shop/' . $item['slug']) ?>"><?= $item['image'] ? '<img src="' . base_url($item['image']) . '" alt="">' : icon('book', 'icon-lg') ?></a>
                            <div>
                                <a class="fw-600" href="<?= site_url('shop/' . $item['slug']) ?>" style="color:inherit"><?= esc($item['title']) ?></a>
                                <?php if ($label !== '') : ?><div class="text-sm text-muted"><?= esc($label) ?></div><?php endif ?>
                                <?php if ($item['product_type'] === 'digital') : ?><span class="badge badge-info mt-1"><?= icon('download') ?>Digital download</span><?php endif ?>
                                <?php if ($short) : ?><div class="text-sm text-danger mt-1"><?= icon('alert') ?> Only <?= (int) $item['stock'] ?> available</div><?php endif ?>
                                <div class="row mt-1">
                                    <?php if ($item['product_type'] !== 'digital') : ?>
                                        <form method="post" action="<?= site_url('cart/items/' . $item['id']) ?>">
                                            <?= csrf_field() ?>
                                            <div class="qty qty-sm" data-qty><button type="button" data-step="-" aria-label="Decrease"><?= icon('minus') ?></button><input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="0" max="99" data-autosubmit aria-label="Quantity"><button type="button" data-step="+" aria-label="Increase"><?= icon('plus') ?></button></div>
                                        </form>
                                    <?php endif ?>
                                    <form method="post" action="<?= site_url('cart/items/' . $item['id'] . '/remove') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit"><?= icon('trash') ?>Remove</button></form>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="price"><?= money((int) $item['unit_price_cents'] * (int) $item['quantity']) ?></div>
                                <?php if ((int) $item['quantity'] > 1) : ?><div class="text-xs text-muted"><?= money((int) $item['unit_price_cents']) ?> each</div><?php endif ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
                <a class="btn btn-ghost mt-2" href="<?= site_url('shop') ?>"><?= icon('arrow-left') ?>Continue shopping</a>
            </div>
            <aside class="card card-body sticky">
                <h2 style="font-size:1.2rem;font-family:var(--font-body)">Order summary</h2>
                <form method="post" action="<?= site_url('cart/discount') ?>" class="input-group mb-2">
                    <?= csrf_field() ?>
                    <?php if ($quote['discount_row']) : ?>
                        <input class="input" value="<?= esc($quote['discount_row']['code'], 'attr') ?>" disabled><button class="btn" type="submit" name="remove" value="1">Remove</button>
                    <?php else : ?>
                        <input class="input" name="code" placeholder="Discount code" style="text-transform:uppercase" aria-label="Discount code"><button class="btn" type="submit">Apply</button>
                    <?php endif ?>
                </form>
                <?= view('Shop/_summary', ['quote' => $quote, 'hideRateName' => true]) ?>
                <?php if ($quote['needs_shipping']) : ?><p class="text-xs text-muted mt-1"><?= icon('info') ?> Shipping estimated for <?= esc(\App\Modules\Shop\Services\Pricing::COUNTRIES[$quote['country']] ?? $quote['country']) ?>; confirmed at checkout.</p><?php endif ?>
                <a class="btn btn-primary btn-lg btn-block mt-2" href="<?= site_url('checkout') ?>"><?= icon('lock') ?>Checkout</a>
                <div class="row center gap-sm mt-2 text-xs text-muted"><?= icon('shield-check') ?>Secure checkout</div>
            </aside>
        </div>
        <?php if ($suggested) : ?>
            <section class="section-sm"><h2 style="font-size:1.35rem">You might also like</h2><div class="grid grid-4"><?php foreach ($suggested as $p) : ?><?= view('partials/product_card', ['product' => $p]) ?><?php endforeach ?></div></section>
        <?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
