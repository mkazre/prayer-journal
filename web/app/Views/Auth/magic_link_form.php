<?= $this->extend(config('Auth')->views['layout']) ?>
<?= $this->section('main') ?>
<h1>Sign in with a link</h1>
<p class="text-2 mb-3">Enter your email and we'll send you a one-time sign-in link.</p>
<?= $this->include('Auth/_errors') ?>
<form action="<?= url_to('magic-link') ?>" method="post" class="stack">
    <?= csrf_field() ?>
    <div class="field">
        <label class="label" for="email">Email</label>
        <div class="input-icon"><?= icon('mail') ?><input class="input input-lg" id="email" type="email" name="email" autocomplete="email" value="<?= esc((string) old('email', auth()->user()->email ?? null), 'attr') ?>" required autofocus></div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block">Send link <?= icon('send') ?></button>
</form>
<p class="text-center mt-3"><a href="<?= url_to('login') ?>"><?= icon('arrow-left') ?> Back to sign in</a></p>
<?= $this->endSection() ?>
