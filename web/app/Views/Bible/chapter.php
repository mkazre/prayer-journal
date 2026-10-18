<?= $this->extend('layouts/bare') ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\Scripture;

$slug     = Scripture::slug((int) $book['position']);
$loggedIn = auth()->loggedIn();
$bookJson = array_map(static fn ($b) => ['n' => $b['name'], 's' => Scripture::slug((int) $b['position']), 'c' => (int) $b['chapter_count'], 't' => $b['testament']], $books);
$verseNum = array_column($verses, 'verse', 'id');
$link     = static fn ($p) => $p ? site_url('bible/' . Scripture::slug((int) $p['book']['position']) . '/' . $p['chapter']) : null;
?>
<div class="bible-toolbar no-print">
    <div class="container">
        <a class="btn btn-ghost btn-icon btn-sm" href="<?= site_url('bible') ?>" aria-label="Bible home"><?= icon('library') ?></a>
        <button class="passage-picker" type="button" data-modal-open="picker"><?= esc($book['name']) ?> <?= $chapter ?><?= icon('chevron-down') ?></button>
        <span class="badge hide-sm">KJV</span>
        <div class="grow"></div>
        <?php if ($prev) : ?><a class="btn btn-ghost btn-icon btn-sm" href="<?= $link($prev) ?>" aria-label="Previous chapter" data-key-prev><?= icon('chevron-left') ?></a><?php endif ?>
        <?php if ($next) : ?><a class="btn btn-ghost btn-icon btn-sm" href="<?= $link($next) ?>" aria-label="Next chapter" data-key-next><?= icon('chevron-right') ?></a><?php endif ?>
        <a class="btn btn-ghost btn-icon btn-sm" href="<?= site_url('bible/search') ?>" aria-label="Search"><?= icon('search') ?></a>
        <div class="dropdown" data-dropdown>
            <button class="btn btn-ghost btn-icon btn-sm" type="button" data-dropdown-toggle aria-label="Reader settings"><?= icon('type') ?></button>
            <div class="dropdown-menu" style="min-width:290px;padding:.9rem">
                <div class="menu-label" style="padding-left:0">Text size</div>
                <div class="row row-nowrap">
                    <button class="btn btn-sm btn-icon" type="button" data-scale="-1" aria-label="Smaller"><?= icon('minus') ?></button>
                    <div class="grow progress"><span id="scale-bar" style="width:50%"></span></div>
                    <button class="btn btn-sm btn-icon" type="button" data-scale="1" aria-label="Larger"><?= icon('plus') ?></button>
                </div>
                <div class="menu-label" style="padding-left:0;margin-top:.6rem">Line spacing</div>
                <div class="segmented" style="width:100%">
                    <?php foreach (['0.9' => 'Compact', '1' => 'Normal', '1.15' => 'Airy'] as $v => $l) : ?><label class="grow"><input type="radio" name="leading" value="<?= $v ?>"><span style="justify-content:center"><?= $l ?></span></label><?php endforeach ?>
                </div>
                <div class="menu-label" style="padding-left:0;margin-top:.6rem">Layout</div>
                <div class="segmented" style="width:100%">
                    <label class="grow"><input type="radio" name="bible_layout" value="paragraph" <?= $prefs['bible_layout'] === 'paragraph' ? 'checked' : '' ?>><span style="justify-content:center">Paragraph</span></label>
                    <label class="grow"><input type="radio" name="bible_layout" value="verse" <?= $prefs['bible_layout'] === 'verse' ? 'checked' : '' ?>><span style="justify-content:center">Verse by verse</span></label>
                </div>
                <div class="menu-label" style="padding-left:0;margin-top:.6rem">Font</div>
                <div class="row gap-xs">
                    <button class="btn btn-sm grow" type="button" data-reading-font="serif" style="font-family:var(--font-read)">Serif</button>
                    <button class="btn btn-sm grow" type="button" data-reading-font="sans">Sans</button>
                </div>
                <label class="switch mt-2"><input type="checkbox" id="verse-numbers" <?= $prefs['verse_numbers'] ? 'checked' : '' ?>><span class="track"></span>Verse numbers</label>
                <div class="menu-sep" style="margin:.8rem 0"></div>
                <button class="btn btn-sm btn-ghost btn-block" type="button" data-theme-toggle><?= icon('moon') ?>Toggle dark mode</button>
            </div>
        </div>
    </div>
