<?= $this->extend(config('Auth')->views['layout']) ?>
<?= $this->section('main') ?>
<h1>Welcome back</h1>
<p class="text-2 mb-3">Sign in to continue your courses, journal and reading plans.</p>
<?= $this->include('Auth/_errors') ?>
<form action="<?= url_to('login') ?>" method="post" class="stack">
    <?= csrf_field() ?>
    <div class="field">
        <label class="label" for="email">Email</label>
        <div class="input-icon"><?= icon('mail') ?><input class="input input-lg" id="email" type="email" name="email" inputmode="email" autocomplete="email" value="<?= esc((string) old('email'), 'attr') ?>" required autofocus></div>
    </div>
    <div class="field">
        <div class="row between"><label class="label" for="password">Password</label>
            <?php if (setting('Auth.allowMagicLinkLogins')) : ?><a class="text-sm" href="<?= url_to('magic-link') ?>">Forgot password?</a><?php endif ?>
        </div>
        <div class="input-icon"><?= icon('lock') ?><input class="input input-lg" id="password" type="password" name="password" autocomplete="current-password" required></div>
    </div>
    <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
        <label class="check"><input type="checkbox" name="remember" <?= old('remember') ? 'checked' : '' ?>>Keep me signed in on this device</label>
    <?php endif ?>
    <button type="submit" class="btn btn-primary btn-lg btn-block">Sign in <?= icon('arrow-right') ?></button>
</form>
<?php if (setting('Auth.allowRegistration')) : ?>
    <p class="text-center text-2 mt-3">New here? <a class="fw-600" href="<?= url_to('register') ?>">Create a free account</a></p>
<?php endif ?>
<?= $this->endSection() ?>
