<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Models\CartModel;
use App\Modules\Shop\Services\Pricing;

$v = static fn (string $k, string $d = '') => esc((string) old($k, $prefill[$k] ?? $d), 'attr');
?>
<div class="container">
    <div class="page-head">
        <nav class="breadcrumbs"><a href="<?= site_url('cart') ?>">Cart</a><?= icon('chevron-right') ?><span>Checkout</span></nav>
        <h1>Checkout</h1>
    </div>
    <form method="post" action="<?= site_url('checkout') ?>" id="checkout-form">
        <?= csrf_field() ?>
        <div class="split">
            <div class="stack stack-lg">
                <section class="card card-body">
                    <h2 style="font-size:1.15rem;font-family:var(--font-body)"><span class="qnum">1</span> Contact</h2>
                    <div class="form-grid">
                        <div class="field"><label class="label" for="email">Email</label><input class="input" id="email" type="email" name="email" value="<?= $v('email') ?>" required autocomplete="email"></div>
                        <div class="field"><label class="label" for="name">Full name</label><input class="input" id="name" name="name" value="<?= $v('name') ?>" required autocomplete="name"></div>
                        <div class="field span-2"><label class="label" for="phone">Phone <span class="opt">(for delivery updates)</span></label><input class="input" id="phone" type="tel" name="phone" value="<?= $v('phone') ?>" autocomplete="tel"></div>
                    </div>
                    <?php if (! auth()->loggedIn()) : ?><p class="text-sm text-muted mb-0">Have an account? <a href="<?= site_url('login') ?>">Sign in</a> for faster checkout and order history.</p><?php endif ?>
                </section>

                <?php if ($quote['needs_shipping']) : ?>
                    <section class="card card-body">
                        <h2 style="font-size:1.15rem;font-family:var(--font-body)"><span class="qnum">2</span> Delivery address</h2>
                        <div class="form-grid">
                            <div class="field span-2"><label class="label" for="line1">Street address</label><input class="input" id="line1" name="line1" value="<?= $v('line1') ?>" required autocomplete="address-line1"></div>
                            <div class="field span-2"><label class="label" for="line2">Apartment, suite, complex <span class="opt">(optional)</span></label><input class="input" id="line2" name="line2" value="<?= $v('line2') ?>" autocomplete="address-line2"></div>
                            <div class="field"><label class="label" for="city">City</label><input class="input" id="city" name="city" value="<?= $v('city') ?>" required autocomplete="address-level2"></div>
                            <div class="field"><label class="label" for="region">Province / state</label><input class="input" id="region" name="region" value="<?= $v('region') ?>" autocomplete="address-level1"></div>
                            <div class="field"><label class="label" for="postal_code">Postal code</label><input class="input" id="postal_code" name="postal_code" value="<?= $v('postal_code') ?>" required autocomplete="postal-code"></div>
                            <div class="field"><label class="label" for="country">Country</label>
                                <select class="select" id="country" name="country" autocomplete="country" onchange="var f=this.form;var i=document.createElement('input');i.type='hidden';i.name='refresh';i.value='1';f.appendChild(i);f.noValidate=true;f.submit();">
                                    <?php foreach (Pricing::COUNTRIES as $code => $name) : ?><option value="<?= $code ?>" <?= $country === $code ? 'selected' : '' ?>><?= esc($name) ?></option><?php endforeach ?>
                                </select></div>
                        </div>
                    </section>

                    <section class="card card-body">
                        <h2 style="font-size:1.15rem;font-family:var(--font-body)"><span class="qnum">3</span> Delivery method</h2>
                        <?php if (! $quote['shipping_available']) : ?>
                            <div class="alert alert-warning"><?= icon('alert') ?><div>We don't ship to <?= esc(Pricing::COUNTRIES[$country] ?? $country) ?> yet. Please contact us.</div></div>
                        <?php else : foreach ($quote['rates'] as $r) : ?>
                            <label class="radio-card">
                                <input type="radio" name="shipping_rate" value="<?= (int) $r['id'] ?>" <?= (int) ($quote['shipping_rate']['id'] ?? -1) === (int) $r['id'] ? 'checked' : '' ?> onchange="var f=this.form;var i=document.createElement('input');i.type='hidden';i.name='refresh';i.value='1';f.appendChild(i);f.noValidate=true;f.submit();">
                                <span class="grow fw-600"><?= esc($r['name']) ?></span>
                                <span class="price"><?= (int) $r['price_cents'] === 0 ? 'Free' : money((int) $r['price_cents']) ?></span>
                                <?php if (isset($r['original_cents']) && $r['original_cents'] > 0) : ?><span class="price-compare"><?= money((int) $r['original_cents']) ?></span><?php endif ?>
                            </label>
                        <?php endforeach; endif ?>
                    </section>
                <?php endif ?>

                <section class="card card-body">
                    <h2 style="font-size:1.15rem;font-family:var(--font-body)"><span class="qnum"><?= $quote['needs_shipping'] ? 4 : 2 ?></span> Payment</h2>
                    <?php if ($gateways === []) : ?>
                        <div class="alert alert-warning"><?= icon('alert') ?><div>Online payments aren't set up yet. Please contact us to complete your order.</div></div>
                    <?php else : $firstGw = array_key_first($gateways); foreach ($gateways as $key => $label) : ?>
                        <label class="radio-card">
                            <input type="radio" name="gateway" value="<?= $key ?>" <?= $key === $firstGw ? 'checked' : '' ?>>
                            <?= icon($key === 'test' ? 'zap' : 'credit-card', 'icon-lg') ?><span class="grow fw-600"><?= esc($label) ?></span>
                            <?php if ($key === 'test') : ?><span class="badge badge-warning">Development</span><?php endif ?>
                        </label>
                    <?php endforeach; endif ?>
                    <div class="field mt-2"><label class="label" for="note">Order note <span class="opt">(optional)</span></label><textarea class="textarea" id="note" name="note" rows="2" placeholder="Gift message, delivery instructions…"><?= esc((string) old('note')) ?></textarea></div>
                </section>
            </div>

            <aside class="card card-body sticky">
                <h2 style="font-size:1.15rem;font-family:var(--font-body)">Your order</h2>
                <?php foreach ($items as $item) : ?>
                    <div class="row row-nowrap mb-1">
                        <span class="cart-thumb" style="width:52px;position:relative"><?= $item['image'] ? '<img src="' . base_url($item['image']) . '" alt="">' : icon('book') ?><span class="count-dot" style="top:-6px;right:-6px"><?= (int) $item['quantity'] ?></span></span>
                        <div class="grow text-sm"><div class="fw-600"><?= esc($item['title']) ?></div><div class="text-muted"><?= esc(CartModel::variantLabel($item)) ?></div></div>
                        <span class="text-sm fw-600"><?= money((int) $item['unit_price_cents'] * (int) $item['quantity']) ?></span>
                    </div>
                <?php endforeach ?>
                <hr style="margin:1rem 0">
                <?= view('Shop/_summary', ['quote' => $quote]) ?>
                <button class="btn btn-primary btn-lg btn-block mt-2" type="submit" <?= $gateways === [] || ! $quote['shipping_available'] ? 'disabled' : '' ?>><?= icon('lock') ?>Pay <?= money($quote['total']) ?></button>
                <p class="text-xs text-muted text-center mt-1"><?= icon('shield-check') ?> Payments are processed securely. We never see your card details.</p>
            </aside>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
