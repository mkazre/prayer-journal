<?php
/**
 * Shared branded error page. Kept self-contained (inline CSS, root-relative
 * asset paths, no database) so it renders even when the app is unhealthy.
 *
 * @var string      $code
 * @var string      $heading
 * @var string      $text
 * @var string|null $detail  shown outside production only
 */
$name = 'Prayer Journal';
try {
    $name = function_exists('site_name') ? site_name() : $name;
} catch (\Throwable $e) {
    // keep the default
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= esc($heading) ?> · <?= esc($name) ?></title>
    <link rel="icon" href="/assets/img/favicon-64.png">
    <style>
        :root { color-scheme: light dark; --bg: #f6f7fa; --card: #fff; --text: #12151c; --muted: #676c79; --brand: #232d47; --border: #e0e4ef; }
        @media (prefers-color-scheme: dark) { :root { --bg: #0d1017; --card: #161a24; --text: #f1f3f8; --muted: #959cad; --brand: #94a3ca; --border: #272e40; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: var(--bg); color: var(--text); font: 16px/1.6 Inter, system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { width: 100%; max-width: 520px; text-align: center; background: var(--card); border: 1px solid var(--border); border-radius: 22px; padding: 40px 28px; }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none; font-weight: 800; font-size: 18px; }
        .brand img { width: 48px; height: 48px; border-radius: 12px; }
        .code { font-size: 64px; font-weight: 800; letter-spacing: -2px; color: var(--brand); margin: 28px 0 0; line-height: 1; }
        h1 { font-size: 22px; margin: 10px 0 6px; }
        p { color: var(--muted); margin: 0 0 24px; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 18px; border-radius: 12px; font-weight: 700; text-decoration: none; border: 1px solid var(--border); color: var(--text); }
        .btn-primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn svg { width: 18px; height: 18px; }
        pre { text-align: left; white-space: pre-wrap; font-size: 13px; color: var(--muted); background: var(--bg); padding: 12px; border-radius: 10px; margin: 24px 0 0; }
        blockquote { margin: 28px 0 0; font-family: Georgia, serif; font-style: italic; color: var(--muted); font-size: 15px; }
    </style>
</head>
<body>
    <main>
        <a class="brand" href="/"><img src="/assets/img/logo-mark.png" alt="<?= esc($name, 'attr') ?> logo"><span><?= esc($name) ?></span></a>
        <div class="code"><?= esc($code) ?></div>
        <h1><?= esc($heading) ?></h1>
        <p><?= esc($text) ?></p>
        <div class="actions">
            <a class="btn btn-primary" href="/"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>Go home</a>
            <a class="btn" href="/search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>Search</a>
        </div>
        <?php if (! empty($detail) && ENVIRONMENT !== 'production') : ?><pre><?= esc($detail) ?></pre><?php endif ?>
        <blockquote>“The Lord is my shepherd; I shall not want.” — Psalm 23:1</blockquote>
    </main>
</body>
</html>
