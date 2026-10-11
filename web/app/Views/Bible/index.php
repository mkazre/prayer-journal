<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php use App\Libraries\Scripture; ?>
<div class="page-head-band">
    <div class="container page-head">
        <div class="row between items-start">
            <div>
                <span class="eyebrow"><?= icon('book-open') ?>King James Version</span>
                <h1 class="mt-1">The Holy Bible</h1>
                <p>Read, search, highlight and take notes — your library follows you on every device.</p>
            </div>
            <?php if ($streak > 0) : ?><span class="streak"><?= icon('flame') ?><?= $streak ?>-day reading streak</span><?php endif ?>
        </div>
        <form class="row mt-3" method="get" action="<?= site_url('bible/search') ?>" style="max-width:680px">
            <div class="input-icon grow"><?= icon('search') ?><input class="input input-lg" type="search" name="q" placeholder='Search words, or go to a passage like "John 3:16" or "Psalm 23"' aria-label="Search the Bible"></div>
            <button class="btn btn-primary btn-lg" type="submit">Search</button>
        </form>
        <div class="chips mt-2">
            <?php foreach (['John 1', 'Psalms 23', 'Romans 8', 'Matthew 5', 'Proverbs 3', 'Isaiah 40', '1 Corinthians 13', 'Ephesians 6'] as $q) : $r = Scripture::parse($q); ?>
                <a class="chip" href="<?= site_url('bible/' . Scripture::slug($r['book']) . '/' . $r['chapter']) ?>"><?= esc($q) ?></a>
            <?php endforeach ?>
        </div>
    </div>
</div>

<div class="container">
    <div class="grid grid-3 mb-4">
        <?php if ($last) : ?>
            <a class="card card-hover card-body row row-nowrap" href="<?= esc($last['url'], 'attr') ?>" style="color:inherit">
                <span class="feature-icon" style="margin:0"><?= icon('history', 'icon-lg') ?></span>
                <div><div class="text-sm text-muted">Continue reading</div><strong style="font-size:1.1rem"><?= esc($last['label']) ?></strong></div>
            </a>
        <?php else : ?>
            <a class="card card-hover card-body row row-nowrap" href="<?= site_url('bible/genesis/1') ?>" style="color:inherit">
                <span class="feature-icon" style="margin:0"><?= icon('play', 'icon-lg') ?></span>
                <div><div class="text-sm text-muted">Start at the beginning</div><strong style="font-size:1.1rem">Genesis 1</strong></div>
            </a>
        <?php endif ?>
        <a class="card card-hover card-body row row-nowrap" href="<?= site_url('bible/plans') ?>" style="color:inherit">
            <span class="feature-icon gold" style="margin:0"><?= icon('calendar-check', 'icon-lg') ?></span>
            <div><div class="text-sm text-muted">Reading plans</div><strong style="font-size:1.1rem"><?= $plans ? count($plans) . ' in progress' : 'Start a plan' ?></strong></div>
        </a>
        <a class="card card-hover card-body row row-nowrap" href="<?= site_url(auth()->loggedIn() ? 'bible/library' : 'login') ?>" style="color:inherit">
            <span class="feature-icon" style="margin:0"><?= icon('highlighter', 'icon-lg') ?></span>
            <div><div class="text-sm text-muted">My library</div><strong style="font-size:1.1rem"><?= $counts ? $counts['highlights'] . ' highlights · ' . $counts['notes'] . ' notes' : 'Sign in to save' ?></strong></div>
        </a>
    </div>

    <?php if ($verse) : ?>
        <div class="verse-card mb-4">
            <span class="eyebrow"><?= icon('sunrise') ?>Verse of the day</span>
            <p class="verse-text">&ldquo;<?= esc($verse['text']) ?>&rdquo;</p>
            <a class="verse-ref" href="<?= site_url('bible/' . Scripture::slug((int) $verse['book_position']) . '/' . $verse['chapter'] . '#v' . $verse['verse']) ?>"><?= esc($verse['book_name']) ?> <?= (int) $verse['chapter'] ?>:<?= (int) $verse['verse'] ?></a>
        </div>
    <?php endif ?>

    <div class="tabs" role="tablist">
        <button class="tab is-active" data-tab="ot" role="tab">Old Testament <span class="badge">39</span></button>
        <button class="tab" data-tab="nt" role="tab">New Testament <span class="badge">27</span></button>
    </div>
    <?php foreach (['OT' => 'ot', 'NT' => 'nt'] as $t => $id) : ?>
        <div class="tab-panel <?= $id === 'ot' ? 'is-active' : '' ?>" id="<?= $id ?>">
            <div class="book-groups">
                <?php foreach ($grouped[$t] ?? [] as $group => $books) : ?>
                    <div class="book-group">
                        <h3><?= esc($group) ?></h3>
                        <div class="book-grid">
                            <?php foreach ($books as $b) : ?>
                                <a class="book-btn" href="<?= site_url('bible/' . Scripture::slug((int) $b['position'])) ?>"><span><?= esc($b['name']) ?></span><small><?= (int) $b['chapter_count'] ?></small></a>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
