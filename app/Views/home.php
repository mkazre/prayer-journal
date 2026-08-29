<?= $this->extend('layouts/main') ?>

<?= $this->section('hero') ?>
<section class="pj-hero">
    <h1>Grow in the Word. Study, Pray, Grow.</h1>
    <p>Study courses, read Scripture, track your growth, and shop resources &mdash; all in one place, built for your walk of faith.</p>
    <?php if (! auth()->loggedIn()) : ?>
        <a class="pj-btn pj-btn-gold" href="<?= site_url('register') ?>">Get Started Free</a>
        <a class="pj-btn pj-btn-outline" href="<?= site_url('courses') ?>">Browse Courses</a>
    <?php else : ?>
        <a class="pj-btn pj-btn-gold" href="<?= site_url('dashboard') ?>">Go to your Dashboard</a>
    <?php endif ?>
</section>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $verse = verse_of_the_day(); ?>
<?php if ($verse) : ?>
    <div class="pj-card" style="text-align:center;margin-bottom:2rem">
        <span class="pj-badge pj-badge-gold">Verse of the Day</span>
        <p class="pj-verse" style="margin:1rem 0 0.5rem">&ldquo;<?= esc($verse['text']) ?>&rdquo;</p>
        <p><a href="<?= site_url('bible/' . $verse['abbrev'] . '/' . $verse['chapter']) ?>"><?= esc($verse['book_name']) ?> <?= esc($verse['chapter']) ?>:<?= esc($verse['verse']) ?></a></p>
    </div>
<?php endif ?>

<div class="pj-grid">
    <a class="pj-card" href="<?= site_url('courses') ?>">
        <h3>📚 Study</h3>
        <p>Take courses, watch lessons, complete quizzes and assignments, and get graded.</p>
    </a>
    <a class="pj-card" href="<?= site_url('bible') ?>">
        <h3>📖 Read</h3>
        <p>Browse the full Bible, bookmark verses, highlight, and keep personal notes.</p>
    </a>
    <a class="pj-card" href="<?= site_url('shop') ?>">
        <h3>🛍️ Shop</h3>
        <p>Find books, journals, and resources to support your journey.</p>
    </a>
    <a class="pj-card" href="<?= site_url('prayer-requests') ?>">
        <h3>🙏 Pray</h3>
        <p>Share prayer requests and stand with the community in prayer.</p>
    </a>
</div>
<?= $this->endSection() ?>
