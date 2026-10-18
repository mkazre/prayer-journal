<?php
/**
 * Flipbook reader for a purchased PDF.
 *
 * PDF pages are drawn onto canvases by PDF.js (no text layer, so nothing to
 * select) and turned with StPageFlip. Copying, right-click, printing and
 * dragging are blocked by the shared body.protect script, and the reader's
 * name and email are watermarked over the page.
 *
 * @var array  $item
 * @var array  $order
 * @var string $fileUrl
 * @var string $watermark
 * @var bool   $inApp     opened from the mobile app (no site chrome to go back to)
 */
?>
<?= $this->include('partials/html_open') ?>
<head>
    <?= $this->include('partials/head') ?>
    <meta name="robots" content="noindex">
    <style>
        body.flip-body { margin: 0; min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column; color: #fff; overflow: hidden;
            background: radial-gradient(900px 500px at 85% -10%, rgba(201,162,39,.22), transparent 60%), linear-gradient(140deg, #0f1321 0%, #161c2e 50%, #232d47 100%); }
        .flip-bar { display: flex; align-items: center; gap: .5rem; padding: .6rem 1rem; background: rgba(10,13,22,.55); backdrop-filter: blur(8px); border-bottom: 1px solid rgba(255,255,255,.08); z-index: 5; }
        .flip-bar .title { font-family: var(--font-display); font-size: 1.05rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex: 1; }
        .flip-bar .btn { --btn-bg: rgba(255,255,255,.08); --btn-fg: #fff; --btn-bd: rgba(255,255,255,.14); }
        .flip-bar .btn:hover { background: rgba(255,255,255,.16); color: #fff; }
        .flip-count { font-size: .85rem; color: rgba(255,255,255,.75); min-width: 5.5rem; text-align: center; font-variant-numeric: tabular-nums; }
        .flip-stage { flex: 1; display: flex; align-items: center; justify-content: center; padding: 1.25rem; position: relative; min-height: 0; }
        .flip-book { box-shadow: 0 30px 80px -20px rgba(0,0,0,.7); transition: transform .5s var(--ease); transform-origin: center; }
        .flip-book.is-intro { transform: scale(.88) rotateX(8deg); opacity: 0; }
        .flip-book.is-zoomed { transform: scale(1.45); }
        .flip-page { background: #fffdf8; overflow: hidden; }
        .flip-page canvas { width: 100%; height: 100%; display: block; pointer-events: none; }
        .flip-page .flip-mark { position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none; font: 600 13px/1.3 var(--font-body); color: rgba(35,45,71,.09); transform: rotate(-28deg); text-align: center; padding: 1rem; }
        .flip-hard { background: #232d47; }
        .flip-loading { position: absolute; inset: 0; display: grid; place-items: center; text-align: center; color: rgba(255,255,255,.85); }
        .flip-loading .bar { width: 220px; height: 4px; border-radius: 4px; background: rgba(255,255,255,.15); overflow: hidden; margin: 1rem auto 0; }
        .flip-loading .bar span { display: block; height: 100%; width: 0; background: var(--accent); transition: width .2s; }
        .flip-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 4; }
        .flip-nav.prev { left: .75rem; } .flip-nav.next { right: .75rem; }
        .flip-hint { position: absolute; bottom: .9rem; left: 50%; transform: translateX(-50%); font-size: .8rem; color: rgba(255,255,255,.6); }
        @media (max-width: 640px) { .flip-nav { display: none; } .flip-bar .hide-xs { display: none; } }
        @media print { body { display: none !important; } }
    </style>
</head>
<body class="flip-body protect" oncopy="return false" data-protect-note="This book is protected — it can only be read here.">
    <div class="protect-watermark" aria-hidden="true" data-text="<?= esc($watermark, 'attr') ?>"></div>
    <div class="flip-bar">
        <?php if (! $inApp) : ?><a class="btn btn-icon btn-sm" href="<?= site_url('orders/' . $order['id']) ?>" aria-label="Back to order"><?= icon('arrow-left') ?></a><?php endif ?>
        <span class="title"><?= esc($item['title_snapshot']) ?></span>
        <button class="btn btn-icon btn-sm" type="button" id="fb-prev" aria-label="Previous page"><?= icon('chevron-left') ?></button>
        <span class="flip-count" id="fb-count" aria-live="polite">—</span>
        <button class="btn btn-icon btn-sm" type="button" id="fb-next" aria-label="Next page"><?= icon('chevron-right') ?></button>
        <button class="btn btn-icon btn-sm hide-xs" type="button" id="fb-zoom" aria-label="Zoom" aria-pressed="false"><?= icon('zoom-in') ?></button>
        <button class="btn btn-icon btn-sm" type="button" id="fb-sound" aria-label="Page-turn sound" aria-pressed="true"><?= icon('headphones') ?></button>
        <button class="btn btn-icon btn-sm hide-xs" type="button" id="fb-full" aria-label="Full screen"><?= icon('maximize') ?></button>
    </div>
    <div class="flip-stage" id="fb-stage">
        <div class="flip-loading" id="fb-loading"><div><div style="font-family:var(--font-display);font-size:1.3rem">Opening your book…</div><div class="bar"><span id="fb-progress"></span></div></div></div>
        <button class="btn btn-icon flip-nav prev" type="button" id="fb-prev2" aria-label="Previous page" style="--btn-bg:rgba(255,255,255,.08);--btn-fg:#fff;--btn-bd:rgba(255,255,255,.14)"><?= icon('chevron-left') ?></button>
        <div class="flip-book is-intro" id="fb-book"></div>
        <button class="btn btn-icon flip-nav next" type="button" id="fb-next2" aria-label="Next page" style="--btn-bg:rgba(255,255,255,.08);--btn-fg:#fff;--btn-bd:rgba(255,255,255,.14)"><?= icon('chevron-right') ?></button>
        <div class="flip-hint" id="fb-hint">Click or swipe the page corner to turn · ← → keys</div>
    </div>

    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/pdfjs/pdf.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/page-flip/page-flip.browser.js') ?>"></script>
    <script>
    (function () {
        var FILE = <?= json_encode($fileUrl) ?>, MARK = <?= json_encode($watermark) ?>;
        var book = document.getElementById('fb-book'), stage = document.getElementById('fb-stage');
        var count = document.getElementById('fb-count'), loading = document.getElementById('fb-loading'), progress = document.getElementById('fb-progress');
        var soundOn = true, audio = null, flip = null, pdf = null, pages = [], rendered = {}, scale = Math.min(window.devicePixelRatio || 1, 2);
        pdfjsLib.GlobalWorkerOptions.workerSrc = <?= json_encode(base_url('assets/vendor/pdfjs/pdf.worker.min.js')) ?>;

        try { soundOn = localStorage.getItem('pj-flip-sound') !== 'off'; } catch (e) {}
        function setSoundBtn() { var b = document.getElementById('fb-sound'); b.setAttribute('aria-pressed', soundOn ? 'true' : 'false'); b.style.opacity = soundOn ? 1 : .45; }
        setSoundBtn();

        // A soft paper "swish": filtered noise with a quick swell and fade.
        function flipSound() {
            if (!soundOn) { return; }
            try {
                audio = audio || new (window.AudioContext || window.webkitAudioContext)();
                var dur = .42, n = Math.floor(audio.sampleRate * dur), buf = audio.createBuffer(1, n, audio.sampleRate), d = buf.getChannelData(0);
                for (var i = 0; i < n; i++) { var t = i / n; d[i] = (Math.random() * 2 - 1) * Math.pow(Math.sin(Math.PI * Math.min(1, t * 1.6)), 2) * (1 - t); }
                var src = audio.createBufferSource(), bp = audio.createBiquadFilter(), hp = audio.createBiquadFilter(), g = audio.createGain();
                src.buffer = buf; bp.type = 'bandpass'; bp.frequency.setValueAtTime(2600, audio.currentTime); bp.frequency.exponentialRampToValueAtTime(900, audio.currentTime + dur); bp.Q.value = .7;
                hp.type = 'highpass'; hp.frequency.value = 300; g.gain.value = .55;
                src.connect(bp); bp.connect(hp); hp.connect(g); g.connect(audio.destination); src.start();
            } catch (e) {}
        }

        function renderPage(i) {
            if (i < 0 || i >= pages.length || rendered[i]) { return; }
            rendered[i] = true;
            pdf.getPage(i + 1).then(function (page) {
                var vp = page.getViewport({ scale: 1 }), box = pages[i].getBoundingClientRect();
                var target = Math.max(box.width || 500, 400) * scale / vp.width;
                var v = page.getViewport({ scale: target }), c = pages[i].querySelector('canvas');
                c.width = Math.floor(v.width); c.height = Math.floor(v.height);
                page.render({ canvasContext: c.getContext('2d'), viewport: v });
            });
        }
        // Draw the pages around the current spread; the rest load as you turn.
        function renderAround(i) { for (var k = i - 2; k <= i + 5; k++) { renderPage(k); } }
        function updateCount() {
            var i = flip.getCurrentPageIndex(), total = pages.length, two = flip.getOrientation() === 'landscape' && i > 0 && i < total - 1;
            count.textContent = (two ? (i + 1) + '–' + Math.min(i + 2, total) : (i + 1)) + ' / ' + total;
        }

        var task = pdfjsLib.getDocument({ url: FILE, withCredentials: true, disableAutoFetch: false, isEvalSupported: false });
        task.onProgress = function (p) { if (p.total) { progress.style.width = Math.round(p.loaded / p.total * 100) + '%'; } };
        task.promise.then(function (doc) {
            pdf = doc;
            return doc.getPage(1);
        }).then(function (first) {
            var vp = first.getViewport({ scale: 1 }), ratio = vp.height / vp.width;
            // Phones and narrow windows read one page at a time; wider screens get a two-page spread.
            var narrow = stage.clientWidth < 760, maxH = stage.clientHeight - 40;
            var maxW = narrow ? stage.clientWidth - 24 : (stage.clientWidth - 140) / 2;
            var w = Math.max(160, Math.min(maxW, maxH / ratio, 620)), h = Math.round(w * ratio);
            var minW = narrow ? Math.ceil(stage.clientWidth / 2) + 1 : 160; // a minimum over half the width keeps it single-page
            for (var i = 0; i < pdf.numPages; i++) {
                var el = document.createElement('div');
                el.className = 'flip-page' + (i === 0 || i === pdf.numPages - 1 ? ' flip-hard' : '');
                el.setAttribute('data-density', i === 0 || i === pdf.numPages - 1 ? 'hard' : 'soft');
                el.innerHTML = '<canvas></canvas><div class="flip-mark"></div>';
                el.querySelector('.flip-mark').textContent = MARK;
                book.appendChild(el); pages.push(el);
            }
            flip = new St.PageFlip(book, {
                // Stretch down on small screens, but never past the size that fits this window.
                width: Math.round(w), height: h, size: 'stretch', minWidth: Math.min(minW, Math.round(w)), maxWidth: Math.round(w), minHeight: Math.round(Math.min(minW, w) * ratio), maxHeight: h,
                showCover: true, maxShadowOpacity: .55, mobileScrollSupport: false, usePortrait: true, flippingTime: 850, drawShadow: true,
            });
            flip.loadFromHTML(pages);
            flip.on('flip', function (e) { renderAround(e.data); updateCount(); });
            flip.on('changeState', function (e) { if (e.data === 'flipping') { flipSound(); } });
            flip.on('changeOrientation', updateCount);
            renderAround(0); updateCount();
            loading.remove();
            requestAnimationFrame(function () { book.classList.remove('is-intro'); });
            // Opens like a book: after a beat the cover turns to the first spread.
            setTimeout(function () { if (flip.getCurrentPageIndex() === 0 && pages.length > 2) { flip.flipNext(); } }, 900);
        }).catch(function () {
            loading.innerHTML = '<div><div style="font-family:var(--font-display);font-size:1.3rem">We couldn’t open this book</div><p style="color:rgba(255,255,255,.7)">Please refresh the page, or contact us if it keeps happening.</p></div>';
        });

        function prev() { if (flip) { flip.flipPrev(); } }
        function next() { if (flip) { flip.flipNext(); } }
        ['fb-prev', 'fb-prev2'].forEach(function (id) { document.getElementById(id).addEventListener('click', prev); });
        ['fb-next', 'fb-next2'].forEach(function (id) { document.getElementById(id).addEventListener('click', next); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft' || e.key === 'PageUp') { prev(); }
            if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') { e.preventDefault(); next(); }
        });
        document.getElementById('fb-sound').addEventListener('click', function () {
            soundOn = !soundOn; setSoundBtn();
            try { localStorage.setItem('pj-flip-sound', soundOn ? 'on' : 'off'); } catch (e) {}
            if (soundOn) { flipSound(); }
        });
        document.getElementById('fb-zoom').addEventListener('click', function () {
            var on = book.classList.toggle('is-zoomed'); this.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        document.getElementById('fb-full').addEventListener('click', function () {
            if (document.fullscreenElement) { document.exitFullscreen(); } else if (document.documentElement.requestFullscreen) { document.documentElement.requestFullscreen(); }
        });
        setTimeout(function () { var h = document.getElementById('fb-hint'); if (h) { h.style.transition = 'opacity .6s'; h.style.opacity = 0; } }, 6000);
    })();
    </script>
</body>
</html>
