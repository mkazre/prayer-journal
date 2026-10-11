<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>My Bookmarks</h1>
<?php foreach ($bookmarks as $b) : ?>
    <div class="pj-card" style="margin-top:.5rem">
        <a href="<?= site_url('bible/' . $b['abbrev'] . '/' . $b['chapter']) ?>"><?= esc($b['book_name']) ?> <?= esc($b['chapter']) ?>:<?= esc($b['verse']) ?></a>
        <p><?= esc($b['text']) ?></p>
    </div>
<?php endforeach ?>
<?php if (empty($bookmarks)) : ?><p>No bookmarks yet.</p><?php endif ?>
<?= $this->endSection() ?>
