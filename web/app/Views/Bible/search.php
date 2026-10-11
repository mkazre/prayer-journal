<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Search the Bible</h1>
<form method="get">
    <input type="text" name="q" value="<?= esc($term) ?>">
    <button class="pj-btn" type="submit">Search</button>
</form>
<?php foreach ($results as $r) : ?>
    <div class="pj-card" style="margin-top:.5rem">
        <a href="<?= site_url('bible/' . $r['abbrev'] . '/' . $r['chapter']) ?>"><?= esc($r['book_name']) ?> <?= esc($r['chapter']) ?>:<?= esc($r['verse']) ?></a>
        <p><?= esc($r['text']) ?></p>
    </div>
<?php endforeach ?>
<?= $this->endSection() ?>
