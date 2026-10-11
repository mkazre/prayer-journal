<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($course['title']) ?></h1>
    <p><?= esc($course['description']) ?></p>
    <p><strong><?= $course['price_cents'] > 0 ? '$' . number_format($course['price_cents'] / 100, 2) : 'Free' ?></strong></p>
    <?php if ($enrolled) : ?>
        <a class="pj-btn" href="<?= site_url('study/' . $course['slug']) ?>">Go to Course</a>
    <?php else : ?>
        <a class="pj-btn pj-btn-gold" href="<?= site_url('courses/' . $course['slug'] . '/enroll') ?>"><?= $course['price_cents'] > 0 ? 'Buy Course' : 'Enroll Free' ?></a>
    <?php endif ?>
</div>

<div class="pj-card" style="margin-top:1.5rem">
    <h2>Reviews <?= $avgRating ? '(⭐ ' . esc($avgRating) . '/5)' : '' ?></h2>
    <?php if ($enrolled) : ?>
        <form method="post" action="<?= site_url('courses/' . $course['slug'] . '/review') ?>">
            <?= csrf_field() ?>
            <select name="rating" required>
                <?php for ($i = 5; $i >= 1; $i--) : ?><option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option><?php endfor ?>
            </select>
            <input type="text" name="body" placeholder="Share your thoughts...">
            <button class="pj-btn" type="submit">Submit Review</button>
        </form>
    <?php endif ?>
    <?php foreach ($reviews as $r) : ?>
        <div style="border-top:1px solid var(--pj-border);padding:.75rem 0">
            <strong><?= esc($r['username']) ?></strong> — <?= str_repeat('⭐', (int) $r['rating']) ?>
            <p><?= esc($r['body']) ?></p>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
