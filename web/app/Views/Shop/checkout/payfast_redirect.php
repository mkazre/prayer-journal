<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container container-narrow text-center section" style="max-width:520px">
    <div class="feature-icon" style="margin:0 auto 1rem"><?= icon('lock', 'icon-lg') ?></div>
    <h1 style="font-size:1.6rem">Taking you to PayFast…</h1>
    <p class="text-2">You'll complete payment on PayFast's secure page.</p>
    <form method="post" action="<?= esc($processUrl, 'attr') ?>" id="payfast-form">
        <?php foreach ($fields as $k => $v) : ?><input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc((string) $v, 'attr') ?>"><?php endforeach ?>
        <button class="btn btn-primary btn-lg" type="submit">Continue to PayFast <?= icon('arrow-right') ?></button>
    </form>
    <script>document.getElementById('payfast-form').submit();</script>
</div>
<?= $this->endSection() ?>
