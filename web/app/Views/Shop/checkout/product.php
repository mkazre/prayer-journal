<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container container-narrow" style="max-width:560px">
    <div class="page-head text-center"><h1><?= esc($title) ?></h1></div>
    <div class="card card-body">
        <div class="row row-nowrap">
            <span class="feature-icon" style="margin:0"><?= icon($icon, 'icon-lg') ?></span>
            <div class="grow"><div class="text-xs text-muted uppercase fw-700"><?= esc($kind) ?></div><strong style="font-size:1.1rem"><?= esc($name) ?></strong><?php if ($description) : ?><div class="text-sm text-2"><?= esc($description) ?></div><?php endif ?></div>
        </div>
        <hr>
        <div class="summary-row total" style="border:0;margin:0;padding:0"><span>Total</span><span><?= money($price, $currency) ?><span class="text-sm text-muted fw-500"><?= esc($suffix ?? '') ?></span></span></div>
        <form method="post" class="mt-3">
            <?= csrf_field() ?>
            <?php if ($gateways === [] && $price > 0) : ?>
                <div class="alert alert-warning"><?= icon('alert') ?><div>Online payments aren't set up yet. Please contact us.</div></div>
            <?php else : $firstGw = array_key_first($gateways); foreach ($gateways as $key => $label) : ?>
                <label class="radio-card"><input type="radio" name="gateway" value="<?= $key ?>" <?= $key === $firstGw ? 'checked' : '' ?>><?= icon($key === 'test' ? 'zap' : 'credit-card') ?><span class="grow fw-600"><?= esc($label) ?></span></label>
            <?php endforeach ?>
                <button class="btn btn-primary btn-lg btn-block mt-2" type="submit"><?= icon('lock') ?><?= $price > 0 ? 'Pay ' . money($price, $currency) : 'Get access' ?></button>
            <?php endif ?>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
