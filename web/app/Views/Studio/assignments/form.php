<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><a href="<?= site_url('studio/courses/' . $course['id'] . '/curriculum') ?>"><?= esc($course['title']) ?></a><?= icon('chevron-right') ?><span><?= $assignment['id'] ? 'Edit assignment' : 'New assignment' ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $a = $assignment; ?>
<form method="post">
    <?= csrf_field() ?>
    <div class="studio-head">
        <div><h1><?= $a['id'] ? esc($a['title']) : 'New assignment' ?></h1><p><?= esc($course['title']) ?><?= $a['id'] ? ' · ' . $submissions . ' submission' . ($submissions === 1 ? '' : 's') : '' ?></p></div>
        <div class="row"><?php if ($a['id']) : ?><a class="btn" href="<?= site_url('studio/grading') ?>"><?= icon('list-checks') ?>Grade submissions</a><?php endif ?><button class="btn btn-primary" type="submit"><?= icon('save') ?>Save</button></div>
    </div>
    <div class="split">
        <section class="card card-body">
            <div class="field"><label class="label" for="title">Title</label><input class="input input-lg" id="title" name="title" value="<?= esc((string) old('title', $a['title']), 'attr') ?>" required maxlength="200"></div>
            <div class="field"><label class="label" for="ins">Instructions</label><textarea class="textarea" id="ins" name="instructions" rows="12"><?= esc((string) old('instructions', $a['instructions'] ?? '')) ?></textarea><span class="help">Plain text; line breaks are kept and Bible references become verse pop-ups.</span></div>
        </section>
        <aside class="card card-body stack">
            <div class="field"><label class="label" for="section">Module</label><select class="select" id="section" name="section_id"><option value="">— None —</option><?php foreach ($sections as $s) : ?><option value="<?= $s['id'] ?>" <?= (int) $a['section_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['title']) ?></option><?php endforeach ?></select></div>
            <div class="field"><label class="label" for="pts">Points</label><input class="input" id="pts" type="number" min="1" name="max_points" value="<?= (int) $a['max_points'] ?>"></div>
            <div class="field"><label class="label" for="due">Due date <span class="opt">(optional)</span></label><input class="input" id="due" type="datetime-local" name="due_at" value="<?= $a['due_at'] ? date('Y-m-d\TH:i', strtotime($a['due_at'])) : '' ?>"></div>
            <label class="switch"><input type="checkbox" name="allow_file" value="1" <?= $a['allow_file'] ? 'checked' : '' ?>><span class="track"></span>Allow file uploads</label>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('save') ?>Save assignment</button>
            <?php if ($a['id']) : ?><button class="btn btn-danger-ghost btn-block" type="submit" form="del-assignment"><?= icon('trash') ?>Delete</button><?php endif ?>
        </aside>
    </div>
</form>
<?php if ($a['id']) : ?><form id="del-assignment" method="post" action="<?= site_url('studio/assignments/' . $a['id'] . '/delete') ?>" data-confirm="Delete this assignment and its submissions?"><?= csrf_field() ?></form><?php endif ?>
<?= $this->endSection() ?>
