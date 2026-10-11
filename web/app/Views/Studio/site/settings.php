<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Settings</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$groupIcons = ['Branding' => 'palette', 'Home page' => 'home', 'Contact & footer' => 'phone', 'Store' => 'store', 'Emails' => 'mail'];
$anchor     = static fn (string $g) => strtolower(strtok($g, ' '));
$logo       = $values['logo_path'] ?? '';
?>
<form method="post" action="<?= site_url('studio/settings') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="studio-head">
        <div><h1>Site settings</h1><p>Branding, home page copy and store behaviour. Changes go live as soon as you save.</p></div>
        <button class="btn btn-primary" type="submit"><?= icon('save') ?>Save settings</button>
    </div>
    <div class="tabs" role="tablist">
        <?php $first = true; foreach ($groups as $name => $fields) : ?><button class="tab <?= $first ? 'is-active' : '' ?>" type="button" role="tab" data-tab="<?= $anchor($name) ?>"><?= icon($groupIcons[$name] ?? 'settings') ?><?= esc($name) ?></button><?php $first = false; endforeach ?>
    </div>
    <div>
        <?php $first = true; foreach ($groups as $name => $fields) : ?>
            <section class="tab-panel card card-body <?= $first ? 'is-active' : '' ?>" id="<?= $anchor($name) ?>">
                <?php if ($name === 'Emails') : ?>
                    <div class="grid grid-4 mb-3">
                        <div class="card stat"><div class="stat-label"><?= icon('clock') ?>Scheduler</div><div class="stat-value" style="font-size:1.1rem"><?= $mail['cron'] ? '<span class="text-success">Running</span>' : '<span class="text-danger">Not set up</span>' ?></div><div class="stat-delta text-muted"><?= $mail['lastCron'] ? 'Last run ' . time_ago(date('Y-m-d H:i:s', $mail['lastCron'])) : 'Never run' ?></div></div>
                        <div class="card stat"><div class="stat-label"><?= icon('send') ?>Sent (7 days)</div><div class="stat-value"><?= (int) $mail['sent7'] ?></div><div class="stat-delta text-muted">via <?= $mail['smtp'] ? 'SMTP' : 'server mail' ?></div></div>
                        <div class="card stat"><div class="stat-label"><?= icon('history') ?>Waiting</div><div class="stat-value"><?= (int) $mail['queued'] ?></div><div class="stat-delta text-muted">in the queue</div></div>
                        <a class="card card-hover stat" href="<?= site_url('studio/emails?status=failed') ?>"><div class="stat-label"><?= icon('alert') ?>Failed</div><div class="stat-value <?= $mail['failed'] ? 'text-danger' : '' ?>"><?= (int) $mail['failed'] ?></div><div class="stat-delta text-muted">open email log</div></a>
                    </div>
                    <?php if (! $mail['cron']) : ?>
                        <div class="alert alert-warning mb-3"><?= icon('alert') ?><div><strong>Set up the scheduler</strong> so reminders, membership billing checks and emails run automatically. In cPanel open <em>Cron Jobs</em>, choose "Once Per Five Minutes" and use this command (or just re-run <code>deploy.sh</code>, which adds it for you):<br><code style="word-break:break-all"><?= esc($mail['cronCmd']) ?></code></div></div>
                    <?php endif ?>
                    <div class="row gap-sm mb-3">
                        <button class="btn" type="submit" formaction="<?= site_url('studio/settings/test-email') ?>" formnovalidate><?= icon('send') ?>Send me a test email</button>
                        <a class="btn btn-ghost" href="<?= site_url('studio/settings/preview-reminder') ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview a learning reminder</a>
                        <a class="btn btn-ghost" href="<?= site_url('studio/emails') ?>"><?= icon('list') ?>Email log</a>
                    </div>
                    <p class="help">Emails currently send from <strong><?= esc($mail['from']) ?></strong>. For best delivery, create that mailbox in cPanel (<em>Email Accounts</em>), enter its SMTP details below, and turn on <em>Email Deliverability</em> (SPF/DKIM) for your domain.</p>
                <?php endif ?>
                <?php if ($name === 'Branding') : ?>
                    <div class="field">
                        <label class="label">Logo</label>
                        <div class="row">
                            <img src="<?= esc(logo_url(), 'attr') ?>" alt="Current logo" style="width:64px;height:64px;border-radius:14px;object-fit:cover;border:1px solid var(--border)">
                            <div class="stack" style="gap:.35rem">
                                <input class="input" type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                <?php if ($logo) : ?><label class="check text-sm"><input type="checkbox" name="reset_logo" value="1"> Restore the default logo</label><?php endif ?>
                                <p class="help mb-0">Square PNG, at least 256×256. Appears on every page, the Studio, certificates and printed journals.</p>
                            </div>
                        </div>
                    </div>
                <?php endif ?>
                <div class="grid grid-2">
                    <?php foreach ($fields as $key => [$label, $type, $help]) : $v = (string) ($values[$key] ?? ''); $wide = $type === 'textarea'; ?>
                        <div class="field" <?= $wide ? 'style="grid-column:1/-1"' : '' ?>>
                            <?php if ($type === 'bool') : ?>
                                <label class="switch"><input type="checkbox" name="<?= $key ?>" value="1" <?= $v === '1' || ($v === '' && $key === 'prices_include_tax') ? 'checked' : '' ?>><span class="track"></span><?= esc($label) ?></label>
                            <?php else : ?>
                                <label class="label" for="s-<?= $key ?>"><?= esc($label) ?></label>
                                <?php if ($type === 'textarea') : ?>
                                    <textarea class="textarea" id="s-<?= $key ?>" name="<?= $key ?>" rows="3"><?= esc($v) ?></textarea>
                                <?php elseif ($type === 'password') : ?>
                                    <input class="input" id="s-<?= $key ?>" type="password" name="<?= $key ?>" autocomplete="new-password" placeholder="<?= $v !== '' ? '•••••••• (saved)' : '' ?>">
                                    <?php if ($v !== '') : ?><label class="check text-xs mt-1"><input type="checkbox" name="<?= $key ?>_clear" value="1"> Remove saved password</label><?php endif ?>
                                <?php elseif (str_starts_with($type, 'select:')) : ?>
                                    <select class="select" id="s-<?= $key ?>" name="<?= $key ?>"><?php foreach (explode('|', substr($type, 7)) as $opt) : [$ov, $ol] = explode('=', $opt, 2); ?><option value="<?= esc($ov, 'attr') ?>" <?= $v === $ov ? 'selected' : '' ?>><?= esc($ol) ?></option><?php endforeach ?></select>
                                <?php elseif ($type === 'color') : ?>
                                    <div class="row row-nowrap gap-sm"><input type="color" id="s-<?= $key ?>" name="<?= $key ?>" value="<?= esc($v ?: ($key === 'brand_color' ? '#4b1e82' : '#c9a227'), 'attr') ?>" style="width:48px;height:40px;padding:0;border:1px solid var(--border);border-radius:8px;background:none"><span class="text-sm text-muted"><?= esc($v ?: 'Default') ?></span></div>
                                <?php else : ?>
                                    <input class="input" id="s-<?= $key ?>" type="<?= $type === 'number' ? 'number' : ($type === 'email' ? 'email' : 'text') ?>" name="<?= $key ?>" value="<?= esc($v, 'attr') ?>">
                                <?php endif ?>
                            <?php endif ?>
                            <?php if ($help) : ?><p class="help"><?= esc($help) ?></p><?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </section>
        <?php $first = false; endforeach ?>
    </div>
</form>
<?= $this->endSection() ?>