</div>

<?php if ($prev) : ?><a class="side-arrow prev no-print" href="<?= $link($prev) ?>" aria-label="Previous chapter"><?= icon('chevron-left') ?></a><?php endif ?>
<?php if ($next) : ?><a class="side-arrow next no-print" href="<?= $link($next) ?>" aria-label="Next chapter"><?= icon('chevron-right') ?></a><?php endif ?>

<article class="reader">
    <header class="reader-title">
        <span class="eyebrow"><?= esc(Scripture::groupFor((int) $book['position'])) ?></span>
        <h1><?= esc($book['name']) ?></h1>
        <div class="chapter-num">Chapter <?= $chapter ?></div>
    </header>

    <div class="scripture-text layout-<?= esc($prefs['bible_layout'], 'attr') ?> <?= $prefs['verse_numbers'] ? '' : 'hide-numbers' ?>" id="scripture" data-book="<?= esc($book['name'], 'attr') ?>" data-chapter="<?= $chapter ?>">
        <?php foreach ($verses as $v) : $vid = (int) $v['id']; ?><span class="verse" id="v<?= (int) $v['verse'] ?>" data-id="<?= $vid ?>" data-v="<?= (int) $v['verse'] ?>" <?= isset($highlights[$vid]) ? 'data-hl="' . esc($highlights[$vid], 'attr') . '"' : '' ?> tabindex="0"><sup><?= (int) $v['verse'] ?></sup><?= esc($v['text']) ?><span class="marks"><?= in_array($vid, $bookmarks, true) ? icon('bookmark', 'icon-fill mk-bookmark') : '' ?><?= isset($notes[$vid]) ? icon('note', 'mk-note') : '' ?></span></span> <?php endforeach ?>
    </div>

    <?php if ($notes) : ?>
        <section class="card mt-4" id="chapter-notes">
            <div class="card-header"><h2><?= icon('note') ?> Your notes on this chapter</h2></div>
            <div class="divider-list">
                <?php foreach ($notes as $verseId => $list) : foreach ($list as $n) : ?>
                    <div class="card-body-sm row items-start row-nowrap" id="note-<?= $n['id'] ?>">
                        <a class="badge badge-brand" href="#v<?= (int) $verseNum[$verseId] ?>">v<?= (int) $verseNum[$verseId] ?></a>
                        <div class="grow text-sm" style="white-space:pre-wrap"><?= esc($n['body']) ?></div>
                        <form method="post" action="<?= site_url('bible/notes/' . $n['id'] . '/delete') ?>" data-ajax data-ajax-reload data-confirm="Delete this note?"><?= csrf_field() ?><button class="btn btn-ghost btn-icon btn-sm" type="submit" aria-label="Delete note"><?= icon('trash') ?></button></form>
                    </div>
                <?php endforeach; endforeach ?>
            </div>
        </section>
    <?php endif ?>

    <nav class="chapter-pager no-print">
        <?php if ($prev) : ?><a href="<?= $link($prev) ?>"><small>Previous</small><strong><?= esc($prev['book']['name']) ?> <?= $prev['chapter'] ?></strong></a><?php else : ?><span style="flex:1"></span><?php endif ?>
        <?php if ($next) : ?><a class="next" href="<?= $link($next) ?>"><small>Next</small><strong><?= esc($next['book']['name']) ?> <?= $next['chapter'] ?></strong></a><?php endif ?>
    </nav>
    <p class="text-center text-xs text-muted mt-3">King James Version · Public domain</p>
</article>

