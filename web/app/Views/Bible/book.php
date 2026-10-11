<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php use App\Libraries\Scripture; $slug = Scripture::slug((int) $book['position']); ?>
<div class="container container-narrow" style="max-width:900px">
    <div class="page-head">
        <nav class="breadcrumbs"><a href="<?= site_url('bible') ?>">Bible</a><?= icon('chevron-right') ?><span><?= esc(Scripture::groupFor((int) $book['position'])) ?></span></nav>
        <span class="eyebrow"><?= $book['testament'] === 'OT' ? 'Old Testament' : 'New Testament' ?> · Book <?= (int) $book['position'] ?> of 66</span>
        <h1 class="mt-1"><?= esc($book['name']) ?></h1>
        <p><?= (int) $book['chapter_count'] ?> chapters. Choose a chapter to start reading.</p>
    </div>
    <div class="chapter-grid">
        <?php for ($c = 1; $c <= (int) $book['chapter_count']; $c++) : ?><a href="<?= site_url('bible/' . $slug . '/' . $c) ?>"><?= $c ?></a><?php endfor ?>
    </div>
    <div class="row between mt-4">
        <?php if ($book['position'] > 1) : $p = $books[$book['position'] - 2]; ?><a class="btn" href="<?= site_url('bible/' . Scripture::slug((int) $p['position'])) ?>"><?= icon('arrow-left') ?><?= esc($p['name']) ?></a><?php else : ?><span></span><?php endif ?>
        <?php if ($book['position'] < 66) : $n = $books[$book['position']]; ?><a class="btn" href="<?= site_url('bible/' . Scripture::slug((int) $n['position'])) ?>"><?= esc($n['name']) ?><?= icon('arrow-right') ?></a><?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
