/* Prayer Journal — progressive-enhancement UI behaviours (no dependencies). */
(function () {
    'use strict';

    var doc = document;
    var root = doc.documentElement;
    var PJ = window.PJ = window.PJ || {};

    function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
    function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
    function on(el, ev, sel, fn) {
        el.addEventListener(ev, function (e) {
            var t = e.target.closest(sel);
            if (t && el.contains(t)) { fn.call(t, e, t); }
        });
    }
    function debounce(fn, ms) { var t; return function () { var a = arguments, s = this; clearTimeout(t); t = setTimeout(function () { fn.apply(s, a); }, ms); }; }
    function icon(name, cls) { return '<svg class="icon ' + (cls || '') + '" aria-hidden="true"><use href="' + PJ.iconBase + '#' + name + '"></use></svg>'; }
    PJ.icon = icon;
    PJ.iconBase = (function () { var m = doc.querySelector('meta[name="pj-icons"]'); return m ? m.content : '/assets/img/icons.svg'; })();
    PJ.csrf = function () { var m = doc.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; };
    PJ.csrfName = function () { var m = doc.querySelector('meta[name="csrf-name"]'); return m ? m.content : 'csrf_test_name'; };

    /* ---------- Requests ---------- */
    PJ.request = function (url, opts) {
        opts = opts || {};
        var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': PJ.csrf() };
        var body = opts.body;
        if (body && !(body instanceof FormData) && typeof body === 'object') {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }
        return fetch(url, { method: opts.method || 'POST', headers: headers, body: body, credentials: 'same-origin' })
            .then(function (res) {
                return res.text().then(function (text) {
                    var data; try { data = text ? JSON.parse(text) : {}; } catch (e) { data = { message: text }; }
                    if (data && data.csrf) { var m = doc.querySelector('meta[name="csrf-token"]'); if (m) { m.content = data.csrf; } }
                    if (!res.ok) { var err = new Error((data && (data.error || data.message)) || 'Something went wrong'); err.data = data; err.status = res.status; throw err; }
                    return data;
                });
            });
    };
    PJ.post = function (url, body) { return PJ.request(url, { body: body || {} }); };
    PJ.get = function (url) { return PJ.request(url, { method: 'GET' }); };

    /* ---------- Toasts ---------- */
    PJ.toast = function (message, type) {
        var box = $('.toasts');
        if (!box) { box = doc.createElement('div'); box.className = 'toasts'; box.setAttribute('role', 'status'); box.setAttribute('aria-live', 'polite'); doc.body.appendChild(box); }
        var t = doc.createElement('div');
        type = type || 'success';
        t.className = 'toast toast-' + type;
        t.innerHTML = icon(type === 'error' ? 'alert' : (type === 'info' ? 'info' : 'check-circle')) + '<div></div><button type="button" aria-label="Dismiss">' + icon('x') + '</button>';
        t.querySelector('div').textContent = message;
        box.appendChild(t);
        var close = function () { t.classList.add('is-leaving'); setTimeout(function () { t.remove(); }, 260); };
        t.querySelector('button').addEventListener('click', close);
        setTimeout(close, type === 'error' ? 7000 : 4200);
    };

    /* ---------- Theme ---------- */
    var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    PJ.applyTheme = function (pref) {
        var resolved = pref === 'dark' || (pref !== 'light' && mql && mql.matches) ? 'dark' : 'light';
        root.setAttribute('data-theme', resolved);
        root.setAttribute('data-theme-pref', pref);
        $$('[data-theme-choice]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-theme-choice') === pref); });
    };
    if (mql) { mql.addEventListener('change', function () { PJ.applyTheme(root.getAttribute('data-theme-pref') || 'system'); }); }
    PJ.savePref = function (key, value) {
        try { localStorage.setItem('pj-' + key, value); } catch (e) {}
        if (root.hasAttribute('data-auth')) {
            var body = {}; body[key] = value;
            PJ.post(root.getAttribute('data-prefs-url'), body).catch(function () {});
        }
    };
    on(doc, 'click', '[data-theme-choice]', function (e, b) {
        var pref = b.getAttribute('data-theme-choice');
        PJ.applyTheme(pref); PJ.savePref('theme', pref);
    });
    on(doc, 'click', '[data-theme-toggle]', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        PJ.applyTheme(next); PJ.savePref('theme', next);
    });
    on(doc, 'click', '[data-text-size]', function (e, b) {
        var v = b.getAttribute('data-text-size');
        root.setAttribute('data-text', v); PJ.savePref('text', v);
        $$('[data-text-size]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
    });
    on(doc, 'click', '[data-reading-font]', function (e, b) {
        var v = b.getAttribute('data-reading-font');
        root.setAttribute('data-reading', v); PJ.savePref('reading', v);
        $$('[data-reading-font]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
    });

    /* ---------- Dropdowns ---------- */
    on(doc, 'click', '[data-dropdown] > [data-dropdown-toggle]', function (e, btn) {
        e.preventDefault();
        var dd = btn.parentElement;
        var open = !dd.classList.contains('is-open');
        $$('[data-dropdown].is-open').forEach(function (d) { d.classList.remove('is-open'); });
        dd.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    doc.addEventListener('click', function (e) {
        if (!e.target.closest('[data-dropdown]')) { $$('[data-dropdown].is-open').forEach(function (d) { d.classList.remove('is-open'); }); }
    });

    /* ---------- Drawers ---------- */
    function closeDrawers() { doc.body.classList.remove('drawer-open'); $$('.is-open[data-drawer]').forEach(function (d) { d.classList.remove('is-open'); }); }
    on(doc, 'click', '[data-drawer-open]', function (e, b) {
        e.preventDefault();
        var d = doc.getElementById(b.getAttribute('data-drawer-open'));
        if (!d) { return; }
        d.classList.add('is-open'); doc.body.classList.add('drawer-open');
    });
    on(doc, 'click', '[data-drawer-close], .drawer-backdrop', closeDrawers);
    doc.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeDrawers(); $$('[data-dropdown].is-open').forEach(function (d) { d.classList.remove('is-open'); }); }
    });

    /* ---------- Modals ---------- */
    on(doc, 'click', '[data-modal-open]', function (e, b) {
        e.preventDefault();
        var m = doc.getElementById(b.getAttribute('data-modal-open'));
        if (m && m.showModal) { m.showModal(); var f = m.querySelector('[autofocus], input, textarea, select'); if (f) { f.focus(); } }
    });
    on(doc, 'click', '[data-modal-close]', function (e, b) { var m = b.closest('dialog'); if (m) { m.close(); } });
    on(doc, 'click', 'dialog.modal', function (e, m) { if (e.target === m) { m.close(); } });

    /* ---------- Tabs ---------- */
    function activateTab(group, id, push) {
        $$('[data-tab]', group).forEach(function (t) {
            var active = t.getAttribute('data-tab') === id;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        var scope = group.getAttribute('data-tabs') ? doc.getElementById(group.getAttribute('data-tabs')) : doc;
        $$('.tab-panel', scope || doc).forEach(function (p) { p.classList.toggle('is-active', p.id === id); });
        if (push && history.replaceState) { history.replaceState(null, '', '#' + id); }
    }
    on(doc, 'click', '[data-tab]', function (e, t) { e.preventDefault(); activateTab(t.closest('.tabs'), t.getAttribute('data-tab'), true); });
    $$('.tabs').forEach(function (group) {
        var hash = location.hash.replace('#', '');
        if (hash && $('[data-tab="' + hash + '"]', group)) { activateTab(group, hash, false); }
    });

    /* ---------- Confirm ---------- */
    doc.addEventListener('submit', function (e) {
        var f = e.target;
        var msg = f.getAttribute('data-confirm') || (e.submitter && e.submitter.getAttribute('data-confirm'));
        if (msg && !window.confirm(msg)) { e.preventDefault(); e.stopImmediatePropagation(); }
    }, true);
    on(doc, 'click', 'a[data-confirm]', function (e, a) { if (!window.confirm(a.getAttribute('data-confirm'))) { e.preventDefault(); } });

    /* ---------- AJAX forms ---------- */
    doc.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute('data-ajax')) { return; }
        e.preventDefault();
        syncEditors(form);
        var btn = e.submitter || form.querySelector('[type="submit"]');
        if (btn) { btn.disabled = true; }
        var fd = new FormData(form);
        if (e.submitter && e.submitter.name) { fd.append(e.submitter.name, e.submitter.value); }
        PJ.request(form.action, { body: fd }).then(function (data) {
            if (data.message) { PJ.toast(data.message, 'success'); }
            form.dispatchEvent(new CustomEvent('pj:success', { detail: data, bubbles: true }));
            if (data.redirect) { location.href = data.redirect; return; }
            if (form.hasAttribute('data-ajax-reload')) { location.reload(); }
            if (form.hasAttribute('data-ajax-reset')) { form.reset(); }
            var dlg = form.closest('dialog'); if (dlg && form.hasAttribute('data-ajax-close')) { dlg.close(); }
        }).catch(function (err) {
            PJ.toast(err.message, 'error');
        }).finally(function () { if (btn) { btn.disabled = false; } });
    });

    /* ---------- Autosave ---------- */
    $$('[data-autosave-url]').forEach(function (el) {
        var state = el.closest('[data-autosave-scope]') ? $('.save-state', el.closest('[data-autosave-scope]')) : null;
        var last = el.value;
        var save = debounce(function () {
            if (el.value === last) { return; }
            var body = {}; body[el.name || 'body'] = el.value;
            var extra = el.getAttribute('data-autosave-extra');
            if (extra) { try { Object.assign(body, JSON.parse(extra)); } catch (e) {} }
            if (state) { state.className = 'save-state'; state.innerHTML = icon('refresh') + ' Saving…'; }
            PJ.post(el.getAttribute('data-autosave-url'), body).then(function (data) {
                last = el.value;
                if (data && data.id && el.getAttribute('data-autosave-extra')) {
                    try { var ex = JSON.parse(el.getAttribute('data-autosave-extra')); ex.id = data.id; el.setAttribute('data-autosave-extra', JSON.stringify(ex)); } catch (e) {}
                }
                if (state) { state.className = 'save-state is-saved'; state.innerHTML = icon('check') + ' Saved'; }
            }).catch(function (err) {
                if (state) { state.className = 'save-state text-danger'; state.innerHTML = icon('alert') + ' ' + err.message; }
            });
        }, 900);
        el.addEventListener('input', function () { if (state) { state.className = 'save-state'; state.textContent = 'Editing…'; } save(); });
        el.addEventListener('blur', save);
    });

    /* ---------- Copy ---------- */
    on(doc, 'click', '[data-copy]', function (e, b) {
        e.preventDefault();
        var text = b.getAttribute('data-copy');
        (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () { PJ.toast('Copied to clipboard'); }, function () { window.prompt('Copy this:', text); });
    });

    /* ---------- Quantity steppers ---------- */
    on(doc, 'click', '[data-qty] button', function (e, b) {
        e.preventDefault();
        var input = b.parentElement.querySelector('input');
        var min = parseInt(input.min || '0', 10), max = parseInt(input.max || '9999', 10);
        var v = (parseInt(input.value, 10) || 0) + (b.getAttribute('data-step') === '-' ? -1 : 1);
        input.value = Math.max(min, Math.min(max, v));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    on(doc, 'change', '[data-autosubmit]', function (e, el) { (el.form || el.closest('form')).requestSubmit ? (el.form || el.closest('form')).requestSubmit() : (el.form || el.closest('form')).submit(); });

    /* ---------- Slugs & counters ---------- */
    $$('[data-slug-from]').forEach(function (slug) {
        var src = doc.querySelector(slug.getAttribute('data-slug-from'));
        if (!src) { return; }
        var touched = slug.value !== '';
        slug.addEventListener('input', function () { touched = true; });
        src.addEventListener('input', function () {
            if (touched) { return; }
            slug.value = src.value.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 200);
        });
    });
    $$('[data-count]').forEach(function (el) {
        var out = doc.getElementById(el.getAttribute('data-count'));
        var max = el.getAttribute('maxlength');
        var upd = function () { if (out) { out.textContent = el.value.length + (max ? ' / ' + max : ''); } };
        el.addEventListener('input', upd); upd();
    });

    /* ---------- Repeaters ---------- */
    on(doc, 'click', '[data-repeater-add]', function (e, b) {
        e.preventDefault();
        var rep = doc.getElementById(b.getAttribute('data-repeater-add'));
        var tpl = rep.querySelector('template');
        var idx = Date.now();
        var html = tpl.innerHTML.replace(/__i__/g, idx);
        var wrap = doc.createElement('div'); wrap.innerHTML = html.trim();
        var node = wrap.firstChild;
        rep.querySelector('[data-repeater-items]').appendChild(node);
        var first = node.querySelector('input, textarea'); if (first) { first.focus(); }
        rep.dispatchEvent(new CustomEvent('pj:repeater-add', { detail: node, bubbles: true }));
    });
    on(doc, 'click', '[data-repeater-remove]', function (e, b) { e.preventDefault(); var row = b.closest('.repeater-row'); if (row) { row.remove(); } });

    /* ---------- File inputs / dropzones ---------- */
    $$('.dropzone[data-for]').forEach(function (zone) {
        var input = doc.getElementById(zone.getAttribute('data-for'));
        if (!input) { return; }
        var label = zone.querySelector('[data-file-label]');
        zone.addEventListener('click', function () { input.click(); });
        ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-over'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('is-over'); }); });
        zone.addEventListener('drop', function (e) { if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); } });
        input.addEventListener('change', function () {
            var names = Array.prototype.map.call(input.files, function (f) { return f.name; });
            if (label) { label.textContent = names.length ? names.join(', ') : label.getAttribute('data-default'); }
            var preview = zone.getAttribute('data-preview') && doc.getElementById(zone.getAttribute('data-preview'));
            if (preview && input.files[0] && /^image\//.test(input.files[0].type)) {
                var img = preview.tagName === 'IMG' ? preview : preview.querySelector('img');
                if (img) { img.src = URL.createObjectURL(input.files[0]); img.hidden = false; preview.hidden = false; var art = preview.querySelector(".cover-art"); if (art) { art.remove(); } }
            }
        });
    });

    /* ---------- Drag & drop sorting ---------- */
    PJ.sortable = function (container) {
        var group = container.getAttribute('data-sortable');
        var dragging = null, placeholder = doc.createElement('div');
        placeholder.className = 'drop-placeholder';
        function items(c) { return $$(':scope > [data-id]', c); }
        container.addEventListener('dragstart', function (e) {
            var item = e.target.closest('[data-id]');
            if (!item || item.parentElement !== container) { return; }
            // Only start from the handle so text inside stays selectable.
            if (!PJ._handleDown && !item.hasAttribute('data-drag-anywhere')) { e.preventDefault(); return; }
            dragging = item; PJ._dragGroup = group; PJ._dragging = item;
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', item.getAttribute('data-id')); } catch (err) {}
            setTimeout(function () { item.classList.add('is-dragging'); }, 0);
        });
        container.addEventListener('dragover', function (e) {
            if (!PJ._dragging || PJ._dragGroup !== group) { return; }
            e.preventDefault();
            var after = null;
            items(container).forEach(function (el) {
                if (el === PJ._dragging) { return; }
                var box = el.getBoundingClientRect();
                if (after === null && e.clientY < box.top + box.height / 2) { after = el; }
            });
            if (after) { container.insertBefore(placeholder, after); } else { container.appendChild(placeholder); }
        });
        container.addEventListener('drop', function (e) {
            if (!PJ._dragging || PJ._dragGroup !== group) { return; }
            e.preventDefault();
            container.insertBefore(PJ._dragging, placeholder);
            placeholder.remove();
            PJ._dragging.classList.remove('is-dragging');
            var moved = PJ._dragging;
            PJ._dragging = null;
            container.dispatchEvent(new CustomEvent('pj:sorted', { detail: { item: moved }, bubbles: true }));
        });
        container.addEventListener('dragend', function () {
            placeholder.remove();
            if (PJ._dragging) { PJ._dragging.classList.remove('is-dragging'); PJ._dragging = null; }
        });
    };
    doc.addEventListener('mousedown', function (e) { PJ._handleDown = !!e.target.closest('.drag-handle'); });
    doc.addEventListener('touchstart', function (e) { PJ._handleDown = !!e.target.closest('.drag-handle'); }, { passive: true });
    $$('[data-sortable]').forEach(PJ.sortable);
    // Keyboard reordering: Alt+ArrowUp / Alt+ArrowDown on a focused handle.
    on(doc, 'keydown', '.drag-handle', function (e, h) {
        if (!e.altKey || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) { return; }
        e.preventDefault();
        var item = h.closest('[data-id]');
        var sib = e.key === 'ArrowUp' ? item.previousElementSibling : item.nextElementSibling;
        if (!sib || !sib.hasAttribute('data-id')) { return; }
        item.parentElement.insertBefore(item, e.key === 'ArrowUp' ? sib : sib.nextSibling);
        h.focus();
        item.parentElement.dispatchEvent(new CustomEvent('pj:sorted', { detail: { item: item }, bubbles: true }));
    });

    /* ---------- Rich text editor ---------- */
    var TOOLS = [
        ['formatBlock:H2', 'heading', 'Heading'], ['formatBlock:H3', null, 'Subheading', 'H3'], ['formatBlock:P', null, 'Paragraph', '¶'], ['|'],
        ['bold', 'bold', 'Bold'], ['italic', 'italic', 'Italic'], ['underline', 'underline', 'Underline'], ['|'],
        ['insertUnorderedList', 'list-ul', 'Bulleted list'], ['insertOrderedList', 'list-ol', 'Numbered list'], ['formatBlock:BLOCKQUOTE', 'quote', 'Quote'],
        ['scripture', 'book-open', 'Scripture block'], ['callout', 'info', 'Callout'], ['|'],
        ['createLink', 'link', 'Insert link'], ['insertImage', 'image', 'Insert image by URL'], ['video', 'video', 'Embed YouTube/Vimeo'], ['insertHorizontalRule', 'minus', 'Divider'], ['|'],
        ['undo', 'undo', 'Undo'], ['redo', 'redo', 'Redo'], ['source', 'code', 'Edit HTML']
    ];
    function syncEditors(scope) { $$('[data-editor-area]', scope || doc).forEach(function (area) { var ta = area.closest('.editor').querySelector('textarea.editor-value'); if (!ta.classList.contains('is-source')) { ta.value = area.innerHTML; } }); }
    PJ.syncEditors = syncEditors;
    $$('textarea[data-editor]').forEach(function (ta) {
        var wrap = doc.createElement('div'); wrap.className = 'editor';
        var bar = doc.createElement('div'); bar.className = 'editor-toolbar'; bar.setAttribute('role', 'toolbar');
        var area = doc.createElement('div'); area.className = 'editor-area prose'; area.contentEditable = 'true'; area.setAttribute('data-editor-area', '');
        area.setAttribute('data-placeholder', ta.getAttribute('placeholder') || 'Start writing…');
        area.innerHTML = ta.value;
        TOOLS.forEach(function (t) {
            if (t[0] === '|') { var s = doc.createElement('span'); s.className = 'sep'; bar.appendChild(s); return; }
            var b = doc.createElement('button'); b.type = 'button'; b.title = t[2]; b.setAttribute('aria-label', t[2]); b.setAttribute('data-cmd', t[0]);
            b.innerHTML = t[1] ? icon(t[1]) : (t[3] || t[2]);
            bar.appendChild(b);
        });
        ta.parentNode.insertBefore(wrap, ta);
        wrap.appendChild(bar); wrap.appendChild(area); wrap.appendChild(ta);
        ta.classList.add('editor-value', 'textarea', 'editor-source'); ta.hidden = true;
        bar.addEventListener('mousedown', function (e) { if (e.target.closest('button')) { e.preventDefault(); } });
        bar.addEventListener('click', function (e) {
            var b = e.target.closest('button'); if (!b) { return; }
            var cmd = b.getAttribute('data-cmd');
            if (cmd === 'source') {
                var toSource = !ta.classList.contains('is-source');
                if (toSource) { ta.value = area.innerHTML; ta.hidden = false; area.hidden = true; ta.classList.add('is-source'); }
                else { area.innerHTML = ta.value; ta.hidden = true; area.hidden = false; ta.classList.remove('is-source'); }
                b.classList.toggle('is-on', toSource);
                return;
            }
            area.focus();
            if (cmd.indexOf('formatBlock:') === 0) { doc.execCommand('formatBlock', false, cmd.split(':')[1]); }
            else if (cmd === 'createLink') { var url = window.prompt('Link URL (https://…)'); if (url) { doc.execCommand('createLink', false, url); } }
            else if (cmd === 'insertImage') { var src = window.prompt('Image URL (https://…)'); if (src) { doc.execCommand('insertImage', false, src); } }
            else if (cmd === 'video') {
                var v = window.prompt('YouTube or Vimeo URL'); if (!v) { return; }
                var m = v.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/), vm = v.match(/vimeo\.com\/(?:video\/)?(\d+)/);
                var src2 = m ? 'https://www.youtube-nocookie.com/embed/' + m[1] : (vm ? 'https://player.vimeo.com/video/' + vm[1] : null);
                if (!src2) { PJ.toast('That does not look like a YouTube or Vimeo link', 'error'); return; }
                doc.execCommand('insertHTML', false, '<div class="video-embed"><iframe src="' + src2 + '" allowfullscreen title="Video"></iframe></div><p><br></p>');
            }
            else if (cmd === 'scripture' || cmd === 'callout') {
                var sel = window.getSelection(); var text = sel && sel.toString() ? sel.toString() : (cmd === 'scripture' ? '“Your word is a lamp to my feet…” (Psalm 119:105)' : 'Write a short note…');
                doc.execCommand('insertHTML', false, '<div class="' + cmd + '">' + text.replace(/[<>&]/g, function (c) { return { '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]; }) + '</div><p><br></p>');
            }
            else { doc.execCommand(cmd, false, null); }
            ta.value = area.innerHTML;
        });
        area.addEventListener('input', function () { ta.value = area.innerHTML; ta.dispatchEvent(new Event('input', { bubbles: true })); });
        area.addEventListener('paste', function (e) {
            // Paste as clean text paragraphs; formatting is re-applied with the toolbar.
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            if (!text) { return; }
            e.preventDefault();
            var html = text.split(/\n{2,}/).map(function (p) { return '<p>' + p.replace(/[<>&]/g, function (c) { return { '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]; }).replace(/\n/g, '<br>') + '</p>'; }).join('');
            doc.execCommand('insertHTML', false, html);
        });
        var form = ta.form; if (form) { form.addEventListener('submit', function () { syncEditors(form); }); }
    });

    /* ---------- Scripture popovers ---------- */
    var pop = null, popTimer = null, popCache = {};
    function hidePop() { if (pop) { pop.remove(); pop = null; } }
    function showPop(a) {
        var ref = a.getAttribute('data-ref');
        var url = (root.getAttribute('data-passage-url') || '/bible/passage') + '?ref=' + encodeURIComponent(ref);
        hidePop();
        pop = doc.createElement('div'); pop.className = 'ref-popover'; pop.setAttribute('role', 'tooltip');
        pop.innerHTML = '<h5></h5><div class="pop-body"><div class="skeleton" style="height:4.5rem"></div></div><div class="mt-1 text-sm"><a href="' + a.href + '">Open in Bible ' + icon('arrow-right') + '</a></div>';
        pop.querySelector('h5').textContent = ref + ' · KJV';
        doc.body.appendChild(pop);
        var r = a.getBoundingClientRect();
        var left = Math.max(12, Math.min(window.scrollX + r.left, window.scrollX + doc.documentElement.clientWidth - pop.offsetWidth - 12));
        pop.style.left = left + 'px';
        pop.style.top = (window.scrollY + r.bottom + 8) + 'px';
        var current = pop;
        var render = function (data) {
            if (current !== pop) { return; }
            var body = current.querySelector('.pop-body'); body.innerHTML = '';
            (data.verses || []).forEach(function (v) { var p = doc.createElement('span'); p.innerHTML = '<sup>' + v.verse + '</sup>'; p.appendChild(doc.createTextNode(v.text + ' ')); body.appendChild(p); });
            if (!data.verses || !data.verses.length) { body.textContent = 'Passage not found.'; }
        };
        if (popCache[ref]) { render(popCache[ref]); return; }
        PJ.get(url).then(function (d) { popCache[ref] = d; render(d); }).catch(function () { if (current === pop) { current.querySelector('.pop-body').textContent = 'Could not load passage.'; } });
    }
    on(doc, 'mouseover', 'a.bible-ref', function (e, a) { clearTimeout(popTimer); popTimer = setTimeout(function () { showPop(a); }, 220); });
    on(doc, 'mouseout', 'a.bible-ref', function () { clearTimeout(popTimer); popTimer = setTimeout(function () { if (pop && !pop.matches(':hover')) { hidePop(); } }, 300); });
    doc.addEventListener('mouseover', function (e) { if (pop && e.target.closest('.ref-popover')) { clearTimeout(popTimer); } });
    doc.addEventListener('mouseout', function (e) { if (pop && e.target.closest('.ref-popover') && !e.relatedTarget?.closest?.('.ref-popover')) { popTimer = setTimeout(hidePop, 300); } });
    on(doc, 'click', 'a.bible-ref', function (e, a) {
        if (window.matchMedia('(hover: none)').matches && (!pop || pop.getAttribute('data-for') !== a.getAttribute('data-ref'))) { e.preventDefault(); showPop(a); pop.setAttribute('data-for', a.getAttribute('data-ref')); }
    });
    doc.addEventListener('click', function (e) { if (pop && !e.target.closest('.ref-popover, a.bible-ref')) { hidePop(); } });

    /* ---------- Timers (quizzes) ---------- */
    $$('[data-timer-seconds]').forEach(function (el) {
        var left = parseInt(el.getAttribute('data-timer-seconds'), 10);
        var form = el.closest('form') || doc.getElementById(el.getAttribute('data-form'));
        var out = el.querySelector('span') || el;
        var tick = function () {
            var m = Math.floor(left / 60), s = left % 60;
            out.textContent = m + ':' + (s < 10 ? '0' : '') + s;
            if (left <= 0) { PJ.toast('Time is up — submitting your answers', 'info'); if (form) { form.submit(); } return; }
            left--; setTimeout(tick, 1000);
        };
        tick();
    });

    /* ---------- Keyboard shortcuts ---------- */
    doc.addEventListener('keydown', function (e) {
        if (e.target.closest('input, textarea, select, [contenteditable="true"]') || e.metaKey || e.ctrlKey || e.altKey) { return; }
        var prev = $('[data-key-prev]'), next = $('[data-key-next]');
        if ((e.key === 'ArrowLeft' || e.key === 'k') && prev) { location.href = prev.href; }
        if ((e.key === 'ArrowRight' || e.key === 'j') && next) { location.href = next.href; }
        if (e.key === '/' && $('[data-search-focus]')) { e.preventDefault(); $('[data-search-focus]').focus(); }
    });

    /* ---------- Misc ---------- */
    on(doc, 'click', '[data-toggle-class]', function (e, b) {
        var parts = b.getAttribute('data-toggle-class').split('@');
        var target = parts[1] ? doc.querySelector(parts[1]) : doc.body;
        if (target) { target.classList.toggle(parts[0]); }
        if (b.hasAttribute('data-remember')) { try { localStorage.setItem('pj-' + b.getAttribute('data-remember'), target.classList.contains(parts[0]) ? '1' : '0'); } catch (err) {} }
    });
    on(doc, 'click', '[data-print]', function (e) { e.preventDefault(); window.print(); });
    on(doc, 'click', '[data-share]', function (e, b) {
        e.preventDefault();
        var data = { title: b.getAttribute('data-share-title') || doc.title, text: b.getAttribute('data-share') || '', url: b.getAttribute('data-share-url') || location.href };
        if (navigator.share) { navigator.share(data).catch(function () {}); }
        else if (navigator.clipboard) { navigator.clipboard.writeText((data.text ? data.text + '\n' : '') + data.url).then(function () { PJ.toast('Link copied — paste it anywhere to share'); }); }
    });
    $$('[data-flash]').forEach(function (el) { PJ.toast(el.getAttribute('data-flash'), el.getAttribute('data-flash-type') || 'success'); el.remove(); });
    $$('[data-autofocus-end]').forEach(function (el) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); });
})();

/* ---------- Copy protection (courses and the flipbook reader) ---------- */
(function () {
    var body = document.body;
    if (!body || !body.classList.contains('protect')) { return; }
    var editable = function (el) { return el && el.closest && el.closest('input, textarea, select, [contenteditable="true"], .allow-select'); };
    var hint;
    var notice = function () {
        if (hint) { return; }
        hint = document.createElement('div');
        hint.className = 'protect-toast';
        hint.textContent = body.getAttribute('data-protect-note') || 'This content is protected.';
        body.appendChild(hint);
        setTimeout(function () { hint.remove(); hint = null; }, 1800);
    };
    document.addEventListener('contextmenu', function (e) { if (!editable(e.target)) { e.preventDefault(); notice(); } });
    ['copy', 'cut'].forEach(function (t) {
        document.addEventListener(t, function (e) { if (!editable(e.target)) { e.preventDefault(); notice(); } });
    });
    document.addEventListener('dragstart', function (e) { if (!editable(e.target)) { e.preventDefault(); } });
    document.addEventListener('keydown', function (e) {
        var k = (e.key || '').toLowerCase(), mod = e.ctrlKey || e.metaKey;
        var inField = editable(e.target);
        // Save, print, view source and developer tools are blocked everywhere;
        // copy/cut/select-all only outside the learner's own writing boxes.
        if ((mod && ['s', 'p', 'u'].indexOf(k) > -1) || k === 'f12' || (mod && e.shiftKey && ['i', 'j', 'c'].indexOf(k) > -1) || (!inField && mod && ['c', 'x', 'a'].indexOf(k) > -1)) {
            e.preventDefault();
            notice();
        }
    });
    window.addEventListener('beforeprint', notice);
    // Faint repeating watermark with the learner's name and email.
    var wm = document.querySelector('.protect-watermark');
    if (wm && wm.dataset.text) {
        var draw = function () {
            wm.innerHTML = '';
            for (var y = -40; y < window.innerHeight + 80; y += 140) {
                for (var x = -60; x < window.innerWidth + 200; x += 340) {
                    var s = document.createElement('span');
                    s.textContent = wm.dataset.text;
                    s.style.left = (x + (y / 140 % 2 ? 170 : 0)) + 'px';
                    s.style.top = y + 'px';
                    wm.appendChild(s);
                }
            }
        };
        draw();
        window.addEventListener('resize', draw);
    }
})();
