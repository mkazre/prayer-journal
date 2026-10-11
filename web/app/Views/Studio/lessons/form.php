<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><a href="<?= site_url('studio/courses/' . $course['id'] . '/curriculum') ?>"><?= esc($course['title']) ?></a><?= icon('chevron-right') ?><span><?= $lesson['id'] ? 'Edit lesson' : 'New lesson' ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Lms\Models\LessonModel;

$l       = $lesson;
$v       = static fn ($k) => esc((string) old($k, $l[$k] ?? ''), 'attr');
$kinds   = \App\Modules\Lms\Models\JournalEntryModel::KINDS;
$prompts = $settings['prompts'];
$typeIcons = ['text' => 'file-text', 'devotion' => 'feather', 'video' => 'play-circle', 'audio' => 'headphones', 'pdf' => 'file', 'embed' => 'monitor'];
?>
<form method="post" enctype="multipart/form-data" id="lesson-form">
    <?= csrf_field() ?>
    <div class="studio-head">
        <div><h1><?= $l['id'] ? esc($l['title']) : 'New lesson' ?></h1><p><?= esc($course['title']) ?></p></div>
        <div class="row">
            <?php if ($l['id']) : ?><a class="btn" href="<?= site_url('study/' . $course['slug'] . '/lesson/' . $l['id']) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview</a><?php endif ?>
            <a class="btn btn-ghost" href="<?= site_url('studio/courses/' . $course['id'] . '/curriculum') ?>">Cancel</a>
            <button class="btn btn-primary" type="submit"><?= icon('save') ?>Save</button>
        </div>
    </div>
    <div class="split">
        <div class="stack stack-lg">
            <section class="card card-body">
                <div class="field"><label class="label" for="title">Title</label><input class="input input-lg" id="title" name="title" value="<?= $v('title') ?>" required maxlength="200" autofocus placeholder="Lesson title"></div>
                <div class="field"><span class="label">Lesson type</span>
                    <div class="segmented" style="flex-wrap:wrap">
                        <?php foreach (LessonModel::TYPES as $k => $label) : ?><label><input type="radio" name="type" value="<?= $k ?>" <?= ($l['type'] ?: 'text') === $k ? 'checked' : '' ?>><span><?= icon($typeIcons[$k]) ?><?= $label ?></span></label><?php endforeach ?>
                    </div>
                </div>
                <div class="field"><label class="label" for="summary">Summary <span class="opt">(shown in the outline)</span></label><input class="input" id="summary" name="summary" value="<?= $v('summary') ?>" maxlength="500"></div>
                <div class="field"><label class="label" for="bref">Key scripture <span class="opt">(shown in full above the lesson)</span></label>
                    <div class="input-icon"><?= icon('book-open') ?><input class="input" id="bref" name="bible_reference" value="<?= $v('bible_reference') ?>" placeholder="e.g. Romans 8:26-27" autocomplete="off"></div>
                    <div id="bref-preview" class="panel mt-1" hidden style="font-family:var(--font-read)"></div>
                </div>
            </section>

            <section class="card card-body">
                <div class="row between mb-1"><h3 class="mb-0">Content</h3><span class="text-xs text-muted">Bible references like "John 3:16" become verse pop-ups automatically.</span></div>
                <textarea name="content" data-editor placeholder="Write your lesson…"><?= esc((string) old('content', $l['content'] ?? '')) ?></textarea>
            </section>

            <section class="card card-body">
                <h3>Media</h3>
                <div class="field"><label class="label" for="video">Video URL</label><input class="input" id="video" name="video_url" value="<?= $v('video_url') ?>" placeholder="YouTube, Vimeo or a direct .mp4 link"></div>
                <div class="field"><label class="label">Attachment <span class="opt">(PDF shows inline, audio gets a player; max 100 MB)</span></label>
                    <input class="sr-only" type="file" id="attachment" name="attachment" accept=".pdf,.mp3,.m4a,.mp4,.zip,.epub,image/*">
                    <div class="dropzone" data-for="attachment"><?= icon('paperclip') ?><span data-file-label data-default="Drop a file or click to upload">Drop a file or click to upload</span></div>
                    <?php if (! empty($l['attachment_path'])) : ?><div class="row between mt-1 text-sm"><a href="<?= base_url($l['attachment_path']) ?>" target="_blank" rel="noopener"><?= icon('file') ?> Current file</a><label class="check"><input type="checkbox" name="remove_attachment" value="1">Remove</label></div><?php endif ?>
                </div>
            </section>

            <section class="card card-body">
                <div class="row between"><h3 class="mb-0"><?= icon('feather') ?> Journaling prompts</h3><span class="text-xs text-muted"><?= $prompts ? count($prompts) . ' custom' : 'Using the course defaults (' . count($course_settings['default_prompts']) . ')' ?></span></div>
                <p class="help mb-2">Leave empty to use the course's default prompts. Answers save to each learner's private journal.</p>
                <div id="prompts-rep">
                    <div data-repeater-items>
                        <?php foreach ($prompts as $i => $p) : ?>
                            <div class="repeater-row"><div class="grow">
                                <div class="row row-nowrap gap-sm"><input class="input input-sm grow" name="prompts[<?= $i ?>][label]" value="<?= esc($p['label'], 'attr') ?>" placeholder="Prompt"><select class="select input-sm" name="prompts[<?= $i ?>][kind]" style="width:auto"><?php foreach ($kinds as $k => [$kl]) : ?><option value="<?= $k ?>" <?= ($p['kind'] ?? '') === $k ? 'selected' : '' ?>><?= $kl ?></option><?php endforeach ?></select></div>
                                <input class="input input-sm" name="prompts[<?= $i ?>][hint]" value="<?= esc((string) ($p['hint'] ?? ''), 'attr') ?>" placeholder="Helper text (optional)">
                            </div><button class="btn btn-ghost btn-icon btn-sm" type="button" data-repeater-remove aria-label="Remove"><?= icon('trash') ?></button></div>
                        <?php endforeach ?>
                    </div>
                    <template><div class="repeater-row"><div class="grow"><div class="row row-nowrap gap-sm"><input class="input input-sm grow" name="prompts[__i__][label]" placeholder="Prompt"><select class="select input-sm" name="prompts[__i__][kind]" style="width:auto"><?php foreach ($kinds as $k => [$kl]) : ?><option value="<?= $k ?>"><?= $kl ?></option><?php endforeach ?></select></div><input class="input input-sm" name="prompts[__i__][hint]" placeholder="Helper text (optional)"></div><button class="btn btn-ghost btn-icon btn-sm" type="button" data-repeater-remove aria-label="Remove"><?= icon('trash') ?></button></div></template>
                    <button class="btn btn-sm" type="button" data-repeater-add="prompts-rep"><?= icon('plus') ?>Add prompt</button>
                </div>
            </section>

            <section class="card card-body">
                <h3><?= icon('link') ?> Resources</h3>
                <div id="res-rep">
                    <div data-repeater-items>
                        <?php foreach ($settings['resources'] as $i => $r) : ?>
                            <div class="repeater-row"><input class="input input-sm" name="resources[<?= $i ?>][label]" value="<?= esc((string) $r['label'], 'attr') ?>" placeholder="Label" style="max-width:200px"><input class="input input-sm grow" name="resources[<?= $i ?>][url]" value="<?= esc((string) $r['url'], 'attr') ?>" placeholder="https://"><button class="btn btn-ghost btn-icon btn-sm" type="button" data-repeater-remove aria-label="Remove"><?= icon('trash') ?></button></div>
                        <?php endforeach ?>
                    </div>
                    <template><div class="repeater-row"><input class="input input-sm" name="resources[__i__][label]" placeholder="Label" style="max-width:200px"><input class="input input-sm grow" name="resources[__i__][url]" placeholder="https://"><button class="btn btn-ghost btn-icon btn-sm" type="button" data-repeater-remove aria-label="Remove"><?= icon('trash') ?></button></div></template>
                    <button class="btn btn-sm" type="button" data-repeater-add="res-rep"><?= icon('plus') ?>Add link</button>
                </div>
            </section>
        </div>

        <aside class="stack stack-lg sticky">
            <section class="card card-body stack">
                <div class="field"><span class="label">Status</span><div class="segmented" style="width:100%"><label class="grow"><input type="radio" name="status" value="published" <?= ($l['status'] ?? 'published') === 'published' ? 'checked' : '' ?>><span style="justify-content:center">Published</span></label><label class="grow"><input type="radio" name="status" value="draft" <?= ($l['status'] ?? '') === 'draft' ? 'checked' : '' ?>><span style="justify-content:center">Draft</span></label></div></div>
                <div class="field"><label class="label" for="section">Module</label><select class="select" id="section" name="section_id"><option value="">— None —</option><?php foreach ($sections as $s) : ?><option value="<?= $s['id'] ?>" <?= (int) $l['section_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['title']) ?></option><?php endforeach ?></select></div>
                <label class="switch"><input type="checkbox" name="is_preview" value="1" <?= ! empty($l['is_preview']) ? 'checked' : '' ?>><span class="track"></span>Free preview</label>
                <div class="field"><label class="label" for="dur">Duration (minutes)</label><input class="input" id="dur" type="number" min="0" name="duration_mins" value="<?= $v('duration_mins') ?>" placeholder="Auto from reading time"></div>
            </section>
            <section class="card card-body stack">
                <h3 class="mb-0"><?= icon('calendar') ?> Availability</h3>
                <div class="field"><label class="label" for="drip">Unlock after (days from enrolling)</label><input class="input" id="drip" type="number" min="0" name="drip_days" value="<?= $v('drip_days') ?>" placeholder="Immediately">
                    <span class="help"><?= $course_settings['drip'] === 'enrollment' ? 'Drip release is on for this course.' : 'Applies when drip release is switched on in course settings.' ?></span></div>
                <div class="field"><label class="label" for="avail">Not before (date)</label><input class="input" id="avail" type="datetime-local" name="available_at" value="<?= ! empty($l['available_at']) ? date('Y-m-d\TH:i', strtotime($l['available_at'])) : '' ?>"></div>
                <div class="field"><label class="label" for="price">Pay-per-lesson price (<?= esc(store_currency()) ?>)</label><input class="input" id="price" type="number" min="0" step="0.01" name="price" value="<?= number_format(((int) ($l['price_cents'] ?? 0)) / 100, 2, '.', '') ?>"><span class="help">Leave at 0 unless this single lesson is sold separately.</span></div>
            </section>
            <div class="stack stack-sm">
                <button class="btn btn-primary btn-block" type="submit"><?= icon('save') ?>Save lesson</button>
                <button class="btn btn-block" type="submit" name="after" value="new"><?= icon('plus') ?>Save &amp; add another</button>
                <button class="btn btn-ghost btn-block" type="submit" name="after" value="curriculum"><?= icon('layers') ?>Save &amp; back to curriculum</button>
            </div>
            <?php if ($l['id']) : ?>
                <button class="btn btn-danger-ghost btn-block" type="submit" form="delete-lesson"><?= icon('trash') ?>Delete lesson</button>
            <?php endif ?>
        </aside>
    </div>
</form>
<?php if ($l['id']) : ?><form id="delete-lesson" method="post" action="<?= site_url('studio/lessons/' . $l['id'] . '/delete') ?>" data-confirm="Delete this lesson? Learner progress for it will be lost."><?= csrf_field() ?></form><?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var input = document.getElementById('bref'), box = document.getElementById('bref-preview'), t;
    function load() {
        var ref = input.value.trim();
        if (!ref) { box.hidden = true; return; }
        PJ.get(<?= json_encode(site_url('bible/passage')) ?> + '?ref=' + encodeURIComponent(ref)).then(function (d) {
            box.hidden = false;
            box.innerHTML = '<div class="text-xs fw-700 text-accent mb-1"></div><div></div>';
            box.firstChild.textContent = d.reference + ' · KJV';
            box.lastChild.textContent = (d.verses || []).map(function (v) { return v.verse + ' ' + v.text; }).join(' ').slice(0, 600);
        }).catch(function () { box.hidden = false; box.innerHTML = '<span class="text-danger text-sm">Reference not recognised — try "John 3:16" or "Psalm 23".</span>'; });
    }
    input.addEventListener('input', function () { clearTimeout(t); t = setTimeout(load, 500); });
    if (input.value) { load(); }
})();
</script>
<?= $this->endSection() ?>
