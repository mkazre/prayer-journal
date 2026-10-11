<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><a href="<?= site_url('studio/courses/' . $course['id']) ?>"><?= esc($course['title']) ?></a><?= icon('chevron-right') ?><span>Settings</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Studio/courses/_header', ['course' => $course, 'tab' => $tab]) ?>
<?php
$s  = $settings;
$sw = static fn (string $name, string $label, string $help = '') => '<label class="switch" style="align-items:flex-start"><input type="checkbox" name="' . $name . '" value="1"' . (! empty($s[$name]) ? ' checked' : '') . '><span class="track"></span><span><strong class="fw-600">' . esc($label) . '</strong>' . ($help ? '<br><span class="text-sm text-muted">' . esc($help) . '</span>' : '') . '</span></label>';
$kinds = \App\Modules\Lms\Models\JournalEntryModel::KINDS;
?>
<form method="post">
    <?= csrf_field() ?>
    <div class="grid grid-2" style="align-items:start">
        <div class="stack stack-lg">
            <section class="card card-body stack">
                <h3 class="mb-0"><?= icon('target') ?> Progression &amp; completion</h3>
                <?= $sw('sequential', 'Sequential learning', 'Learners must complete each step before the next one unlocks.') ?>
                <div class="field"><span class="label">Release schedule (drip)</span>
                    <label class="radio-card"><input type="radio" name="drip" value="none" <?= $s['drip'] === 'none' ? 'checked' : '' ?>><span><strong>Everything available immediately</strong><br><span class="text-sm text-muted">Self-paced.</span></span></label>
                    <label class="radio-card"><input type="radio" name="drip" value="enrollment" <?= $s['drip'] === 'enrollment' ? 'checked' : '' ?>><span><strong>Release on a schedule after enrolling</strong><br><span class="text-sm text-muted">Each lesson's "unlock after N days" setting applies — e.g. one devotion a day.</span></span></label>
                </div>
                <div class="field"><span class="label">A course is complete when the learner has…</span>
                    <div class="segmented"><label><input type="radio" name="completion" value="lessons" <?= $s['completion'] === 'lessons' ? 'checked' : '' ?>><span>Finished every lesson</span></label><label><input type="radio" name="completion" value="lessons_quizzes" <?= $s['completion'] === 'lessons_quizzes' ? 'checked' : '' ?>><span>Finished lessons and passed every quiz</span></label></div>
                </div>
                <div class="form-grid">
                    <div class="field"><label class="label" for="limit">Enrollment limit</label><input class="input" id="limit" name="enrollment_limit" type="number" min="1" value="<?= esc((string) $s['enrollment_limit'], 'attr') ?>" placeholder="Unlimited"></div>
                    <div class="field"><label class="label" for="days">Access length (days)</label><input class="input" id="days" name="access_days" type="number" min="1" value="<?= esc((string) $s['access_days'], 'attr') ?>" placeholder="Lifetime"></div>
                </div>
            </section>
            <section class="card card-body stack">
                <h3 class="mb-0"><?= icon('notebook') ?> Learning experience</h3>
                <?= $sw('journal', 'Journaling prompts', 'Show prompts under each lesson; answers save to the learner\'s private Prayer Journal.') ?>
                <?= $sw('discussions', 'Lesson discussions', 'Learners can comment and reply under each lesson.') ?>
                <?= $sw('reviews', 'Course reviews', 'Enrolled learners can rate and review the course.') ?>
                <?= $sw('show_progress', 'Show progress to learners') ?>
                <?= $sw('preview_public', 'Free previews for visitors', 'Lessons marked "preview" can be read without an account.') ?>
            </section>
            <section class="card card-body">
                <h3><?= icon('feather') ?> Default journaling prompts</h3>
                <p class="help">Shown under every lesson that doesn't define its own prompts.</p>
                <div id="prompts-rep">
                    <div data-repeater-items>
                        <?php foreach ($s['default_prompts'] ?: [] as $i => $p) : ?>
                            <div class="repeater-row"><div class="grow">
                                <div class="row row-nowrap gap-sm"><input class="input input-sm grow" name="prompts[<?= $i ?>][label]" value="<?= esc($p['label'], 'attr') ?>" placeholder="Prompt title">
                                    <select class="select input-sm" name="prompts[<?= $i ?>][kind]" style="width:auto"><?php foreach ($kinds as $k => [$l]) : ?><option value="<?= $k ?>" <?= ($p['kind'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></div>
                                <input class="input input-sm" name="prompts[<?= $i ?>][hint]" value="<?= esc((string) ($p['hint'] ?? ''), 'attr') ?>" placeholder="Helper text or scripture (optional)">
                            </div><button class="btn btn-ghost btn-icon btn-sm" type="button" data-repeater-remove aria-label="Remove"><?= icon('trash') ?></button></div>
                        <?php endforeach ?>
                    </div>
                    <template>
                        <div class="repeater-row"><div class="grow">
                            <div class="row row-nowrap gap-sm"><input class="input input-sm grow" name="prompts[__i__][label]" placeholder="Prompt title"><select class="select input-sm" name="prompts[__i__][kind]" style="width:auto"><?php foreach ($kinds as $k => [$l]) : ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach ?></select></div>
                            <input class="input input-sm" name="prompts[__i__][hint]" placeholder="Helper text or scripture (optional)">
                        </div><button class="btn btn-ghost btn-icon btn-sm" type="button" data-repeater-remove aria-label="Remove"><?= icon('trash') ?></button></div>
                    </template>
                    <button class="btn btn-sm" type="button" data-repeater-add="prompts-rep"><?= icon('plus') ?>Add prompt</button>
                </div>
            </section>
        </div>
        <div class="stack stack-lg">
            <section class="card card-body stack">
                <h3 class="mb-0"><?= icon('award') ?> Certificate</h3>
                <?= $sw('certificate_enabled', 'Issue a certificate on completion') ?>
                <div class="field"><label class="label">Certificate title</label><input class="input" name="certificate_title" value="<?= esc($s['certificate_title'], 'attr') ?>"></div>
                <div class="field"><label class="label">Wording</label><input class="input" name="certificate_message" value="<?= esc($s['certificate_message'], 'attr') ?>"><span class="help">Shown as "[Name] <em>has completed</em> [Course]".</span></div>
                <div class="form-grid">
                    <div class="field"><label class="label">Signed by</label><input class="input" name="certificate_signatory" value="<?= esc($s['certificate_signatory'], 'attr') ?>"></div>
                    <div class="field"><label class="label">Their role</label><input class="input" name="certificate_role" value="<?= esc($s['certificate_role'], 'attr') ?>"></div>
                </div>
            </section>
            <section class="card card-body">
                <h3><?= icon('palette') ?> Appearance</h3>
                <div class="form-grid">
                    <div class="field"><label class="label">Brand colour</label><div class="row row-nowrap gap-sm"><input class="color-input" type="color" name="brand_color" value="<?= esc($s['brand_color'], 'attr') ?>"><span class="text-sm text-muted">Hero, cover and certificate</span></div></div>
                    <div class="field"><label class="label">Accent colour</label><div class="row row-nowrap gap-sm"><input class="color-input" type="color" name="accent_color" value="<?= esc($s['accent_color'], 'attr') ?>"><span class="text-sm text-muted">Highlights and seal</span></div></div>
                </div>
                <div class="course-cover" style="border-radius:12px;aspect-ratio:21/9;<?= course_cover_style($course) ?>"><div class="cover-art"><span><?= esc($course['title']) ?></span></div></div>
            </section>
            <section class="card card-body">
                <h3><?= icon('message') ?> Messages to learners</h3>
                <div class="field"><label class="label">Welcome message</label><textarea class="textarea" name="welcome_message" rows="3" maxlength="1000"><?= esc($s['welcome_message']) ?></textarea></div>
                <div class="field"><label class="label">Completion message</label><textarea class="textarea" name="completion_message" rows="3" maxlength="1000"><?= esc($s['completion_message']) ?></textarea></div>
            </section>
            <button class="btn btn-primary btn-lg" type="submit"><?= icon('save') ?>Save settings</button>
        </div>
    </div>
</form>
<?= $this->endSection() ?>
