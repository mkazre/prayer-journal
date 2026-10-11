<?= $this->extend('layouts/main') ?>

<?= $this->section('hero') ?>
<section class="hero">
    <div class="container">
        <div class="hero-grid">
            <div class="fade-in">
                <span class="eyebrow"><?= icon('feather') ?><?= esc(site_setting('hero_eyebrow', 'A companion for your secret place')) ?></span>
                <h1 class="mt-2"><?= site_setting('hero_title') ? esc(site_setting('hero_title')) : 'Make prayer your <em>lifestyle</em>, not a ritual.' ?></h1>
                <p class="mt-2" style="max-width:560px"><?= esc(site_setting('hero_subtitle', 'Guided courses, the whole Bible, a private prayer journal that remembers every answered prayer, and resources for every season of faith.')) ?></p>
                <div class="row mt-3">
                    <?php if (! auth()->loggedIn()) : ?>
                        <a class="btn btn-accent btn-lg" href="<?= site_url('register') ?>">Start free <?= icon('arrow-right') ?></a>
                        <a class="btn btn-light btn-lg" href="<?= site_url('courses') ?>"><?= icon('graduation') ?>Browse courses</a>
                    <?php else : ?>
                        <a class="btn btn-accent btn-lg" href="<?= site_url('dashboard') ?>">Continue where you left off <?= icon('arrow-right') ?></a>
                        <a class="btn btn-light btn-lg" href="<?= site_url('journal') ?>"><?= icon('notebook') ?>Open my journal</a>
                    <?php endif ?>
                </div>
                <div class="hero-stats">
                    <div><strong><?= number_format($stats['devotions']) ?></strong><span>guided devotions</span></div>
                    <div><strong><?= number_format($stats['verses']) ?></strong><span>verses of scripture</span></div>
                    <?php if ($stats['prayers'] > 0) : ?>
                        <div><strong><?= number_format($stats['prayers']) ?></strong><span>prayers prayed together</span></div>
                    <?php else : ?>
                        <div><strong>66</strong><span>books of the Bible</span></div>
                    <?php endif ?>
                </div>
            </div>
            <div class="hero-card fade-in" style="animation-delay:.1s">
                <?php if ($devotion && $spotlight) : ?>
                    <div class="row between">
                        <span class="eyebrow"><?= icon('sunrise') ?>Today's devotion</span>
                        <span class="badge" style="background:rgba(255,255,255,.12);color:#fff;border-color:transparent"><?= icon('clock') ?><?= (int) $devotion['duration_mins'] ?: 10 ?> min</span>
                    </div>
                    <h3 class="mt-2" style="color:#fff;font-family:var(--font-display);font-size:1.6rem"><?= esc($devotion['title']) ?></h3>
                    <?php if ($devotion['bible_reference']) : ?><p class="mb-1" style="color:var(--accent);font-weight:600"><?= icon('book-open') ?> <?= esc($devotion['bible_reference']) ?></p><?php endif ?>
                    <p class="clamp-3" style="font-family:var(--font-read);font-size:1.02rem"><?= esc(excerpt($devotion['content'], 260)) ?></p>
                    <div class="row mt-2">
                        <a class="btn btn-accent" href="<?= site_url(auth()->loggedIn() ? 'study/' . $spotlight['slug'] . '/lesson/' . $devotion['id'] : 'courses/' . $spotlight['slug']) ?>">Read &amp; journal <?= icon('arrow-right') ?></a>
                        <span class="text-sm" style="color:rgba(255,255,255,.7)">from <?= esc($spotlight['title']) ?></span>
                    </div>
                <?php elseif ($verse) : ?>
                    <span class="eyebrow"><?= icon('sunrise') ?>Verse of the day</span>
                    <p class="mt-2" style="font-family:var(--font-read);font-size:1.4rem;color:#fff">&ldquo;<?= esc($verse['text']) ?>&rdquo;</p>
                    <p style="color:var(--accent);font-weight:700"><?= esc($verse['book_name']) ?> <?= (int) $verse['chapter'] ?>:<?= (int) $verse['verse'] ?></p>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Everything in one place</span>
                <h2>Built for your whole walk of faith</h2>
                <p>Study, pray, read and grow — with tools that remember where you are.</p>
            </div>
        </div>
        <div class="grid grid-3">
            <?php foreach ([
                ['graduation', 'Guided courses', 'Lessons, devotions, quizzes and certificates — at your own pace or one day at a time.', 'courses', ''],
                ['notebook', 'Private prayer journal', 'Record prayer points with scripture, mark them answered, and look back on God\'s faithfulness.', 'journal', 'gold'],
                ['book-open', 'The whole Bible', 'A beautiful reader with highlights, notes, bookmarks and instant verse look-ups everywhere.', 'bible', ''],
                ['calendar-check', 'Reading plans', 'Bible in a year, the Gospels in 30 days, Psalms & Proverbs — with progress you can see.', 'bible/plans', 'gold'],
                ['hands', 'Prayer wall', 'Share requests, stand with others in prayer, and celebrate answered prayers together.', 'prayer-requests', ''],
                ['bag', 'Resources shop', 'Journals, books and gifts — digital downloads delivered instantly.', 'shop', 'gold'],
            ] as [$ic, $h, $p, $href, $tone]) : ?>
                <a class="card card-hover feature" href="<?= site_url($href) ?>">
                    <div class="feature-icon <?= $tone ?>"><?= icon($ic, 'icon-lg') ?></div>
                    <h3><?= esc($h) ?></h3>
                    <p><?= esc($p) ?></p>
                </a>
            <?php endforeach ?>
        </div>
    </div>
