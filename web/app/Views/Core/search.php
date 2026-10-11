<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$total = count($courses) + count($lessons) + count($products) + $verseCount + count($journal) + ($reference ? 1 : 0);
$mark  = static function (string $text) use ($term): string {
    $safe = esc($text);
    foreach (array_slice(array_filter(preg_split('/\s+/u', trim($term, '"')) ?: [], static fn ($w) => mb_strlen($w) > 1), 0, 6) as $w) {
        $safe = preg_replace('/(' . preg_quote(esc($w), '/') . ')/iu', '<mark>$1</mark>', $safe);
    }

    return $safe;
};
?>
<div class="container container-narrow" style="max-width:980px">
    <div class="page-head">
        <h1>Search</h1>
        <form method="get" action="<?= site_url('search') ?>" class="input-icon mt-2" role="search">
            <?= icon('search') ?><input class="input input-lg" type="search" name="q" value="<?= esc($term, 'attr') ?>" placeholder="Try “John 3:16”, “gratitude” or “journal”" autofocus aria-label="Search">
        </form>
        <?php if ($term !== '') : ?><p class="mt-2"><?= $total ? number_format($total) . ' results for' : 'Nothing found for' ?> <strong>“<?= esc($term) ?>”</strong></p><?php endif ?>
    </div>

    <?php if ($term === '') : ?>
        <div class="grid grid-3">
            <?php foreach ([['bible', 'Scripture', 'Type a reference like Psalm 23 or Romans 8:28 to jump straight to it.', 'bible'], ['graduation', 'Courses & lessons', 'Find teaching by topic: prayer, gratitude, meditation…', 'courses'], ['notebook', 'Your journal', 'Signed-in searches include your own prayers and reflections.', 'journal']] as [$i, $h, $p, $u]) : ?>
                <a class="card card-hover card-body" href="<?= site_url($u) ?>" style="color:inherit"><div class="feature-icon"><?= icon($i, 'icon-lg') ?></div><h3><?= $h ?></h3><p class="text-sm text-muted mb-0"><?= $p ?></p></a>
            <?php endforeach ?>
        </div>
    <?php else : ?>
        <div class="stack">
            <?php if ($reference) : ?>
                <a class="card card-hover card-body row row-nowrap" href="<?= \App\Libraries\Scripture::url($reference) ?>" style="color:inherit">
                    <span class="feature-icon"><?= icon('book-open', 'icon-lg') ?></span>
                    <span class="grow"><span class="eyebrow">Bible passage</span><br><strong style="font-size:1.15rem"><?= esc($reference['label']) ?></strong><br><span class="text-sm text-muted">Open in the reader</span></span>
                    <?= icon('arrow-right') ?>
                </a>
            <?php endif ?>

            <?php if ($courses) : ?>
                <section>
                    <h2 class="h-sm"><?= icon('graduation') ?> Courses</h2>
                    <div class="grid grid-3"><?php foreach ($courses as $course) : ?><?= view('partials/course_card', ['course' => $course]) ?><?php endforeach ?></div>
                </section>
            <?php endif ?>

            <?php if ($lessons) : ?>
                <section class="card">
                    <div class="card-header"><h2><?= icon('list') ?> Lessons</h2></div>
                    <div class="divider-list">
                        <?php foreach ($lessons as $l) : ?>
                            <a class="card-body-sm row row-nowrap" style="color:inherit" href="<?= site_url('study/' . $l['course_slug'] . '/lesson/' . $l['id']) ?>">
                                <span class="feature-icon feature-icon-sm"><?= icon(lesson_icon($l['type'] ?? 'text')) ?></span>
                                <span class="grow"><span class="fw-600"><?= $mark($l['title']) ?></span><br><span class="text-xs text-muted"><?= esc($l['course_title']) ?><?= $l['summary'] ? ' · ' . $mark(excerpt($l['summary'], 110)) : '' ?></span></span>
                                <?= icon('chevron-right') ?>
                            </a>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endif ?>

            <?php if ($verses) : ?>
                <section class="card">
                    <div class="card-header"><h2><?= icon('bible') ?> Scripture</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('bible/search?q=' . rawurlencode($term)) ?>">All <?= number_format($verseCount) ?> verses<?= icon('arrow-right') ?></a></div>
                    <div class="divider-list">
                        <?php foreach ($verses as $v) : ?>
                            <a class="card-body-sm" style="color:inherit;display:block" href="<?= site_url('bible/' . \App\Libraries\Scripture::slug((int) $v['book_position']) . '/' . $v['chapter']) ?>#v<?= $v['verse'] ?>">
                                <span class="fw-600 text-sm" style="color:var(--brand)"><?= esc($v['book_name'] . ' ' . $v['chapter'] . ':' . $v['verse']) ?></span>
                                <span class="d-block reading-text"><?= $mark($v['text']) ?></span>
                            </a>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endif ?>

            <?php if ($journal) : ?>
                <section class="card">
                    <div class="card-header"><h2><?= icon('notebook') ?> Your journal</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('journal?q=' . rawurlencode($term)) ?>">Open journal<?= icon('arrow-right') ?></a></div>
                    <div class="divider-list">
                        <?php foreach ($journal as $j) : ?>
                            <a class="card-body-sm" style="color:inherit;display:block" href="<?= site_url('journal?q=' . rawurlencode($term)) ?>#entry-<?= $j['id'] ?>">
                                <span class="row between"><span class="fw-600 text-sm"><?= esc($j['title'] ?: ucfirst($j['kind'])) ?></span><span class="text-xs text-muted"><?= nice_date($j['entry_date'] ?: $j['created_at'], 'j M Y') ?></span></span>
                                <span class="d-block text-sm text-2"><?= $mark(excerpt(strip_tags((string) $j['body']), 180)) ?></span>
                            </a>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endif ?>

            <?php if ($products) : ?>
                <section>
                    <h2 class="h-sm"><?= icon('store') ?> Shop</h2>
                    <div class="grid grid-4"><?php foreach ($products as $product) : ?><?= view('partials/product_card', ['product' => $product]) ?><?php endforeach ?></div>
                </section>
            <?php endif ?>

            <?php if (! $total) : ?>
                <div class="card empty"><div class="feature-icon"><?= icon('search', 'icon-lg') ?></div><h3>No matches</h3><p>Check the spelling, try fewer words, or search for a Bible reference like “Philippians 4:6”.</p></div>
            <?php endif ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
