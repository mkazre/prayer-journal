<?= $this->extend(config('Auth')->views['layout']) ?>
<?= $this->section('main') ?>
<h1>Create your account</h1>
<p class="text-2 mb-3">Free forever. Save your journal, progress and highlights everywhere.</p>
<?= $this->include('Auth/_errors') ?>
<form action="<?= url_to('register') ?>" method="post" class="stack">
    <?= csrf_field() ?>
    <div class="field">
        <label class="label" for="username">Name you'd like to use</label>
        <div class="input-icon"><?= icon('user') ?><input class="input input-lg" id="username" type="text" name="username" autocomplete="username" value="<?= esc((string) old('username'), 'attr') ?>" required minlength="3" maxlength="30" pattern="[A-Za-z0-9._\-]+" title="Letters, numbers, dots, dashes or underscores" autofocus></div>
        <span class="help">Letters, numbers, dots or dashes — you can set a display name later.</span>
    </div>
    <div class="field">
        <label class="label" for="email">Email</label>
        <div class="input-icon"><?= icon('mail') ?><input class="input input-lg" id="email" type="email" name="email" inputmode="email" autocomplete="email" value="<?= esc((string) old('email'), 'attr') ?>" required></div>
    </div>
    <div class="form-grid">
        <div class="field">
            <label class="label" for="password">Password</label>
            <input class="input input-lg" id="password" type="password" name="password" autocomplete="new-password" required minlength="8">
        </div>
        <div class="field">
            <label class="label" for="password_confirm">Confirm</label>
            <input class="input input-lg" id="password_confirm" type="password" name="password_confirm" autocomplete="new-password" required minlength="8">
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block">Create account <?= icon('arrow-right') ?></button>
    <p class="text-xs text-muted text-center">By creating an account you agree to use this community with kindness and respect.</p>
</form>
<p class="text-center text-2 mt-3">Already have an account? <a class="fw-600" href="<?= url_to('login') ?>">Sign in</a></p>
<?= $this->endSection() ?>