</section>

<?php if ($spotlight) : ?>
<section class="section-sm">
    <div class="container">
        <div class="card" style="overflow:hidden">
            <div class="grid grid-2" style="gap:0;align-items:stretch">
                <div class="course-cover" style="<?= course_cover_style($spotlight) ?>;aspect-ratio:auto;min-height:320px">
                    <?php if (! empty($spotlight['thumbnail_path'])) : ?>
                        <img src="<?= base_url($spotlight['thumbnail_path']) ?>" alt="">
                    <?php else : ?>
                        <div class="cover-art" style="padding:2rem"><span style="font-size:2rem;max-width:420px"><?= esc($spotlight['title']) ?></span></div>
                    <?php endif ?>
                </div>
                <div class="card-body" style="padding:2.5rem">
                    <span class="eyebrow"><?= icon('sparkles') ?>Featured course</span>
                    <h2 class="mt-1"><?= esc($spotlight['title']) ?></h2>
                    <p class="text-2"><?= esc($spotlight['summary']) ?></p>
                    <div class="course-meta mb-2" style="font-size:.9rem">
                        <span><?= icon('layers') ?><?= (int) $spotlight['lesson_count'] ?> lessons</span>
                        <span><?= icon('clock') ?><?= \App\Modules\Lms\Services\Curriculum::duration((int) $spotlight['total_minutes']) ?></span>
                        <?php if ($spotlight['author_name']) : ?><span><?= icon('feather') ?><?= esc($spotlight['author_name']) ?></span><?php endif ?>
                    </div>
                    <ul class="checklist mb-3">
                        <?php foreach (array_slice(array_filter(explode("\n", (string) $spotlight['what_you_learn'])), 0, 4) as $line) : ?>
                            <li><?= icon('check-circle') ?><?= esc($line) ?></li>
                        <?php endforeach ?>
                    </ul>
                    <div class="row">
                        <a class="btn btn-primary btn-lg" href="<?= site_url('courses/' . $spotlight['slug']) ?>">View course <?= icon('arrow-right') ?></a>
                        <span class="price <?= (int) $spotlight['price_cents'] === 0 ? 'price-free' : '' ?>" style="font-size:1.25rem"><?= price_label((int) $spotlight['price_cents']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif ?>

<?php if (count($courses) > 1) : ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Learn</span><h2>Popular courses</h2></div>
            <a class="btn btn-ghost" href="<?= site_url('courses') ?>">All courses <?= icon('arrow-right') ?></a>
        </div>
        <div class="grid grid-3">
            <?php foreach (array_slice($courses, 0, 3) as $course) : ?>
                <?= view('partials/course_card', ['course' => $course]) ?>
            <?php endforeach ?>
        </div>
    </div>
</section>
<?php endif ?>

<section class="section">
    <div class="container">
        <div class="grid grid-2 gap-xl" style="align-items:center">
            <div>
                <span class="eyebrow">How it works</span>
                <h2>Three simple rhythms</h2>
                <div class="stack stack-lg mt-3">
                    <?php foreach ([
                        ['book-open', 'Read', 'Open today\'s devotion and its key scripture — tap any reference to read the verse in place.'],
                        ['pen', 'Write', 'Record your prayer points, meditation and gratitude. Everything saves as you type.'],
                        ['check-circle', 'Remember', 'Mark prayers answered and add your testimony. Your journal becomes a record of God\'s faithfulness.'],
                    ] as $i => [$ic, $h, $p]) : ?>
                        <div class="row items-start row-nowrap gap-lg">
                            <div class="feature-icon <?= $i === 1 ? 'gold' : '' ?>" style="margin:0"><?= icon($ic, 'icon-lg') ?></div>
                            <div><h3 class="mb-0"><?= ($i + 1) . '. ' . esc($h) ?></h3><p class="text-2 mb-0"><?= esc($p) ?></p></div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <?php if ($verse) : ?>
                <div class="verse-card">
                    <span class="eyebrow"><?= icon('sunrise') ?>Verse of the day</span>
                    <p class="verse-text">&ldquo;<?= esc($verse['text']) ?>&rdquo;</p>
                    <div class="row between">
                        <a class="verse-ref" href="<?= site_url('bible/' . \App\Libraries\Scripture::slug((int) $verse['book_position']) . '/' . $verse['chapter'] . '#v' . $verse['verse']) ?>"><?= esc($verse['book_name']) ?> <?= (int) $verse['chapter'] ?>:<?= (int) $verse['verse'] ?> · KJV</a>
                        <button class="btn btn-sm btn-ghost" type="button" data-share="&ldquo;<?= esc($verse['text'], 'attr') ?>&rdquo; — <?= esc($verse['book_name'], 'attr') ?> <?= (int) $verse['chapter'] ?>:<?= (int) $verse['verse'] ?>" data-share-title="Verse of the day"><?= icon('share') ?>Share</button>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
</section>

<?php if ($products !== []) : ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Shop</span><h2>Resources for the journey</h2></div>
            <a class="btn btn-ghost" href="<?= site_url('shop') ?>">Visit the shop <?= icon('arrow-right') ?></a>
        </div>
        <div class="grid grid-4">
            <?php foreach ($products as $product) : ?>
                <?= view('partials/product_card', ['product' => $product]) ?>
            <?php endforeach ?>
        </div>
    </div>
</section>
<?php endif ?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Community</span><h2>Pray with others</h2><p>Every request is held up by real people. Add yours, or stand with someone today.</p></div>
            <a class="btn btn-primary" href="<?= site_url('prayer-requests') ?>"><?= icon('hands') ?>Visit the prayer wall</a>
        </div>
        <?php if ($prayers !== []) : ?>
            <div class="grid grid-3">
                <?php foreach ($prayers as $p) : ?>
                    <div class="card prayer-card <?= $p['is_answered'] ? 'is-answered' : '' ?>">
                        <div class="row between">
                            <span class="badge badge-brand"><?= esc(\App\Modules\Core\Models\PrayerRequestModel::CATEGORIES[$p['category']] ?? 'General') ?></span>
                            <?php if ($p['is_answered']) : ?><span class="badge badge-success"><?= icon('check') ?>Answered</span><?php endif ?>
                        </div>
                        <div class="body clamp-3"><?= esc($p['body']) ?></div>
                        <div class="row between text-sm text-muted mt-auto">
                            <span><?= $p['is_anonymous'] ? 'Anonymous' : esc(display_name(['id' => $p['user_id'], 'username' => $p['username']])) ?> · <?= time_ago($p['created_at']) ?></span>
                            <span><?= icon('hands') ?> <?= (int) $p['prayer_count'] ?></span>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
</section>

<?php if (! auth()->loggedIn()) : ?>
<section class="section-sm">
    <div class="container">
        <div class="welcome-band text-center" style="padding:3.5rem 2rem">
            <img src="<?= logo_url() ?>" alt="" width="64" height="64" style="margin:0 auto 1rem;border-radius:16px">
            <h2 style="font-size:clamp(1.8rem,3vw,2.6rem)">Your prayer life is about to change.</h2>
            <p class="mb-3" style="max-width:560px;margin-left:auto;margin-right:auto">Create a free account to save your journal, track your courses and keep your Bible highlights on every device.</p>
            <a class="btn btn-accent btn-lg" href="<?= site_url('register') ?>">Create your free account <?= icon('arrow-right') ?></a>
        </div>
    </div>
</section>
<?php endif ?>
<?= $this->endSection() ?>
