<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Search</h1>
<form method="get">
    <input type="text" name="q" value="<?= esc($term) ?>" placeholder="Search courses, products, Bible verses...">
    <button class="pj-btn" type="submit">Search</button>
</form>

<?php if ($term !== '') : ?>
    <h2>Courses</h2>
    <?php foreach ($courses as $c) : ?><p><a href="<?= site_url('courses/' . $c['slug']) ?>"><?= esc($c['title']) ?></a></p><?php endforeach ?>
    <?php if (empty($courses)) : ?><p>No matching courses.</p><?php endif ?>

    <h2>Shop</h2>
    <?php foreach ($products as $p) : ?><p><a href="<?= site_url('shop/' . $p['slug']) ?>"><?= esc($p['title']) ?></a></p><?php endforeach ?>
    <?php if (empty($products)) : ?><p>No matching products.</p><?php endif ?>

    <h2>Bible</h2>
    <?php foreach ($verses as $v) : ?><p><a href="<?= site_url('bible/' . $v['abbrev'] . '/' . $v['chapter']) ?>"><?= esc($v['book_name']) ?> <?= esc($v['chapter']) ?>:<?= esc($v['verse']) ?></a> — <?= esc($v['text']) ?></p><?php endforeach ?>
    <?php if (empty($verses)) : ?><p>No matching verses.</p><?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
