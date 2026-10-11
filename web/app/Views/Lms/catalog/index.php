<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$levels = \App\Modules\Lms\Models\CourseModel::LEVELS;
$q      = static fn (array $over) => site_url('courses') . '?' . http_build_query(array_filter(array_merge($filters, $over)));
?>
<div class="page-head-band">
    <div class="container page-head">
        <span class="eyebrow"><?= icon('graduation') ?>Learn</span>
        <h1 class="mt-1">Courses</h1>
        <p>Guided teaching, daily devotions and journaling prompts — learn at your own pace, with progress saved on every device.</p>
        <form class="row mt-3" method="get" action="<?= site_url('courses') ?>" style="max-width:640px">
            <div class="input-icon grow"><?= icon('search') ?><input class="input input-lg" type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Search courses, topics or tags"></div>
            <?php foreach (['category', 'level', 'price', 'sort'] as $k) : if ($filters[$k] !== '') : ?><input type="hidden" name="<?= $k ?>" value="<?= esc($filters[$k], 'attr') ?>"><?php endif; endforeach ?>
            <button class="btn btn-primary btn-lg" type="submit">Search</button>
        </form>
    </div>
</div>
<div class="container">
    <div class="toolbar">
        <div class="chips">
            <a class="chip <?= $filters['category'] === '' ? 'is-active' : '' ?>" href="<?= $q(['category' => null]) ?>">All topics</a>
            <?php foreach ($categories as $cat) : ?>
                <a class="chip <?= $filters['category'] === $cat['slug'] ? 'is-active' : '' ?>" href="<?= $q(['category' => $cat['slug']]) ?>"><?= esc($cat['name']) ?></a>
            <?php endforeach ?>
        </div>
        <form class="row gap-sm" method="get" action="<?= site_url('courses') ?>">
            <?php foreach (['q', 'category'] as $k) : if ($filters[$k] !== '') : ?><input type="hidden" name="<?= $k ?>" value="<?= esc($filters[$k], 'attr') ?>"><?php endif; endforeach ?>
            <select class="select input-sm" name="level" data-autosubmit aria-label="Level" style="width:auto">
                <option value="">Any level</option>
                <?php foreach ($levels as $k => $l) : if ($k === 'all') { continue; } ?><option value="<?= $k ?>" <?= $filters['level'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?>
            </select>
            <select class="select input-sm" name="price" data-autosubmit aria-label="Price" style="width:auto">
                <option value="">Any price</option><option value="free" <?= $filters['price'] === 'free' ? 'selected' : '' ?>>Free</option><option value="paid" <?= $filters['price'] === 'paid' ? 'selected' : '' ?>>Paid</option>
            </select>
            <select class="select input-sm" name="sort" data-autosubmit aria-label="Sort" style="width:auto">
                <?php foreach (['popular' => 'Most popular', 'newest' => 'Newest', 'rating' => 'Highest rated', 'title' => 'A–Z'] as $k => $l) : ?><option value="<?= $k ?>" <?= $filters['sort'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?>
            </select>
        </form>
    </div>
    <p class="text-sm text-muted"><?= count($courses) ?> course<?= count($courses) === 1 ? '' : 's' ?><?= $filters['q'] ? ' for “' . esc($filters['q']) . '”' : '' ?></p>
    <?php if ($courses === []) : ?>
        <div class="card empty">
            <div class="feature-icon"><?= icon('search', 'icon-lg') ?></div>
            <h3>No courses found</h3><p>Try a different search or clear the filters.</p>
            <a class="btn" href="<?= site_url('courses') ?>">Clear filters</a>
        </div>
    <?php else : ?>
        <div class="grid grid-3">
            <?php foreach ($courses as $course) : ?>
                <?= view('partials/course_card', ['course' => $course, 'progress' => $mine[(int) $course['id']] ?? null, 'href' => isset($mine[(int) $course['id']]) ? site_url('study/' . $course['slug']) : null]) ?>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
