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
        <button class="tab" data-tab="membership" role="tab"><?= icon('sparkles') ?>Membership</button>
        <button class="tab" data-tab="emails" role="tab"><?= icon('mail') ?>Emails</button>
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

    <section class="tab-panel" id="membership">
        <?php
        $m      = $membership;
        $labels = ['active' => ['Active', 'success'], 'past_due' => ['Payment overdue', 'warning'], 'cancelled' => ['Cancelled — access until period end', 'brand']];
        ?>
        <?php if (! $m) : ?>
            <div class="card empty"><div class="feature-icon"><?= icon('sparkles', 'icon-lg') ?></div><h3>No active membership</h3><p>Membership unlocks every membership course for one monthly price. Cancel any time.</p><a class="btn btn-primary" href="<?= site_url('pricing') ?>">See plans</a></div>
        <?php else : [$label, $tone] = $labels[$m['status']] ?? [ucfirst($m['status']), 'brand']; ?>
            <div class="card card-body stack">
                <div class="row between">
                    <div><div class="text-xs text-muted">Your plan</div><h3 class="mb-0"><?= esc($m['plan']['name'] ?? 'Membership') ?></h3></div>
                    <span class="badge badge-<?= $tone ?>"><?= esc($label) ?></span>
                </div>
                <div class="grid grid-3">
                    <div><div class="text-xs text-muted">Price</div><strong><?= $m['plan'] ? money((int) $m['plan']['price_cents'], $m['plan']['currency']) . ($m['plan']['type'] === 'subscription' ? ' / ' . ($m['plan']['billing_interval'] === 'year' ? 'year' : 'month') : ' once') : '' ?></strong></div>
                    <div><div class="text-xs text-muted"><?= $m['status'] === 'cancelled' ? 'Access until' : ($m['renews'] ? 'Next payment' : 'Access until') ?></div><strong><?= $m['expires_at'] ? nice_date($m['expires_at'], 'j F Y') : 'Lifetime' ?></strong></div>
                    <div><div class="text-xs text-muted">Member since</div><strong><?= $m['started_at'] ? nice_date($m['started_at'], 'j F Y') : '—' ?></strong></div>
                </div>
                <?php if ($m['status'] === 'past_due') : ?>
                    <div class="alert alert-warning"><?= icon('alert') ?><div>We could not collect your last payment. Please update your card before <strong><?= nice_date(date('Y-m-d H:i:s', strtotime($m['expires_at']) + \App\Modules\Shop\Services\Membership::GRACE_DAYS * 86400), 'j F Y') ?></strong> to keep your access.</div></div>
                <?php endif ?>
                <div class="row gap-sm">
                    <?php if ($m['gateway'] === 'payfast' && $m['gateway_token'] && in_array($m['status'], ['active', 'past_due'], true)) : ?>
                        <a class="btn" href="<?= esc(\App\Modules\Shop\Services\PayFastGateway::updateCardUrl($m['gateway_token'], site_url('account#membership')), 'attr') ?>"><?= icon('credit-card') ?>Update payment card</a>
                    <?php endif ?>
                    <?php if (in_array($m['status'], ['active', 'past_due'], true) && ($m['plan']['type'] ?? '') === 'subscription') : ?>
                        <form method="post" action="<?= site_url('account/membership/cancel') ?>"><?= csrf_field() ?><button class="btn btn-ghost" type="submit" data-confirm="Cancel your membership? You keep access until <?= $m['expires_at'] ? esc(nice_date($m['expires_at'], 'j F Y'), 'attr') : 'the end of the period' ?>."><?= icon('x') ?>Cancel membership</button></form>
                    <?php elseif ($m['status'] === 'cancelled') : ?>
                        <a class="btn btn-primary" href="<?= site_url('pricing') ?>"><?= icon('refresh') ?>Rejoin</a>
                    <?php endif ?>
                </div>
            </div>
        <?php endif ?>
        <?php if ($memberPayments) : ?>
            <div class="card mt-3">
                <div class="card-header"><h2><?= icon('receipt') ?> Payment history</h2></div>
                <div class="table-wrap" style="border:0;border-radius:0"><table class="table">
                    <thead><tr><th>Date</th><th>Plan</th><th>Method</th><th class="num">Amount</th></tr></thead>
                    <tbody><?php foreach ($memberPayments as $p) : ?><tr><td class="text-sm"><?= nice_date($p['created_at'], 'j M Y') ?></td><td class="text-sm"><?= esc($p['plan_name']) ?></td><td class="text-sm"><?= esc(ucfirst((string) $p['gateway'])) ?></td><td class="num"><?= money((int) $p['amount_cents'], $p['currency']) ?></td></tr><?php endforeach ?></tbody>
                </table></div>
            </div>
        <?php endif ?>
    </section>

    <section class="tab-panel" id="emails">
        <form class="card card-body stack" method="post" action="<?= site_url('account/preferences') ?>" data-ajax>
            <?= csrf_field() ?>
            <div><h3 class="mb-0">Email preferences</h3><p class="text-sm text-muted">Receipts, membership billing and account security emails are always sent. Choose which other emails you would like.</p></div>
            <?php foreach (['email_reminders' => ['Learning reminders', 'A friendly nudge with your progress and next lesson when you have been away for a while.'], 'email_courses' => ['Course updates', 'Announcements from the instructors of your courses.'], 'email_community' => ['Prayer wall & discussions', 'When someone prays for your request or replies to your question.']] as $key => [$label, $help]) : ?>
                <label class="switch"><input type="checkbox" value="1" <?= ! empty($prefs[$key]) ? 'checked' : '' ?> onchange="this.nextElementSibling.nextElementSibling.value=this.checked?1:0"><span class="track"></span><input type="hidden" name="<?= $key ?>" value="<?= ! empty($prefs[$key]) ? 1 : 0 ?>"><span><strong><?= $label ?></strong><br><span class="text-sm text-muted"><?= $help ?></span></span></label>
            <?php endforeach ?>
            <div><button class="btn btn-primary" type="submit"><?= icon('save') ?>Save email preferences</button></div>
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
