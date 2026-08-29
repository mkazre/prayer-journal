<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>The Bible</h1>
<form method="get" action="<?= site_url('bible/search') ?>" style="margin-bottom:1rem">
    <input type="text" name="q" placeholder="Search verses...">
    <button class="pj-btn" type="submit">Search</button>
</form>
<?php if (auth()->loggedIn()) : ?>
    <p><a href="<?= site_url('bible/my-bookmarks') ?>">My Bookmarks</a></p>
    <?php if ($streak > 0) : ?><p class="pj-badge pj-badge-gold">🔥 <?= esc($streak) ?>-day reading streak</p><?php endif ?>
<?php endif ?>

<?php if (empty($books)) : ?>
    <p class="pj-card">The Bible text hasn't been imported yet. Run <code>php spark bible:import</code>.</p>
<?php endif ?>

<h2>Old Testament</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.5rem">
    <?php foreach ($books as $b) : if ($b['testament'] !== 'OT') { continue; } ?>
        <a class="pj-btn" href="<?= site_url('bible/' . $b['abbrev'] . '/1') ?>"><?= esc($b['name']) ?></a>
    <?php endforeach ?>
</div>
<h2>New Testament</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.5rem">
    <?php foreach ($books as $b) : if ($b['testament'] !== 'NT') { continue; } ?>
        <a class="pj-btn" href="<?= site_url('bible/' . $b['abbrev'] . '/1') ?>"><?= esc($b['name']) ?></a>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
