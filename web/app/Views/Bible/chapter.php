<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= esc($book['name']) ?> <?= esc($chapter) ?></h1>
<p>
    <?php if ($chapter > 1) : ?><a href="<?= site_url('bible/' . $book['abbrev'] . '/' . ($chapter - 1)) ?>">&larr; Prev</a> | <?php endif ?>
    <?php if ($chapter < $book['chapter_count']) : ?><a href="<?= site_url('bible/' . $book['abbrev'] . '/' . ($chapter + 1)) ?>">Next &rarr;</a><?php endif ?>
</p>

<div class="pj-card">
    <?php foreach ($verses as $v) :
        $color = $highlights[$v['id']] ?? null;
        $bookmarked = in_array($v['id'], $bookmarks); ?>
        <p style="<?= $color ? 'background:' . esc($color) . '33;padding:.25rem;border-radius:4px' : '' ?>">
            <sup><?= esc($v['verse']) ?></sup> <?= esc($v['text']) ?>
            <?php if (auth()->loggedIn()) : ?>
                <form style="display:inline" method="post" action="<?= site_url('bible/verses/' . $v['id'] . '/bookmark') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" style="border:none;background:none;cursor:pointer" title="Bookmark"><?= $bookmarked ? '★' : '☆' ?></button>
                </form>
                <form style="display:inline" method="post" action="<?= site_url('bible/verses/' . $v['id'] . '/highlight') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="color" value="<?= $color ? '' : 'gold' ?>">
                    <button type="submit" style="border:none;background:none;cursor:pointer" title="Highlight">🖍</button>
                </form>
            <?php endif ?>
        </p>
    <?php endforeach ?>
    <?php if (empty($verses)) : ?>
        <p>No verses found for this chapter (Bible text may not be imported yet).</p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
