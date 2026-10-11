<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Settings</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$groupIcons = ['Branding' => 'palette', 'Home page' => 'home', 'Contact & footer' => 'mail', 'Store' => 'store'];
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
