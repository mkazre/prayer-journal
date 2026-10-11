<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\Scripture;

$words = [];
if ($term !== '') {
    $words = preg_match('/^"(.+)"$/u', $term, $m) ? [$m[1]] : array_filter(preg_split('/\s+/u', $term));
}
$mark = static function (string $text) use ($words): string {
    $out = esc($text);
    foreach ($words as $w) {
        $out = preg_replace('/(' . preg_quote(esc($w), '/') . ')/iu', '<mark>$1</mark>', $out);
    }

    return $out;
};
$q = static fn (array $over) => site_url('bible/search') . '?' . http_build_query(array_filter(array_merge(['q' => $term, 't' => $testament, 'book' => $bookSlug], $over)));
?>
<div class="container container-narrow" style="max-width:900px">
    <div class="page-head">
        <nav class="breadcrumbs"><a href="<?= site_url('bible') ?>">Bible</a><?= icon('chevron-right') ?><span>Search</span></nav>
        <h1>Search the Bible</h1>
        <form class="row mt-2" method="get" action="<?= site_url('bible/search') ?>">
            <div class="input-icon grow"><?= icon('search') ?><input class="input input-lg" type="search" name="q" value="<?= esc($term, 'attr') ?>" placeholder='Words, "an exact phrase", or a reference' autofocus></div>
            <select class="select input-lg" name="t" style="width:auto"><option value="">Whole Bible</option><option value="OT" <?= $testament === 'OT' ? 'selected' : '' ?>>Old Testament</option><option value="NT" <?= $testament === 'NT' ? 'selected' : '' ?>>New Testament</option></select>
            <select class="select input-lg" name="book" style="width:auto;max-width:180px"><option value="">Any book</option>
                <?php foreach ($books as $b) : $s = Scripture::slug((int) $b['position']); ?><option value="<?= $s ?>" <?= $bookSlug === $s ? 'selected' : '' ?>><?= esc($b['name']) ?></option><?php endforeach ?>
            </select>
            <button class="btn btn-primary btn-lg" type="submit">Search</button>
        </form>
        <p class="text-sm text-muted mt-1">Tip: wrap words in quotes for an exact phrase — <a href="<?= $q(['q' => '"the Lord is my shepherd"', 't' => null, 'book' => null]) ?>">"the Lord is my shepherd"</a></p>
    </div>

    <?php if ($direct) : ?>
        <a class="card card-hover card-body row mb-3" href="<?= Scripture::url($direct) ?>" style="color:inherit">
            <span class="feature-icon" style="margin:0"><?= icon('book-open', 'icon-lg') ?></span>
            <div class="grow"><div class="text-sm text-muted">Go to passage</div><strong style="font-size:1.15rem"><?= esc($direct['label']) ?></strong></div><?= icon('arrow-right') ?>
        </a>
    <?php endif ?>

    <?php if ($term !== '') : ?>
        <p class="text-sm text-muted"><?= number_format($total) ?> verse<?= $total === 1 ? '' : 's' ?> found<?= $pages > 1 ? ' · page ' . $page . ' of ' . $pages : '' ?></p>
        <?php if ($results === [] && ! $direct) : ?>
            <div class="card empty"><div class="feature-icon"><?= icon('search', 'icon-lg') ?></div><h3>No verses found</h3><p>Try fewer words, or check the spelling (the KJV uses words like "thee" and "saith").</p></div>
        <?php else : ?>
            <div class="card divider-list">
                <?php foreach ($results as $r) : $url = site_url('bible/' . Scripture::slug((int) $r['book_position']) . '/' . $r['chapter'] . '#v' . $r['verse']); ?>
                    <a class="card-body search-hit" href="<?= $url ?>" style="color:inherit;display:block">
                        <div class="fw-700 text-brand text-sm mb-1"><?= esc($r['book_name']) ?> <?= (int) $r['chapter'] ?>:<?= (int) $r['verse'] ?></div>
                        <div style="font-family:var(--reading-font);font-size:1.05rem;line-height:1.65"><?= $mark($r['text']) ?></div>
                    </a>
                <?php endforeach ?>
            </div>
            <?php if ($pages > 1) : ?>
                <nav class="pagination">
                    <?php if ($page > 1) : ?><a href="<?= $q(['page' => $page - 1]) ?>"><?= icon('chevron-left') ?></a><?php endif ?>
                    <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++) : ?><a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= $q(['page' => $i]) ?>"><?= $i ?></a><?php endfor ?>
                    <?php if ($page < $pages) : ?><a href="<?= $q(['page' => $page + 1]) ?>"><?= icon('chevron-right') ?></a><?php endif ?>
                </nav>
            <?php endif ?>
        <?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
