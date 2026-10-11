<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container container-narrow" style="max-width:900px">
    <div class="page-head">
        <h1>Account settings</h1>
        <p>Your profile, how the site looks for you, and your password.</p>
    </div>
    <div class="tabs" role="tablist">
        <button class="tab is-active" data-tab="profile" role="tab"><?= icon('user') ?>Profile</button>
        <button class="tab" data-tab="preferences" role="tab"><?= icon('sliders') ?>Preferences</button>
        <button class="tab" data-tab="security" role="tab"><?= icon('shield') ?>Security</button>
    </div>

    <section class="tab-panel is-active" id="profile">
        <form class="card card-body" method="post" action="<?= site_url('account/profile') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row gap-xl items-start">
                <div class="text-center">
                    <div id="avatar-preview"><?= avatar($user->id, display_name(), 'avatar-xl') ?></div>
                    <label class="btn btn-sm mt-2" for="avatar"><?= icon('upload') ?>Change photo</label>
                    <input class="sr-only" type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" onchange="if(this.files[0]){var u=URL.createObjectURL(this.files[0]);document.querySelector('#avatar-preview .avatar').innerHTML='<img src=&quot;'+u+'&quot; alt=&quot;&quot;>';}">
                    <?php if (! empty($profile['avatar_path'])) : ?><label class="check text-xs mt-1 center"><input type="checkbox" name="remove_avatar" value="1">Remove</label><?php endif ?>
                </div>
                <div class="grow" style="min-width:260px">
                    <div class="field"><label class="label" for="display_name">Display name</label><input class="input" id="display_name" name="display_name" maxlength="120" value="<?= esc((string) ($profile['display_name'] ?? ''), 'attr') ?>" placeholder="<?= esc($user->username, 'attr') ?>"><span class="help">Shown on the prayer wall, reviews and certificates.</span></div>
                    <div class="field"><label class="label" for="bio">About you <span class="opt">(optional)</span></label><textarea class="textarea" id="bio" name="bio" rows="3" maxlength="1000"><?= esc((string) ($profile['bio'] ?? '')) ?></textarea></div>
                    <div class="form-grid">
                        <div class="field"><span class="label">Username</span><input class="input" value="<?= esc($user->username, 'attr') ?>" disabled></div>
                        <div class="field"><span class="label">Email</span><input class="input" value="<?= esc($user->email, 'attr') ?>" disabled></div>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= icon('save') ?>Save profile</button>
                </div>
            </div>
        </form>
    </section>

    <section class="tab-panel" id="preferences">
        <form class="card card-body stack stack-lg" method="post" action="<?= site_url('account/preferences') ?>" data-ajax>
            <?= csrf_field() ?>
            <?php $seg = static function (string $name, array $options, string $current): string {
                $out = '<div class="segmented">';
                foreach ($options as $value => [$label, $ic]) {
                    $out .= '<label><input type="radio" name="' . $name . '" value="' . $value . '"' . ($current === $value ? ' checked' : '') . '><span>' . ($ic ? icon($ic) : '') . esc($label) . '</span></label>';
                }

                return $out . '</div>';
            }; ?>
            <div class="row between"><div><strong>Theme</strong><div class="text-sm text-muted">Auto follows your device setting.</div></div><?= $seg('theme', ['light' => ['Light', 'sun'], 'dark' => ['Dark', 'moon'], 'system' => ['Auto', 'monitor']], $prefs['theme']) ?></div>
            <div class="row between"><div><strong>Text size</strong><div class="text-sm text-muted">Applies across the whole site.</div></div><?= $seg('text', ['sm' => ['Small', null], 'md' => ['Default', null], 'lg' => ['Large', null], 'xl' => ['Extra large', null]], $prefs['text']) ?></div>
            <div class="row between"><div><strong>Reading font</strong><div class="text-sm text-muted">For lessons, devotions, scripture and your journal.</div></div><?= $seg('reading', ['serif' => ['Serif', null], 'sans' => ['Sans-serif', null]], $prefs['reading']) ?></div>
            <div class="row between"><div><strong>Bible layout</strong><div class="text-sm text-muted">Flowing paragraphs or one verse per line.</div></div><?= $seg('bible_layout', ['paragraph' => ['Paragraphs', 'file-text'], 'verse' => ['Verse by verse', 'list']], $prefs['bible_layout']) ?></div>
            <label class="switch"><input type="checkbox" name="verse_numbers" value="1" <?= $prefs['verse_numbers'] ? 'checked' : '' ?> onchange="this.nextElementSibling.nextElementSibling.value=this.checked?1:0"><span class="track"></span><input type="hidden" name="verse_numbers" value="<?= $prefs['verse_numbers'] ? 1 : 0 ?>">Show verse numbers in the Bible reader</label>
            <div><button class="btn btn-primary" type="submit" onclick="setTimeout(function(){location.reload()},500)"><?= icon('save') ?>Save preferences</button></div>
        </form>
    </section>

    <section class="tab-panel" id="security">
        <form class="card card-body" method="post" action="<?= site_url('account/password') ?>" style="max-width:520px">
            <?= csrf_field() ?>
            <h3>Change password</h3>
            <div class="field"><label class="label" for="cp">Current password</label><input class="input" id="cp" type="password" name="current_password" autocomplete="current-password" required></div>
            <div class="field"><label class="label" for="np">New password</label><input class="input" id="np" type="password" name="new_password" autocomplete="new-password" minlength="8" required></div>
            <div class="field"><label class="label" for="np2">Confirm new password</label><input class="input" id="np2" type="password" name="new_password_confirm" autocomplete="new-password" minlength="8" required></div>
            <button class="btn btn-primary" type="submit"><?= icon('lock') ?>Update password</button>
        </form>
        <div class="card card-body mt-2 row between">
            <div><strong>Your data</strong><div class="text-sm text-muted">Download a printable copy of your whole prayer journal.</div></div>
            <a class="btn" href="<?= site_url('journal/print') ?>" target="_blank" rel="noopener"><?= icon('download') ?>Export journal</a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
