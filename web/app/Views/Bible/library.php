<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php use App\Libraries\Scripture; ?>
<div class="container container-narrow" style="max-width:900px">
    <div class="page-head">
        <nav class="breadcrumbs"><a href="<?= site_url('bible') ?>">Bible</a><?= icon('chevron-right') ?><span>My library</span></nav>
        <h1>My Bible library</h1>
        <p>Every verse you've highlighted, bookmarked or written about.</p>
    </div>
    <div class="tabs">
        <?php foreach (['highlights' => ['Highlights', 'highlighter'], 'bookmarks' => ['Bookmarks', 'bookmark'], 'notes' => ['Notes', 'note']] as $k => [$l, $ic]) : ?>
            <a class="tab <?= $tab === $k ? 'is-active' : '' ?>" href="<?= site_url('bible/library?tab=' . $k) ?>"><?= icon($ic) ?><?= $l ?> <span class="badge"><?= (int) $counts[$k] ?></span></a>
        <?php endforeach ?>
    </div>
    <?php if ($tab === 'highlights') : ?>
        <div class="row gap-sm mb-2">
            <a class="chip <?= $color === '' ? 'is-active' : '' ?>" href="<?= site_url('bible/library?tab=highlights') ?>">All colours</a>
            <?php foreach ($colors as $c) : ?><a class="chip <?= $color === $c ? 'is-active' : '' ?>" href="<?= site_url('bible/library?tab=highlights&color=' . $c) ?>"><span class="swatch" data-c="<?= $c ?>" style="width:1rem;height:1rem;box-shadow:none"></span><?= ucfirst($c) ?></a><?php endforeach ?>
        </div>
    <?php endif ?>
    <?php if ($items === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('book-open', 'icon-lg') ?></div><h3>Nothing saved yet</h3><p>While reading, tap a verse to highlight it, bookmark it or add a note.</p><a class="btn btn-primary" href="<?= site_url('bible') ?>">Open the Bible</a></div>
    <?php else : ?>
        <div class="card divider-list">
            <?php foreach ($items as $it) : $url = site_url('bible/' . Scripture::slug((int) $it['position']) . '/' . $it['chapter'] . '#v' . $it['verse']); ?>
                <div class="card-body" <?= ! empty($it['color']) ? 'style="border-left:4px solid var(--hl-' . esc($it['color'], 'attr') . ')"' : '' ?>>
                    <div class="row between"><a class="fw-700 text-sm" href="<?= $url ?>"><?= esc($it['book_name']) ?> <?= (int) $it['chapter'] ?>:<?= (int) $it['verse'] ?></a><span class="text-xs text-muted"><?= time_ago($it['created_at']) ?></span></div>
                    <p class="mb-0 mt-1" style="font-family:var(--reading-font);font-size:1.05rem"><?= esc($it['text']) ?></p>
                    <?php if (! empty($it['body'])) : ?>
                        <div class="panel mt-1 row items-start row-nowrap"><?= icon('note') ?><div class="grow text-sm" style="white-space:pre-wrap"><?= esc($it['body']) ?></div>
                            <form method="post" action="<?= site_url('bible/notes/' . $it['id'] . '/delete') ?>" data-ajax data-ajax-reload data-confirm="Delete this note?"><?= csrf_field() ?><button class="btn btn-ghost btn-icon btn-sm" type="submit" aria-label="Delete note"><?= icon('trash') ?></button></form>
                        </div>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
