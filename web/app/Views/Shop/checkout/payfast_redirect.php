<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Redirecting to PayFast...</h1>
    <p>Please wait while we take you to PayFast to complete your payment.</p>
    <form id="payfast-form" method="post" action="<?= esc($processUrl) ?>">
        <?php foreach ($fields as $key => $value) : ?>
            <input type="hidden" name="<?= esc($key) ?>" value="<?= esc($value) ?>">
        <?php endforeach ?>
    </form>
</div>
<script>document.getElementById('payfast-form').submit();</script>
<?= $this->endSection() ?>
