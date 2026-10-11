<?= $this->extend('layouts/main') ?>
<?php
use App\Modules\Lms\Services\Curriculum;

$lessonCount = Curriculum::countItems($sections, 'lesson');
$quizCount   = Curriculum::countItems($sections, 'quiz');
$minutes     = Curriculum::totalMinutes($sections);
$free        = (int) $course['price_cents'] === 0 && ($course['access_mode'] ?? 'free') !== 'subscription';
$canBuy      = (int) $course['price_cents'] > 0 && ($course['access_mode'] ?? 'free') !== 'subscription';
$planLabel   = $cheapestPlan ? money((int) $cheapestPlan['price_cents'], $cheapestPlan['currency']) . ($cheapestPlan['type'] === 'subscription' ? '/' . ($cheapestPlan['billing_interval'] === 'year' ? 'year' : 'month') : '') : null;
$sellsModules = ! $free && ! $enrolled && array_filter($sections, static fn ($s) => $s['price_cents'] > 0) !== [];
$lines       = static fn (?string $t) => array_values(array_filter(array_map('trim', explode("\n", (string) $t))));
$firstPreview = null;
foreach (Curriculum::flatten($sections) as $it) { if ($it['kind'] === 'lesson' && $it['is_preview']) { $firstPreview = $it; break; } }
?>
<?= $this->section('hero') ?>
<section class="course-hero" style="--course-brand:<?= esc($settings['brand_color'], 'attr') ?>;--course-accent:<?= esc($settings['accent_color'], 'attr') ?>">
    <div class="container">
        <div class="split">
            <div>
                <nav class="breadcrumbs"><a href="<?= site_url('courses') ?>">Courses</a><?= icon('chevron-right') ?><?php if (! empty($course['category_slug'])) : ?><a href="<?= site_url('courses?category=' . $course['category_slug']) ?>"><?= esc($course['category_name']) ?></a><?= icon('chevron-right') ?><?php endif ?><span><?= esc($course['title']) ?></span></nav>
                <?php if ($course['status'] !== 'published') : ?><span class="badge badge-warning mb-1"><?= icon('eye-off') ?>Draft — only instructors can see this</span><?php endif ?>
                <h1><?= esc($course['title']) ?></h1>
                <p class="lead"><?= esc($course['subtitle'] ?: $course['summary']) ?></p>
                <div class="course-meta mt-2">
                    <?php if ($avgRating) : ?><span><strong style="color:var(--accent)"><?= number_format($avgRating, 1) ?></strong> <?= stars($avgRating) ?> (<?= (int) $course['review_count'] ?>)</span><?php endif ?>
                    <span><?= icon('users') ?><?= number_format((int) $course['student_count']) ?> learners</span>
                    <span><?= icon('layers') ?><?= $lessonCount ?> lessons</span>
                    <span><?= icon('clock') ?><?= Curriculum::duration($minutes) ?></span>
                    <span><?= icon('globe') ?><?= esc($course['language'] ?: 'English') ?></span>
                    <span><?= icon('target') ?><?= esc(\App\Modules\Lms\Models\CourseModel::LEVELS[$course['level']] ?? 'All levels') ?></span>
                </div>
                <?php if ($course['author_name']) : ?>
                    <div class="row mt-3"><?= avatar(null, $course['author_name']) ?><div><div class="text-sm" style="color:rgba(255,255,255,.7)">Created by</div><strong><?= esc($course['author_name']) ?></strong></div></div>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container mt-3">
    <div class="split">
        <div class="stack stack-lg">
            <?php if ($lines($course['what_you_learn'])) : ?>
                <section class="card card-body">
                    <h2 style="font-size:1.4rem">What you'll learn</h2>
                    <ul class="checklist learn-grid"><?php foreach ($lines($course['what_you_learn']) as $l) : ?><li><?= icon('check') ?><?= esc($l) ?></li><?php endforeach ?></ul>
                </section>
            <?php endif ?>

            <?php if ($course['intro_video_url'] && video_embed_url($course['intro_video_url'])) : ?>
                <div class="video-embed"><iframe src="<?= esc(video_embed_url($course['intro_video_url']), 'attr') ?>" title="Course introduction" allowfullscreen loading="lazy"></iframe></div>
            <?php endif ?>

            <section>
                <div class="section-head mb-2">
                    <h2 class="mb-0" style="font-size:1.4rem">Course content</h2>
                    <span class="text-sm text-muted"><?= count($sections) ?> modules · <?= $lessonCount ?> lessons<?= $quizCount ? ' · ' . $quizCount . ' quizzes' : '' ?> · <?= Curriculum::duration($minutes) ?></span>
                </div>
                <div class="accordion">
                    <?php foreach ($sections as $i => $s) : $mins = Curriculum::totalMinutes([$s]); ?>
                        <details <?= $i === 0 ? 'open' : '' ?>>
                            <summary>
                                <span class="qnum"><?= $i + 1 ?></span>
                                <div class="grow"><div><?= esc($s['title']) ?></div><div class="text-xs text-muted fw-500"><?= count($s['items']) ?> items · <?= Curriculum::duration($mins) ?></div></div>
                                <?php if ($ownedSections !== null && in_array((int) $s['id'], $ownedSections, true)) : ?>
                                    <span class="badge badge-success"><?= icon('check') ?>Yours</span>
                                <?php elseif ($sellsModules && $s['price_cents'] > 0) : ?>
                                    <span class="badge badge-accent"><?= money((int) $s['price_cents'], $course['currency']) ?></span>
                                <?php endif ?>
                                <?= icon('chevron-down', 'chev') ?>
                            </summary>
                            <div class="acc-body">
                                <?php if ($s['summary']) : ?><p class="text-sm text-2"><?= esc($s['summary']) ?></p><?php endif ?>
                                <?php if ($sellsModules && $s['price_cents'] > 0 && ! ($ownedSections !== null && in_array((int) $s['id'], $ownedSections, true))) : ?>
                                    <div class="row between mb-2 module-buy"><span class="text-sm">Just want this module? Get lifetime access to its <?= count($s['items']) ?> lessons and activities.</span><a class="btn btn-sm btn-soft" href="<?= site_url('checkout/module/' . $s['id']) ?>"><?= icon('unlock') ?>Buy module · <?= money((int) $s['price_cents'], $course['currency']) ?></a></div>
                                <?php endif ?>
                                <?php foreach ($s['items'] as $it) : ?>
                                    <div class="lesson-row">
                                        <?= icon(lesson_icon($it['type'])) ?>
                                        <span class="grow"><?= esc($it['title']) ?><?php if ($it['kind'] === 'lesson' && $it['bible_reference']) : ?> <span class="text-xs text-muted">· <?= esc($it['bible_reference']) ?></span><?php endif ?></span>
                                        <?php if ($it['kind'] === 'lesson' && $it['is_preview']) : ?>
                                            <a class="badge badge-brand" href="<?= site_url('courses/' . $course['slug'] . '/preview/' . $it['id']) ?>"><?= icon('eye') ?>Preview</a>
                                        <?php endif ?>
                                        <span class="text-xs text-muted nowrap"><?= $it['kind'] === 'quiz' ? (int) ($it['questions'] ?? 0) . ' questions' : ($it['duration'] ? $it['duration'] . ' min' : lesson_type_label($it['type'])) ?></span>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </details>
                    <?php endforeach ?>
                </div>
            </section>

            <?php if ($course['description']) : ?>
                <section class="card card-body">
                    <h2 style="font-size:1.4rem">About this course</h2>
                    <div class="prose" style="max-width:none;font-size:1rem"><?= scripture_links(clean_html($course['description'])) ?></div>
                </section>
            <?php endif ?>

            <?php if ($lines($course['requirements']) || $lines($course['audience'])) : ?>
                <div class="grid grid-2">
                    <?php if ($lines($course['requirements'])) : ?>
                        <section class="card card-body"><h3><?= icon('list-checks') ?> Before you start</h3><ul class="checklist"><?php foreach ($lines($course['requirements']) as $l) : ?><li><?= icon('chevron-right') ?><?= esc($l) ?></li><?php endforeach ?></ul></section>
                    <?php endif ?>
                    <?php if ($lines($course['audience'])) : ?>
                        <section class="card card-body"><h3><?= icon('users') ?> Who this is for</h3><ul class="checklist"><?php foreach ($lines($course['audience']) as $l) : ?><li><?= icon('chevron-right') ?><?= esc($l) ?></li><?php endforeach ?></ul></section>
                    <?php endif ?>
                </div>
            <?php endif ?>

            <?php if ($course['author_name']) : ?>
                <section class="card card-body">
                    <h2 style="font-size:1.4rem">About the author</h2>
                    <div class="row items-start row-nowrap gap-lg">
                        <?= avatar(null, $course['author_name'], 'avatar-xl') ?>
                        <div><h3 class="mb-1"><?= esc($course['author_name']) ?></h3><p class="text-2 mb-0"><?= esc((string) $course['author_bio']) ?></p></div>
                    </div>
                </section>
            <?php endif ?>

            <?php if ($settings['reviews']) : ?>
                <section id="reviews">
                    <h2 style="font-size:1.4rem">Reviews</h2>
                    <div class="card card-body">
                        <div class="row gap-xl items-start">
                            <div class="text-center" style="min-width:140px">
                                <div class="result-score" style="font-size:3.2rem"><?= $avgRating ? number_format($avgRating, 1) : '—' ?></div>
                                <?= stars($avgRating ?? 0) ?>
                                <div class="text-sm text-muted mt-1"><?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?></div>
                            </div>
                            <div class="grow stack stack-sm" style="min-width:220px">
                                <?php for ($s = 5; $s >= 1; $s--) : $pct = count($reviews) ? round($breakdown[$s] / count($reviews) * 100) : 0; ?>
                                    <div class="rating-bar"><span><?= $s ?> star</span><div class="progress"><span style="width:<?= $pct ?>%"></span></div><span class="text-right"><?= $pct ?>%</span></div>
                                <?php endfor ?>
                            </div>
                        </div>
                        <?php if ($enrolled) : ?>
                            <hr>
                            <form method="post" action="<?= site_url('courses/' . $course['slug'] . '/review') ?>">
                                <?= csrf_field() ?>
                                <div class="field"><span class="label"><?= $myReview ? 'Update your review' : 'Rate this course' ?></span>
                                    <div class="rating-input">
                                        <?php for ($s = 5; $s >= 1; $s--) : ?><input type="radio" id="r<?= $s ?>" name="rating" value="<?= $s ?>" <?= (int) ($myReview['rating'] ?? 0) === $s ? 'checked' : '' ?> required><label for="r<?= $s ?>" title="<?= $s ?> stars"><?= icon('star', 'icon-fill') ?></label><?php endfor ?>
                                    </div>
                                </div>
                                <div class="field"><textarea class="textarea" name="body" rows="3" placeholder="What did this course mean to you?"><?= esc((string) ($myReview['body'] ?? '')) ?></textarea></div>
                                <button class="btn btn-primary" type="submit"><?= icon('send') ?><?= $myReview ? 'Update review' : 'Post review' ?></button>
                            </form>
                        <?php endif ?>
                    </div>
                    <?php if ($reviews) : ?>
                        <div class="card mt-2 divider-list">
                            <?php foreach (array_slice($reviews, 0, 12) as $r) : ?>
                                <div class="card-body row items-start row-nowrap">
                                    <?= avatar((int) $r['user_id'], display_name(['id' => $r['user_id'], 'username' => $r['username']])) ?>
                                    <div><div class="row gap-sm"><strong><?= esc(display_name(['id' => $r['user_id'], 'username' => $r['username']])) ?></strong><?= stars((float) $r['rating']) ?><span class="text-xs text-muted"><?= time_ago($r['created_at']) ?></span></div>
                                        <?php if ($r['body']) : ?><p class="text-2 mb-0 mt-1"><?= esc($r['body']) ?></p><?php endif ?></div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </section>
            <?php endif ?>
        </div>

        <aside>
            <div class="card buy-card sticky" style="margin-top:-12rem">
                <div class="course-cover" style="<?= course_cover_style($course) ?>">
                    <?php if ($course['thumbnail_path']) : ?><img src="<?= base_url($course['thumbnail_path']) ?>" alt=""><?php else : ?><div class="cover-art"><span><?= esc($course['title']) ?></span></div><?php endif ?>
                </div>
                <div class="card-body stack">
                    <div class="row between">
                        <span class="price <?= $free ? 'price-free' : '' ?>" style="font-size:1.8rem"><?= ($course['access_mode'] ?? '') === 'subscription' ? 'Membership' : price_label((int) $course['price_cents'], $course['currency']) ?></span>
                        <?php if ($enrolled) : ?><span class="badge badge-success"><?= icon('check') ?>Enrolled</span><?php elseif ($inMembership && ($course['access_mode'] ?? '') !== 'subscription') : ?><span class="badge badge-brand"><?= icon('sparkles') ?>In membership</span><?php endif ?>
                    </div>
                    <?php if ($lapsed) : ?><div class="alert alert-warning text-sm"><?= icon('clock') ?><div>Your membership is not active, so this course is paused. Your progress is saved.</div></div><?php endif ?>
                    <?php if ($enrolled) : ?>
                        <div><div class="progress"><span style="width:<?= (int) $enrollment['progress_percent'] ?>%"></span></div><div class="text-xs text-muted mt-1"><?= (int) $enrollment['progress_percent'] ?>% complete</div></div>
                        <a class="btn btn-primary btn-lg btn-block" href="<?= site_url('study/' . $course['slug']) ?>"><?= icon('play') ?><?= (int) $enrollment['progress_percent'] > 0 ? 'Continue learning' : 'Start learning' ?></a>
                    <?php elseif ($full) : ?>
                        <button class="btn btn-lg btn-block" disabled>Course is full</button>
                    <?php elseif (auth()->loggedIn()) : ?>
                        <?php if ($ownedSections !== null) : ?>
                            <div class="text-sm">You own <strong><?= count($ownedSections) ?> of <?= count($sections) ?></strong> modules.</div>
                            <a class="btn btn-primary btn-lg btn-block" href="<?= site_url('study/' . $course['slug']) ?>"><?= icon('play') ?>Continue learning</a>
                        <?php endif ?>
                        <?php if ($free) : ?>
                            <form method="post" action="<?= site_url('courses/' . $course['slug'] . '/enroll') ?>"><?= csrf_field() ?><button class="btn btn-primary btn-lg btn-block" type="submit"><?= icon('sparkles') ?>Enroll for free</button></form>
                        <?php endif ?>
                        <?php if ($canBuy) : ?>
                            <a class="btn <?= $ownedSections === null ? 'btn-primary btn-lg' : '' ?> btn-block" href="<?= site_url('checkout/course/' . $course['id']) ?>"><?= icon('unlock') ?><?= $ownedSections === null ? 'Buy the course' : 'Unlock the full course' ?> · <?= money((int) $course['price_cents'], $course['currency']) ?></a>
                        <?php endif ?>
                        <?php if ($inMembership && $planLabel) : ?>
                            <a class="btn <?= $canBuy || $ownedSections !== null ? 'btn-soft' : 'btn-primary btn-lg' ?> btn-block" href="<?= site_url('pricing') ?>"><?= icon('sparkles') ?><?= $canBuy ? 'Or join membership' : 'Join membership' ?> · from <?= $planLabel ?></a>
                        <?php endif ?>
                        <?php if ($sellsModules && $ownedSections === null) : ?><p class="text-xs text-muted text-center mb-0">Prefer to start small? Each module can also be bought on its own (see Course content).</p><?php endif ?>
                    <?php else : ?>
                        <a class="btn btn-primary btn-lg btn-block" href="<?= site_url('register') ?>"><?= icon('sparkles') ?><?= $free ? 'Sign up to enroll free' : 'Sign up to get started' ?></a>
                        <a class="btn btn-block" href="<?= site_url('login') ?>">I already have an account</a>
                        <?php if ($inMembership && $planLabel) : ?><p class="text-xs text-muted text-center mb-0">Included in membership from <?= $planLabel ?><?= $canBuy ? ', or buy it once' : '' ?>.</p><?php endif ?>
                    <?php endif ?>
                    <?php if ($firstPreview && ! $enrolled) : ?>
                        <a class="btn btn-soft btn-block" href="<?= site_url('courses/' . $course['slug'] . '/preview/' . $firstPreview['id']) ?>"><?= icon('eye') ?>Preview a lesson</a>
                    <?php endif ?>
                    <ul class="checklist text-sm">
                        <li><?= icon('layers') ?><?= $lessonCount ?> lessons in <?= count($sections) ?> modules</li>
                        <?php if ($settings['journal']) : ?><li><?= icon('notebook') ?>Guided journaling prompts</li><?php endif ?>
                        <?php if ($quizCount) : ?><li><?= icon('list-checks') ?><?= $quizCount ?> review quizzes</li><?php endif ?>
                        <?php if ($settings['certificate_enabled']) : ?><li><?= icon('award') ?>Certificate of completion</li><?php endif ?>
                        <li><?= icon('smartphone') ?>Works on phone, tablet and desktop</li>
                        <li><?= icon('infinity') ?><?= $settings['access_days'] ? (int) $settings['access_days'] . ' days of access' : 'Lifetime access' ?></li>
                    </ul>
                    <div class="row center gap-sm">
                        <button class="btn btn-sm btn-ghost" type="button" data-share="<?= esc($course['title'], 'attr') ?>" data-share-title="<?= esc($course['title'], 'attr') ?>"><?= icon('share') ?>Share</button>
                        <?php if ($canEdit) : ?><a class="btn btn-sm btn-ghost" href="<?= site_url('studio/courses/' . $course['id']) ?>"><?= icon('edit') ?>Edit in Studio</a><?php endif ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <?php if ($related) : ?>
        <section class="section-sm">
            <h2 style="font-size:1.4rem">More like this</h2>
            <div class="grid grid-3"><?php foreach ($related as $c) : ?><?= view('partials/course_card', ['course' => $c]) ?><?php endforeach ?></div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
