<?php
/** @var array $course row from CourseModel::catalog() */
$c        = $course;
$lessons  = (int) ($c['lesson_count'] ?? 0);
$minutes  = (int) ($c['total_minutes'] ?? 0);
$rating   = isset($c['avg_rating']) && $c['avg_rating'] !== null ? (float) $c['avg_rating'] : null;
$progress = $progress ?? null;
$href     = $href ?? site_url('courses/' . $c['slug']);
?>
<a class="card card-hover course-card" href="<?= $href ?>">
    <div class="course-cover" style="<?= course_cover_style($c) ?>">
        <?php if (! empty($c['thumbnail_path'])) : ?>
            <img src="<?= base_url($c['thumbnail_path']) ?>" alt="" loading="lazy">
        <?php else : ?>
            <div class="cover-art"><span><?= esc($c['title']) ?></span></div>
        <?php endif ?>
        <?php if (! empty($c['is_featured'])) : ?><span class="badge"><?= icon('sparkles') ?>Featured</span><?php endif ?>
    </div>
    <div class="course-body">
        <?php if (! empty($c['category_name'])) : ?><span class="eyebrow"><?= esc($c['category_name']) ?></span><?php endif ?>
        <h3 class="clamp-2"><?= esc($c['title']) ?></h3>
        <?php if (! empty($c['subtitle']) || ! empty($c['summary'])) : ?>
            <p class="text-sm text-2 clamp-2 mb-0"><?= esc($c['subtitle'] ?: $c['summary']) ?></p>
        <?php endif ?>
        <div class="course-meta">
            <?php if ($lessons) : ?><span><?= icon('layers') ?><?= $lessons ?> lessons</span><?php endif ?>
            <?php if ($minutes) : ?><span><?= icon('clock') ?><?= \App\Modules\Lms\Services\Curriculum::duration($minutes) ?></span><?php endif ?>
            <?php if ($rating) : ?><span><?= stars($rating) ?> <?= number_format($rating, 1) ?></span><?php endif ?>
        </div>
        <?php if ($progress !== null) : ?>
            <div class="progress mt-1"><span style="width:<?= (int) $progress ?>%"></span></div>
            <div class="row between text-xs text-muted"><span><?= (int) $progress ?>% complete</span><span class="text-brand fw-600">Continue <?= icon('arrow-right') ?></span></div>
        <?php else : ?>
            <div class="course-foot">
                <span class="text-sm text-muted"><?= icon('users') ?> <?= number_format((int) ($c['student_count'] ?? 0)) ?> learners</span>
                <span class="price <?= (int) $c['price_cents'] === 0 ? 'price-free' : '' ?>"><?= price_label((int) $c['price_cents'], $c['currency'] ?? null) ?></span>
            </div>
        <?php endif ?>
    </div>
</a>
