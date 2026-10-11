<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><a href="<?= site_url('studio/courses/' . $course['id'] . '/curriculum') ?>"><?= esc($course['title']) ?></a><?= icon('chevron-right') ?><span><?= $quiz['id'] ? 'Edit quiz' : 'New quiz' ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Lms\Models\QuizModel;

$q   = $quiz;
$v   = static fn ($k) => esc((string) old($k, $q[$k] ?? ''), 'attr');
$qJson = array_map(static fn ($x) => [
    'id' => (int) $x['id'], 'question' => $x['question'], 'type' => $x['type'] ?: 'single', 'points' => (int) $x['points'],
    'explanation' => (string) $x['explanation'], 'correct_text' => (string) $x['correct_text'],
    'options' => array_map(static fn ($o) => $o['option_text'], $x['options']),
    'correct' => array_keys(array_filter(array_map(static fn ($o) => (int) $o['is_correct'], $x['options']))),
], $q['questions'] ?? []);
?>
<form method="post" id="quiz-form">
    <?= csrf_field() ?>
    <div class="studio-head">
        <div><h1><?= $q['id'] ? esc($q['title']) : 'New quiz' ?></h1><p><?= esc($course['title']) ?></p></div>
        <div class="row">
            <?php if ($q['id']) : ?><a class="btn" href="<?= site_url('study/' . $course['slug'] . '/quiz/' . $q['id']) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview</a><?php endif ?>
            <button class="btn btn-primary" type="submit"><?= icon('save') ?>Save quiz</button>
        </div>
    </div>
    <div class="split">
        <div class="stack stack-lg">
            <section class="card card-body">
                <div class="field"><label class="label" for="title">Title</label><input class="input input-lg" id="title" name="title" value="<?= $v('title') ?>" required maxlength="200" placeholder="e.g. Module 1 review"></div>
                <div class="field"><label class="label" for="ins">Instructions</label><textarea class="textarea" id="ins" name="instructions" rows="2"><?= esc((string) old('instructions', $q['instructions'] ?? '')) ?></textarea></div>
            </section>
            <div id="questions" class="stack"></div>
            <div class="card card-body row center gap-sm">
                <span class="text-sm text-muted">Add a question:</span>
                <?php foreach (QuizModel::QUESTION_TYPES as $k => $label) : ?><button class="btn btn-sm" type="button" data-add-q="<?= $k ?>"><?= icon('plus') ?><?= esc($label) ?></button><?php endforeach ?>
            </div>
        </div>
        <aside class="stack stack-lg sticky">
            <section class="card card-body stack">
                <div class="field"><span class="label">Status</span><div class="segmented" style="width:100%"><label class="grow"><input type="radio" name="status" value="published" <?= ($q['status'] ?? 'published') === 'published' ? 'checked' : '' ?>><span style="justify-content:center">Published</span></label><label class="grow"><input type="radio" name="status" value="draft" <?= ($q['status'] ?? '') === 'draft' ? 'checked' : '' ?>><span style="justify-content:center">Draft</span></label></div></div>
                <div class="field"><label class="label" for="section">Module</label><select class="select" id="section" name="section_id"><option value="">— None —</option><?php foreach ($sections as $s) : ?><option value="<?= $s['id'] ?>" <?= (int) $q['section_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['title']) ?></option><?php endforeach ?></select></div>
                <div class="form-grid">
                    <div class="field"><label class="label" for="pass">Pass mark %</label><input class="input" id="pass" type="number" min="0" max="100" name="pass_percent" value="<?= $v('pass_percent') ?>"></div>
                    <div class="field"><label class="label" for="att">Attempts</label><input class="input" id="att" type="number" min="0" name="max_attempts" value="<?= $v('max_attempts') ?>"><span class="help">0 = unlimited</span></div>
                </div>
                <div class="field"><label class="label" for="time">Time limit (minutes)</label><input class="input" id="time" type="number" min="1" name="time_limit_mins" value="<?= $v('time_limit_mins') ?>" placeholder="No limit"></div>
                <label class="switch"><input type="checkbox" name="shuffle_questions" value="1" <?= ! empty($q['shuffle_questions']) ? 'checked' : '' ?>><span class="track"></span>Shuffle question order</label>
                <label class="switch"><input type="checkbox" name="show_answers" value="1" <?= ! empty($q['show_answers']) ? 'checked' : '' ?>><span class="track"></span>Show correct answers after submitting</label>
            </section>
            <?php if ($stats && (int) $stats['attempts'] > 0) : ?>
                <section class="card card-body text-sm">
                    <div class="summary-row"><span>Attempts</span><strong><?= (int) $stats['attempts'] ?></strong></div>
                    <div class="summary-row"><span>Average score</span><strong><?= round((float) $stats['avg']) ?>%</strong></div>
                    <div class="summary-row"><span>Passes</span><strong><?= (int) $stats['passes'] ?></strong></div>
                </section>
            <?php endif ?>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('save') ?>Save quiz</button>
            <?php if ($q['id']) : ?><button class="btn btn-danger-ghost btn-block" type="submit" form="delete-quiz"><?= icon('trash') ?>Delete quiz</button><?php endif ?>
        </aside>
    </div>
</form>
<?php if ($q['id']) : ?><form id="delete-quiz" method="post" action="<?= site_url('studio/quizzes/' . $q['id'] . '/delete') ?>" data-confirm="Delete this quiz and all attempts?"><?= csrf_field() ?></form><?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var types = <?= json_encode(QuizModel::QUESTION_TYPES) ?>;
    var data = <?= json_encode($qJson, JSON_UNESCAPED_UNICODE) ?>;
    var wrap = document.getElementById('questions');
    var n = 0;
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML.replace(/"/g, '&quot;'); }

    function optionRow(i, j, text, correct, type) {
        var input = type === 'multiple'
            ? '<input type="checkbox" name="questions[' + i + '][correct][]" value="' + j + '"' + (correct ? ' checked' : '') + ' title="Correct answer">'
            : '<input type="radio" name="questions[' + i + '][correct][]" value="' + j + '"' + (correct ? ' checked' : '') + ' title="Correct answer">';
        return '<div class="row row-nowrap gap-sm mb-1 opt-row" data-j="' + j + '"><label class="check" style="margin:0" title="Mark as correct">' + input + '</label>' +
            '<input class="input input-sm grow" name="questions[' + i + '][options][' + j + ']" value="' + esc(text) + '" placeholder="Option ' + (j + 1) + '">' +
            '<button class="btn btn-ghost btn-icon btn-sm" type="button" data-del-opt aria-label="Remove option">' + PJ.icon('x') + '</button></div>';
    }

    function render(q) {
        var i = n++, type = q.type || 'single';
        var card = document.createElement('section');
        card.className = 'card question-card'; card.setAttribute('data-i', i);
        var typeSel = Object.keys(types).map(function (k) { return '<option value="' + k + '"' + (k === type ? ' selected' : '') + '>' + types[k] + '</option>'; }).join('');
        card.innerHTML =
            '<div class="card-header"><div class="row row-nowrap grow"><span class="qnum"></span><select class="select input-sm" name="questions[' + i + '][type]" data-type style="width:auto">' + typeSel + '</select></div>' +
            '<div class="row row-nowrap gap-sm"><label class="text-sm text-muted">Points</label><input class="input input-sm" type="number" min="1" name="questions[' + i + '][points]" value="' + (q.points || 1) + '" style="width:70px">' +
            '<button class="btn btn-ghost btn-icon btn-sm" type="button" data-up aria-label="Move up">' + PJ.icon('chevron-up') + '</button>' +
            '<button class="btn btn-ghost btn-icon btn-sm" type="button" data-down aria-label="Move down">' + PJ.icon('chevron-down') + '</button>' +
            '<button class="btn btn-ghost btn-icon btn-sm" type="button" data-del-q aria-label="Delete question">' + PJ.icon('trash') + '</button></div></div>' +
            '<div class="card-body">' +
            '<input type="hidden" name="questions[' + i + '][id]" value="' + (q.id || '') + '">' +
            '<div class="field"><textarea class="textarea" name="questions[' + i + '][question]" rows="2" placeholder="Question" required style="min-height:3.5rem">' + esc(q.question) + '</textarea></div>' +
            '<div data-options></div>' +
            '<div class="field" data-short hidden><label class="label">Accepted answers</label><input class="input" name="questions[' + i + '][correct_text]" value="' + esc(q.correct_text) + '" placeholder="e.g. knowledge|the knowledge"><span class="help">Separate alternatives with | — matching ignores case and punctuation.</span></div>' +
            '<div class="field mb-0"><label class="label">Explanation <span class="opt">(shown after submitting)</span></label><input class="input input-sm" name="questions[' + i + '][explanation]" value="' + esc(q.explanation) + '" placeholder="Why is this the answer? Scripture references link automatically."></div>' +
            '</div>';
        wrap.appendChild(card);
        var opts = card.querySelector('[data-options]');
        function drawOptions(t, list, correct) {
            card.querySelector('[data-short]').hidden = t !== 'short_text';
            if (t === 'short_text') { opts.innerHTML = ''; return; }
            if (t === 'true_false') { list = ['True', 'False']; }
            var html = '<div class="label mb-1">' + (t === 'multiple' ? 'Options — tick every correct answer' : 'Options — choose the correct answer') + '</div>';
            list.forEach(function (text, j) { html += optionRow(i, j, text, correct.indexOf(j) !== -1, t); });
            if (t !== 'true_false') { html += '<button class="btn btn-sm btn-ghost" type="button" data-add-opt>' + PJ.icon('plus') + 'Add option</button>'; }
            opts.innerHTML = html;
            if (t === 'true_false') { opts.querySelectorAll('input.input').forEach(function (inp) { inp.readOnly = true; }); opts.querySelectorAll('[data-del-opt]').forEach(function (b) { b.remove(); }); }
        }
        drawOptions(type, q.options && q.options.length ? q.options : ['', '', '', ''], q.correct || []);
        card.querySelector('[data-type]').addEventListener('change', function (e) {
            var current = Array.prototype.map.call(opts.querySelectorAll('.opt-row input.input'), function (x) { return x.value; });
            drawOptions(e.target.value, current.length && e.target.value !== 'true_false' && current.join('') !== 'TrueFalse' ? current : ['', '', '', ''], []);
        });
        opts.addEventListener('click', function (e) {
            if (e.target.closest('[data-add-opt]')) {
                var j = opts.querySelectorAll('.opt-row').length + Date.now() % 1000;
                e.target.closest('[data-add-opt]').insertAdjacentHTML('beforebegin', optionRow(i, j, '', false, card.querySelector('[data-type]').value));
            }
            if (e.target.closest('[data-del-opt]')) { e.target.closest('.opt-row').remove(); }
        });
        number();
        return card;
    }
    function number() { wrap.querySelectorAll('.question-card .qnum').forEach(function (el, k) { el.textContent = k + 1; }); }
    wrap.addEventListener('click', function (e) {
        var card = e.target.closest('.question-card'); if (!card) { return; }
        if (e.target.closest('[data-del-q]') && confirm('Delete this question?')) { card.remove(); }
        if (e.target.closest('[data-up]') && card.previousElementSibling) { wrap.insertBefore(card, card.previousElementSibling); }
        if (e.target.closest('[data-down]') && card.nextElementSibling) { wrap.insertBefore(card.nextElementSibling, card); }
        number();
    });
    document.querySelectorAll('[data-add-q]').forEach(function (b) {
        b.addEventListener('click', function () { var c = render({ type: b.getAttribute('data-add-q') }); c.scrollIntoView({ behavior: 'smooth', block: 'center' }); c.querySelector('textarea').focus(); });
    });
    // Submit questions in their on-screen order.
    document.getElementById('quiz-form').addEventListener('submit', function () {
        wrap.querySelectorAll('.question-card').forEach(function (card, k) { var o = document.createElement('input'); o.type = 'hidden'; o.name = 'order[]'; o.value = card.getAttribute('data-i'); card.appendChild(o); });
    });
    data.forEach(render);
    if (!data.length) { render({ type: 'single' }); }
})();
</script>
<?= $this->endSection() ?>