<div class="verse-actions no-print" id="verse-actions" role="toolbar" aria-label="Verse actions">
    <span class="sel-label" id="sel-label"></span>
    <?php foreach ($colors as $c) : ?><button class="swatch" type="button" data-c="<?= $c ?>" data-hl-btn="<?= $c ?>" aria-label="Highlight <?= esc(strtolower(\App\Modules\Bible\Controllers\BibleController::COLOR_LABELS[$c] ?? $c), 'attr') ?>"></button><?php endforeach ?>
    <button class="swatch swatch-none" type="button" data-hl-btn="" aria-label="Remove highlight"><?= icon('x') ?></button>
    <span class="menu-sep" style="width:1px;height:1.6rem;margin:0 .2rem"></span>
    <button class="btn btn-ghost btn-icon btn-sm" type="button" id="act-bookmark" aria-label="Bookmark"><?= icon('bookmark') ?></button>
    <button class="btn btn-ghost btn-icon btn-sm" type="button" id="act-note" aria-label="Add note"><?= icon('note') ?></button>
    <button class="btn btn-ghost btn-icon btn-sm" type="button" id="act-copy" aria-label="Copy"><?= icon('copy') ?></button>
    <button class="btn btn-ghost btn-icon btn-sm" type="button" id="act-share" aria-label="Share"><?= icon('share') ?></button>
    <button class="btn btn-ghost btn-icon btn-sm" type="button" id="act-close" aria-label="Clear selection"><?= icon('x') ?></button>
</div>

<dialog class="modal modal-lg" id="picker" aria-label="Choose a passage">
    <div class="modal-head"><h3>Go to a passage</h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body">
        <div class="input-icon mb-2"><?= icon('search') ?><input class="input" type="search" id="picker-filter" placeholder="Filter books…" autofocus></div>
        <div id="picker-books"></div>
        <div id="picker-chapters" hidden>
            <button class="btn btn-sm btn-ghost mb-2" type="button" id="picker-back"><?= icon('arrow-left') ?>All books</button>
            <h3 id="picker-book-name"></h3>
            <div class="chapter-grid" id="picker-grid"></div>
        </div>
    </div>
</dialog>

<dialog class="modal" id="note-modal">
    <form id="note-form">
        <div class="modal-head"><h3><?= icon('note') ?> Note on <span id="note-ref"></span></h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
        <div class="modal-body">
            <blockquote id="note-quote" class="text-2" style="font-family:var(--reading-font);margin:0 0 1rem;padding-left:1rem;border-left:3px solid var(--accent)"></blockquote>
            <textarea class="textarea" id="note-body" rows="5" placeholder="What is God showing you here?" required></textarea>
        </div>
        <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('save') ?>Save note</button></div>
    </form>
