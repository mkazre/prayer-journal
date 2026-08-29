<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Courses</h1>
<form method="get" style="margin-bottom:1rem">
    <input type="text" name="q" placeholder="Search courses..." value="<?= esc($_GET['q'] ?? '') ?>">
    <button class="pj-btn" type="submit">Search</button>
</form>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem">
    <?php foreach ($courses as $c) : ?>
        <a class="pj-card" style="text-decoration:none;color:inherit" href="<?= site_url('courses/' . $c['slug']) ?>">
            <h3><?= esc($c['title']) ?></h3>
            <p><?= esc($c['summary']) ?></p>
            <?php if ($c['avg_rating']) : ?><p>⭐ <?= esc($c['avg_rating']) ?>/5</p><?php endif ?>
            <p><strong><?= $c['price_cents'] > 0 ? '$' . number_format($c['price_cents'] / 100, 2) : 'Free' ?></strong></p>
        </a>
    <?php endforeach ?>
    <?php if (empty($courses)) : ?>
        <p>No courses published yet.</p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
