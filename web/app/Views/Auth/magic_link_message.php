<?= $this->extend(config('Auth')->views['layout']) ?>
<?= $this->section('main') ?>
<div class="feature-icon gold" style="width:3.5rem;height:3.5rem"><?= icon('mail', 'icon-lg') ?></div>
<h1>Check your email</h1>
<p class="text-2">We've sent a sign-in link to your inbox. It expires shortly, so use it soon.</p>
<a class="btn btn-block mt-2" href="<?= url_to('login') ?>"><?= icon('arrow-left') ?> Back to sign in</a>
<?= $this->endSection() ?>
