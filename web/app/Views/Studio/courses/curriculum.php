<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><a href="<?= site_url('studio/courses/' . $course['id']) ?>"><?= esc($course['title']) ?></a><?= icon('chevron-right') ?><span>Curriculum</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Studio/courses/_header', ['course' => $course, 'tab' => $tab]) ?>
<?php use App\Modules\Lms\Services\Curriculum; ?>
<div class="row between mb-2">
    <p class="text-sm text-muted mb-0"><?= icon('grip') ?> Drag items by the handle to reorder them or move them between modules (keyboard: focus a handle and press Alt + ↑/↓). Changes save automatically.
        · <?= Curriculum::countItems($sections, 'lesson') ?> lessons · <?= Curriculum::countItems($sections, 'quiz') ?> quizzes · <?= Curriculum::countItems($sections, 'assignment') ?> assignments · <?= Curriculum::duration(Curriculum::totalMinutes($sections)) ?></p>
    <div class="row gap-sm">
        <button class="btn btn-sm btn-ghost" type="button" onclick="document.querySelectorAll('.builder-items').forEach(function(b){b.hidden=!b.hidden})"><?= icon('layers') ?>Collapse / expand</button>
        <button class="btn btn-primary btn-sm" type="button" data-modal-open="new-section"><?= icon('plus') ?>Add module</button>
    </div>
</div>

<div id="builder" data-sortable="sections" data-reorder-url="<?= site_url('studio/courses/' . $course['id'] . '/reorder') ?>">
    <?php foreach ($sections as $si => $s) : ?>
        <section class="builder-section" data-id="<?= (int) $s['id'] ?>" draggable="true">
            <div class="builder-section-head">
                <?php if ($s['id']) : ?><span class="drag-handle" tabindex="0" title="Drag to reorder module" aria-label="Reorder module"><?= icon('grip') ?></span><?php endif ?>
                <span class="qnum"><?= $si + 1 ?></span>
                <div class="grow"><h3><?= esc($s['title']) ?><?php if (! empty($s['price_cents'])) : ?> <span class="badge badge-accent" title="Sold on its own"><?= money((int) $s['price_cents']) ?></span><?php endif ?></h3><?php if ($s['summary']) : ?><div class="text-xs text-muted"><?= esc($s['summary']) ?></div><?php endif ?></div>
                <span class="text-xs text-muted hide-sm"><?= count($s['items']) ?> items · <?= Curriculum::duration(Curriculum::totalMinutes([$s])) ?></span>
                <div class="dropdown" data-dropdown>
                    <button class="btn btn-sm" type="button" data-dropdown-toggle><?= icon('plus') ?>Add</button>
                    <div class="dropdown-menu">
                        <a class="menu-item" href="<?= site_url('studio/courses/' . $course['id'] . '/lessons/new?section=' . $s['id']) ?>"><?= icon('file-text') ?>Lesson</a>
                        <a class="menu-item" href="<?= site_url('studio/courses/' . $course['id'] . '/lessons/new?type=devotion&section=' . $s['id']) ?>"><?= icon('feather') ?>Devotion</a>
                        <a class="menu-item" href="<?= site_url('studio/courses/' . $course['id'] . '/lessons/new?type=video&section=' . $s['id']) ?>"><?= icon('play-circle') ?>Video lesson</a>
                        <a class="menu-item" href="<?= site_url('studio/courses/' . $course['id'] . '/quizzes/new?section=' . $s['id']) ?>"><?= icon('list-checks') ?>Quiz</a>
                        <a class="menu-item" href="<?= site_url('studio/courses/' . $course['id'] . '/assignments/new?section=' . $s['id']) ?>"><?= icon('pen') ?>Assignment</a>
                    </div>
                </div>
                <?php if ($s['id']) : ?>
                    <div class="dropdown" data-dropdown>
                        <button class="btn btn-sm btn-ghost btn-icon" type="button" data-dropdown-toggle aria-label="Module options"><?= icon('more') ?></button>
                        <div class="dropdown-menu">
                            <button class="menu-item" type="button" data-modal-open="section-<?= $s['id'] ?>"><?= icon('edit') ?>Rename</button>
                            <form method="post" action="<?= site_url('studio/sections/' . $s['id'] . '/delete') ?>" data-confirm="Delete this module? Its lessons will move to the previous module."><?= csrf_field() ?><button class="menu-item danger" type="submit"><?= icon('trash') ?>Delete module</button></form>
                        </div>
                    </div>
                <?php endif ?>
            </div>
            <div class="builder-items" data-sortable="items">
                <?php foreach ($s['items'] as $it) :
                    $editUrl = site_url('studio/' . ['lesson' => 'lessons', 'quiz' => 'quizzes', 'assignment' => 'assignments'][$it['kind']] . '/' . $it['id']); ?>
                    <div class="builder-item" data-id="<?= $it['id'] ?>" data-kind="<?= $it['kind'] ?>" draggable="true">
                        <span class="drag-handle" tabindex="0" title="Drag to reorder" aria-label="Reorder <?= esc($it['title'], 'attr') ?>"><?= icon('grip') ?></span>
                        <span class="type-icon <?= $it['kind'] ?>"><?= icon(lesson_icon($it['type'])) ?></span>
                        <a class="grow fw-500" href="<?= $editUrl ?>" style="color:inherit"><?= esc($it['title']) ?>
                            <span class="text-xs text-muted fw-400"><?= lesson_type_label($it['type']) ?><?= $it['duration'] ? ' · ' . $it['duration'] . ' min' : '' ?><?= ! empty($it['bible_reference']) ? ' · ' . esc($it['bible_reference']) : '' ?><?= $it['kind'] === 'quiz' ? ' · ' . (int) $it['questions'] . ' questions' : '' ?></span></a>
                        <?php if ($it['status'] !== 'published') : ?><span class="badge badge-warning">Draft</span><?php endif ?>
                        <?php if ($it['is_preview']) : ?><span class="badge badge-brand hide-sm"><?= icon('eye') ?>Preview</span><?php endif ?>
                        <?php if ($it['drip_days'] !== null && $settings['drip'] === 'enrollment') : ?><span class="badge hide-sm"><?= icon('calendar') ?>Day <?= $it['drip_days'] + 1 ?></span><?php endif ?>
                        <?php if (! empty($it['price_cents'])) : ?><span class="badge badge-accent hide-sm"><?= money($it['price_cents']) ?></span><?php endif ?>
                        <div class="dropdown" data-dropdown>
                            <button class="btn btn-ghost btn-icon btn-sm" type="button" data-dropdown-toggle aria-label="Item options"><?= icon('more') ?></button>
                            <div class="dropdown-menu">
                                <a class="menu-item" href="<?= $editUrl ?>"><?= icon('edit') ?>Edit</a>
                                <?php if ($it['kind'] === 'lesson') : ?>
                                    <a class="menu-item" href="<?= site_url('study/' . $course['slug'] . '/lesson/' . $it['id']) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview</a>
                                    <form method="post" action="<?= site_url('studio/lessons/' . $it['id'] . '/duplicate') ?>"><?= csrf_field() ?><button class="menu-item" type="submit"><?= icon('copy-plus') ?>Duplicate</button></form>
                                <?php endif ?>
                                <form method="post" action="<?= $editUrl . '/delete' ?>" data-confirm="Delete “<?= esc($it['title'], 'attr') ?>”? This cannot be undone."><?= csrf_field() ?><button class="menu-item danger" type="submit"><?= icon('trash') ?>Delete</button></form>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </section>

        <?php if ($s['id']) : ?>
            <dialog class="modal" id="section-<?= $s['id'] ?>">
                <form method="post" action="<?= site_url('studio/sections/' . $s['id']) ?>">
                    <?= csrf_field() ?>
                    <div class="modal-head"><h3>Edit module</h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
                    <div class="modal-body">
                        <div class="field"><label class="label">Title</label><input class="input" name="title" value="<?= esc($s['title'], 'attr') ?>" required maxlength="200"></div>
                        <div class="field"><label class="label">Summary <span class="opt">(shown to learners)</span></label><textarea class="textarea" name="summary" rows="3"><?= esc((string) $s['summary']) ?></textarea></div>
                        <div class="field"><label class="label">Sell this module on its own (<?= esc(store_currency()) ?>)</label><input class="input" name="price" type="number" min="0" step="0.01" value="<?= number_format((int) ($s['price_cents'] ?? 0) / 100, 2, '.', '') ?>"><span class="help">Leave at 0 to sell only with the full course or membership. Buyers get lifetime access to this module's lessons.</span></div>
                    </div>
                    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
                </form>
            </dialog>
        <?php endif ?>
    <?php endforeach ?>
