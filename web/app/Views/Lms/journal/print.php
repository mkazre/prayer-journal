<?php $kinds = \App\Modules\Lms\Models\JournalEntryModel::KINDS; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><title>My Prayer Journal — <?= esc(display_name()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Literata:ital,wght@0,400;0,600;1,400&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Literata', Georgia, serif; color: #1a1424; max-width: 760px; margin: 2rem auto; padding: 0 1.5rem; line-height: 1.6; }
        header { display: flex; align-items: center; gap: 1rem; border-bottom: 2px solid #c9a227; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        header img { width: 56px; height: 56px; border-radius: 12px; }
        h1 { font-family: 'Playfair Display', serif; margin: 0; color: #4b1e82; }
        .meta { color: #776f85; font-size: .9rem; }
        .entry { padding: .9rem 0; border-bottom: 1px solid #e6e1ee; break-inside: avoid; }
        .entry h3 { margin: 0 0 .2rem; font-size: 1rem; }
        .kind { font: 700 .7rem system-ui, sans-serif; text-transform: uppercase; letter-spacing: .1em; color: #8a6b0f; }
        .answered { color: #13795b; font-weight: 600; font-family: system-ui, sans-serif; font-size: .8rem; }
        .body { white-space: pre-wrap; }
        .toolbar { margin-bottom: 1rem; font-family: system-ui, sans-serif; }
        @media print { .toolbar { display: none; } body { margin: 0 auto; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Print or save as PDF</button></div>
    <header>
        <img src="<?= logo_url() ?>" alt="<?= esc(site_name()) ?> logo">
        <div><h1>My Prayer Journal</h1><div class="meta"><?= esc(display_name()) ?> · <?= (int) $stats['total'] ?> entries · <?= (int) $stats['answered'] ?> answered prayers · printed <?= date('j F Y') ?></div></div>
    </header>
    <?php foreach ($entries as $e) : ?>
        <div class="entry">
            <div class="kind"><?= esc($kinds[$e['kind']][0] ?? 'Note') ?> · <?= date('j M Y', strtotime((string) ($e['entry_date'] ?: $e['created_at']))) ?></div>
            <?php if ($e['title']) : ?><h3><?= esc($e['title']) ?></h3><?php endif ?>
            <div class="body"><?= esc($e['body']) ?></div>
            <?php if ($e['scripture']) : ?><div class="meta"><?= esc($e['scripture']) ?></div><?php endif ?>
            <?php if ($e['is_answered']) : ?><div class="answered">Answered <?= nice_date($e['answered_at']) ?><?= $e['answer_note'] ? ' — ' . esc($e['answer_note']) : '' ?></div><?php endif ?>
        </div>
    <?php endforeach ?>
</body>
</html>
