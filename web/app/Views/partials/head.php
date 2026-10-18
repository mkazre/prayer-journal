<?php
/**
 * Shared <head> contents for every layout.
 *
 * @var string|null $title
 * @var string|null $metaDescription
 */
$prefs       = user_prefs();
$pageTitle   = isset($title) && $title !== site_name() ? $title . ' · ' . site_name() : site_name() . ' — ' . site_setting('tagline', 'Study, pray and grow');
$description = $metaDescription ?? site_setting('meta_description', 'Courses, a full Bible reader, a personal prayer journal and resources for your walk of faith.');
$brand       = preg_match('/^#[0-9a-f]{6}$/i', (string) site_setting('brand_color')) ? site_setting('brand_color') : null;
$accent      = preg_match('/^#[0-9a-f]{6}$/i', (string) site_setting('accent_color')) ? site_setting('accent_color') : null;
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= esc($pageTitle) ?></title>
<meta name="description" content="<?= esc($description, 'attr') ?>">
<meta name="theme-color" content="#161c2e">
<meta property="og:title" content="<?= esc($pageTitle, 'attr') ?>">
<meta property="og:description" content="<?= esc($description, 'attr') ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="<?= esc($ogImage ?? base_url('assets/img/icon-1024.png'), 'attr') ?>">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="pj-icons" content="<?= esc(icon_sprite_url(), 'attr') ?>">
<link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon-64.png') ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/img/apple-touch-icon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Literata:ital,opsz,wght@0,7..72,400;0,7..72,600;1,7..72,400&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/app.css') ?>">
<?php if ($brand || $accent) : ?>
<style>:root{<?= $brand ? '--brand:' . $brand . ';' : '' ?><?= $accent ? '--accent:' . $accent . ';' : '' ?>}</style>
<?php endif ?>
<script>
(function () {
    var r = document.documentElement, ls = function (k) { try { return localStorage.getItem('pj-' + k); } catch (e) { return null; } };
    var authed = r.hasAttribute('data-auth');
    var pref = (authed ? r.getAttribute('data-theme-pref') : ls('theme')) || r.getAttribute('data-theme-pref') || 'system';
    var dark = pref === 'dark' || (pref !== 'light' && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches);
    r.setAttribute('data-theme', dark ? 'dark' : 'light');
    r.setAttribute('data-theme-pref', pref);
    if (!authed) {
        if (ls('text')) { r.setAttribute('data-text', ls('text')); }
        if (ls('reading')) { r.setAttribute('data-reading', ls('reading')); }
    }
})();
</script>
<?= $this->renderSection('head') ?>