</div>

<?php if ($sections === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('layers', 'icon-lg') ?></div><h3>Start with a module</h3><p>Modules group your lessons — like chapters in a book.</p><button class="btn btn-primary" type="button" data-modal-open="new-section"><?= icon('plus') ?>Add module</button></div>
<?php endif ?>

<dialog class="modal" id="new-section">
    <form method="post" action="<?= site_url('studio/courses/' . $course['id'] . '/sections') ?>">
        <?= csrf_field() ?>
        <div class="modal-head"><h3>New module</h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
        <div class="modal-body">
            <div class="field"><label class="label">Title</label><input class="input" name="title" required maxlength="200" placeholder="e.g. Week 1 · Learning to listen" autofocus></div>
            <div class="field"><label class="label">Summary <span class="opt">(optional)</span></label><textarea class="textarea" name="summary" rows="3"></textarea></div>
        </div>
        <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('plus') ?>Add module</button></div>
    </form>
</dialog>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var builder = document.getElementById('builder');
    var save = function () {
        var sections = Array.prototype.map.call(builder.querySelectorAll(':scope > .builder-section'), function (sec) {
            return {
                id: parseInt(sec.getAttribute('data-id'), 10) || null,
                items: Array.prototype.map.call(sec.querySelectorAll('.builder-items > [data-id]'), function (it) { return { kind: it.getAttribute('data-kind'), id: parseInt(it.getAttribute('data-id'), 10) }; })
            };
        });
        builder.querySelectorAll(':scope > .builder-section .qnum').forEach(function (n, i) { n.textContent = i + 1; });
        PJ.post(builder.getAttribute('data-reorder-url'), { sections: sections }).then(function () { PJ.toast('Order saved'); }).catch(function (e) { PJ.toast(e.message, 'error'); });
    };
    builder.addEventListener('pj:sorted', save);
    // Items can be dropped into another module: route dragover on an item list to that list.
    builder.querySelectorAll('.builder-items').forEach(function (list) {
        list.addEventListener('dragover', function (e) { if (PJ._dragging && PJ._dragGroup === 'items' && PJ._dragging.parentElement !== list && !list.querySelector('[data-id]')) { e.preventDefault(); list.appendChild(PJ._dragging); } });
    });
})();
</script>
<?= $this->endSection() ?>
