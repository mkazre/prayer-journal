<?php
$p = user_prefs();
?>
<!doctype html>
<html lang="en" data-theme-pref="<?= esc($p['theme'], 'attr') ?>" data-text="<?= esc($p['text'], 'attr') ?>" data-reading="<?= esc($p['reading'], 'attr') ?>"<?= auth()->loggedIn() ? ' data-auth' : '' ?> data-prefs-url="<?= site_url('account/preferences') ?>" data-passage-url="<?= site_url('bible/passage') ?>">