</dialog>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var loggedIn = <?= $loggedIn ? 'true' : 'false' ?>;
    var books = <?= json_encode($bookJson, JSON_UNESCAPED_UNICODE) ?>;
    var base = <?= json_encode(site_url('bible')) ?>;
    var loginUrl = <?= json_encode(site_url('login')) ?>;
    var text = document.getElementById('scripture');
    var bar = document.getElementById('verse-actions');
    var selected = [];
    var bookName = text.getAttribute('data-book'), chapter = text.getAttribute('data-chapter');

    /* ---------- Reader settings ---------- */
    var root = document.documentElement;
    function ls(k, v) { try { if (v === undefined) { return localStorage.getItem('pj-' + k); } localStorage.setItem('pj-' + k, v); } catch (e) { return null; } }
    var scale = parseFloat(ls('bible-scale') || '1'), leading = ls('bible-leading') || '1';
    function applyScale() { root.style.setProperty('--bible-scale', scale); document.getElementById('scale-bar').style.width = Math.round((scale - 0.8) / 0.8 * 100) + '%'; }
    function applyLeading() { root.style.setProperty('--bible-leading', leading); var r = document.querySelector('input[name=leading][value="' + leading + '"]'); if (r) { r.checked = true; } }
    applyScale(); applyLeading();
    document.querySelectorAll('[data-scale]').forEach(function (b) { b.addEventListener('click', function () { scale = Math.max(0.8, Math.min(1.6, scale + parseInt(b.getAttribute('data-scale'), 10) * 0.1)); scale = Math.round(scale * 10) / 10; ls('bible-scale', scale); applyScale(); }); });
    document.querySelectorAll('input[name=leading]').forEach(function (r) { r.addEventListener('change', function () { leading = r.value; ls('bible-leading', leading); applyLeading(); }); });
    document.querySelectorAll('input[name=bible_layout]').forEach(function (r) {
        r.addEventListener('change', function () {
            text.classList.remove('layout-paragraph', 'layout-verse'); text.classList.add('layout-' + r.value);
            if (loggedIn) { PJ.post(<?= json_encode(site_url('bible/settings')) ?>, { bible_layout: r.value }).catch(function () {}); }
        });
    });
    document.getElementById('verse-numbers').addEventListener('change', function (e) {
        text.classList.toggle('hide-numbers', !e.target.checked);
        if (loggedIn) { PJ.post(<?= json_encode(site_url('bible/settings')) ?>, { verse_numbers: e.target.checked ? 1 : 0 }).catch(function () {}); }
    });

    /* ---------- Deep links ---------- */
    function flashHash() {
        var m = location.hash.match(/^#v(\d+)(?:-(\d+))?$/);
        if (!m) { return; }
        var from = parseInt(m[1], 10), to = parseInt(m[2] || m[1], 10);
        for (var v = from; v <= to; v++) { var el = document.getElementById('v' + v); if (el) { el.classList.add('is-flash'); } }
        var first = document.getElementById('v' + from);
        if (first) { setTimeout(function () { first.scrollIntoView({ block: 'center', behavior: 'smooth' }); }, 60); }
    }
    flashHash(); window.addEventListener('hashchange', flashHash);

    /* ---------- Selection ---------- */
    function label() {
        var nums = selected.map(function (el) { return parseInt(el.getAttribute('data-v'), 10); }).sort(function (a, b) { return a - b; });
        var parts = [], start = nums[0], prev = nums[0];
        for (var i = 1; i <= nums.length; i++) {
            if (nums[i] === prev + 1) { prev = nums[i]; continue; }
            parts.push(start === prev ? String(start) : start + '-' + prev);
            start = prev = nums[i];
        }
        return bookName + ' ' + chapter + ':' + parts.join(',');
    }
    function selText() {
        return selected.slice().sort(function (a, b) { return a.getAttribute('data-v') - b.getAttribute('data-v'); })
            .map(function (el) { return el.childNodes[1] ? el.childNodes[1].textContent.trim() : el.textContent.trim(); }).join(' ');
    }
    function refresh() {
        bar.classList.toggle('is-open', selected.length > 0);
        if (selected.length) { document.getElementById('sel-label').textContent = label(); }
        var marked = selected.length && selected.every(function (el) { return el.querySelector('.mk-bookmark'); });
        document.getElementById('act-bookmark').classList.toggle('is-on', !!marked);
    }
    function toggle(el) {
        var i = selected.indexOf(el);
        if (i >= 0) { selected.splice(i, 1); el.classList.remove('is-selected'); } else { selected.push(el); el.classList.add('is-selected'); }
        refresh();
    }
    function clear() { selected.forEach(function (el) { el.classList.remove('is-selected'); }); selected = []; refresh(); }
    text.addEventListener('click', function (e) { var v = e.target.closest('.verse'); if (v && !window.getSelection().toString()) { toggle(v); } });
    text.addEventListener('keydown', function (e) { if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('verse')) { e.preventDefault(); toggle(e.target); } });
    document.getElementById('act-close').addEventListener('click', clear);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { clear(); } });

    function needLogin() { if (!loggedIn) { PJ.toast('Sign in to save highlights, bookmarks and notes', 'info'); setTimeout(function () { location.href = loginUrl; }, 1200); return true; } return false; }

    /* ---------- Highlights ---------- */
    bar.querySelectorAll('[data-hl-btn]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (needLogin()) { return; }
            var color = b.getAttribute('data-hl-btn');
            var ids = selected.map(function (el) { return el.getAttribute('data-id'); });
            PJ.post(base + '/verses/' + ids[0] + '/highlight', { color: color, verse_ids: ids }).then(function () {
                selected.forEach(function (el) { if (color) { el.setAttribute('data-hl', color); } else { el.removeAttribute('data-hl'); } });
                clear();
            }).catch(function (err) { PJ.toast(err.message, 'error'); });
        });
    });

    /* ---------- Bookmarks ---------- */
    document.getElementById('act-bookmark').addEventListener('click', function () {
        if (needLogin()) { return; }
        var addAll = !selected.every(function (el) { return el.querySelector('.mk-bookmark'); });
        var jobs = selected.filter(function (el) { return !!el.querySelector('.mk-bookmark') !== addAll; }).map(function (el) {
            return PJ.post(base + '/verses/' + el.getAttribute('data-id') + '/bookmark', {}).then(function (d) {
                var marks = el.querySelector('.marks'), mk = marks.querySelector('.mk-bookmark');
                if (d.bookmarked && !mk) { marks.insertAdjacentHTML('afterbegin', PJ.icon('bookmark', 'icon-fill mk-bookmark')); }
                if (!d.bookmarked && mk) { mk.remove(); }
            });
        });
        Promise.all(jobs).then(function () { PJ.toast(addAll ? 'Bookmarked ' + label() : 'Bookmark removed'); clear(); }).catch(function (err) { PJ.toast(err.message, 'error'); });
    });

    /* ---------- Notes ---------- */
    var noteModal = document.getElementById('note-modal');
    document.getElementById('act-note').addEventListener('click', function () {
        if (needLogin()) { return; }
        document.getElementById('note-ref').textContent = label();
        document.getElementById('note-quote').textContent = selText();
        document.getElementById('note-body').value = '';
        noteModal.showModal();
        document.getElementById('note-body').focus();
    });
    document.getElementById('note-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var first = selected.slice().sort(function (a, b) { return a.getAttribute('data-v') - b.getAttribute('data-v'); })[0];
        var body = document.getElementById('note-body').value.trim();
        if (selected.length > 1) { body = '[' + label() + '] ' + body; }
        PJ.post(base + '/verses/' + first.getAttribute('data-id') + '/note', { body: body }).then(function () {
            noteModal.close(); PJ.toast('Note saved'); setTimeout(function () { location.reload(); }, 600);
        }).catch(function (err) { PJ.toast(err.message, 'error'); });
    });

    /* ---------- Copy & share ---------- */
    document.getElementById('act-copy').addEventListener('click', function () {
        var t = '“' + selText() + '” — ' + label() + ' (KJV)';
        (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { PJ.toast('Copied ' + label()); clear(); }, function () { window.prompt('Copy:', t); });
    });
    document.getElementById('act-share').addEventListener('click', function () {
        var nums = selected.map(function (el) { return parseInt(el.getAttribute('data-v'), 10); }).sort(function (a, b) { return a - b; });
        var url = location.href.split('#')[0] + '#v' + nums[0] + (nums.length > 1 ? '-' + nums[nums.length - 1] : '');
        var data = { title: label(), text: '“' + selText() + '” — ' + label(), url: url };
        if (navigator.share) { navigator.share(data).catch(function () {}); }
        else if (navigator.clipboard) { navigator.clipboard.writeText(data.text + '\n' + url).then(function () { PJ.toast('Verse and link copied'); }); }
    });

    /* ---------- Passage picker ---------- */
    var list = document.getElementById('picker-books'), chapters = document.getElementById('picker-chapters');
    function renderBooks(filter) {
        var f = (filter || '').toLowerCase(), html = '';
        ['OT', 'NT'].forEach(function (t) {
            var items = books.filter(function (b) { return b.t === t && b.n.toLowerCase().indexOf(f) !== -1; });
            if (!items.length) { return; }
            html += '<div class="book-group mb-2"><h3>' + (t === 'OT' ? 'Old Testament' : 'New Testament') + '</h3><div class="book-grid">';
            items.forEach(function (b) { html += '<button type="button" class="book-btn' + (b.n === bookName ? ' is-active' : '') + '" data-slug="' + b.s + '"><span>' + b.n + '</span><small>' + b.c + '</small></button>'; });
            html += '</div></div>';
        });
        list.innerHTML = html || '<p class="text-muted">No books match.</p>';
    }
    renderBooks('');
    document.getElementById('picker-filter').addEventListener('input', function (e) { chapters.hidden = true; list.hidden = false; renderBooks(e.target.value); });
    list.addEventListener('click', function (e) {
        var b = e.target.closest('[data-slug]'); if (!b) { return; }
        var book = books.filter(function (x) { return x.s === b.getAttribute('data-slug'); })[0];
        if (book.c === 1) { location.href = base + '/' + book.s + '/1'; return; }
        document.getElementById('picker-book-name').textContent = book.n;
        var g = ''; for (var c = 1; c <= book.c; c++) { g += '<a href="' + base + '/' + book.s + '/' + c + '"' + (book.n === bookName && String(c) === chapter ? ' class="is-active"' : '') + '>' + c + '</a>'; }
        document.getElementById('picker-grid').innerHTML = g;
        list.hidden = true; chapters.hidden = false;
    });
    document.getElementById('picker-back').addEventListener('click', function () { chapters.hidden = true; list.hidden = false; });
})();
</script>
<?= $this->endSection() ?>
