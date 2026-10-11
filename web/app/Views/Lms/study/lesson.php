<?= $this->extend('layouts/bare') ?>
<?= $this->section('content') ?>
<?php
$type      = $lesson['type'] ?: 'text';
$embed     = video_embed_url($lesson['video_url']);
$done      = $current['done'] ?? false;
$itemUrl   = static fn (?array $it) => $it ? site_url('study/' . $course['slug'] . '/' . $it['kind'] . '/' . $it['id']) : null;
$kinds     = \App\Modules\Lms\Models\JournalEntryModel::KINDS;
$minutes   = (int) $lesson['duration_mins'] ?: reading_minutes($lesson['content']);
?>
<div class="player" style="--course-brand:<?= esc($settings['brand_color'], 'attr') ?>">
    <?= view('Lms/study/_sidebar', compact('course', 'sections', 'current', 'enrollment', 'preview', 'settings')) ?>
    <div class="player-main">
        <?= view('Lms/study/_top', ['course' => $course, 'current' => $current, 'topActions' => '<span class="text-xs text-muted hide-sm"><span class="kbd">←</span> <span class="kbd">→</span> to navigate</span>']) ?>
        <div class="player-content">
            <header class="lesson-head mb-3">
                <span class="eyebrow"><?= icon(lesson_icon($type)) ?><?= lesson_type_label($type) ?> · <?= $minutes ?> min<?= $done ? ' · ' . icon('check') . ' Completed' : '' ?></span>
                <h1 class="mt-1"><?= esc($lesson['title']) ?></h1>
                <?php if ($lesson['summary'] && $type !== 'devotion') : ?><p class="text-lg text-2 mb-0"><?= esc($lesson['summary']) ?></p><?php endif ?>
            </header>

            <?php if ($embed) : ?>
                <div class="video-embed mb-3"><iframe src="<?= esc($embed, 'attr') ?>" title="<?= esc($lesson['title'], 'attr') ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
            <?php elseif ($lesson['video_url'] && preg_match('/\.(mp4|webm)(\?|$)/i', $lesson['video_url'])) : ?>
                <div class="video-embed mb-3"><video src="<?= esc($lesson['video_url'], 'attr') ?>" controls playsinline preload="metadata"></video></div>
            <?php endif ?>
            <?php if ($type === 'audio' && $lesson['attachment_path']) : ?>
                <div class="card card-body mb-3 row"><?= icon('headphones', 'icon-lg text-brand') ?><audio class="grow" controls preload="metadata" src="<?= base_url($lesson['attachment_path']) ?>" style="width:100%"></audio></div>
            <?php elseif ($lesson['attachment_path'] && str_ends_with(strtolower($lesson['attachment_path']), '.pdf')) : ?>
                <div class="card mb-3" style="overflow:hidden">
                    <iframe src="<?= base_url($lesson['attachment_path']) ?>#view=FitH" title="Document" style="width:100%;height:70vh;border:0;display:block"></iframe>
                    <div class="card-footer row between"><span class="text-sm"><?= icon('file') ?> Lesson document</span><a class="btn btn-sm" href="<?= base_url($lesson['attachment_path']) ?>" download><?= icon('download') ?>Download</a></div>
                </div>
            <?php endif ?>

            <?= view('Lms/study/_scripture', ['verses' => $verses, 'reference' => $lesson['bible_reference']]) ?>

            <article class="prose" style="max-width:none"><?= scripture_links(clean_html($lesson['content'])) ?></article>

            <?php if ($resources || ($lesson['attachment_path'] && $type !== 'audio' && ! str_ends_with(strtolower((string) $lesson['attachment_path']), '.pdf'))) : ?>
                <section class="card mt-4">
                    <div class="card-header"><h2><?= icon('paperclip') ?> Resources</h2></div>
                    <div class="divider-list">
                        <?php if ($lesson['attachment_path'] && $type !== 'audio' && ! str_ends_with(strtolower($lesson['attachment_path']), '.pdf')) : ?>
                            <a class="card-body-sm row" href="<?= base_url($lesson['attachment_path']) ?>" download><?= icon('download') ?><span class="grow">Lesson file</span></a>
                        <?php endif ?>
                        <?php foreach ($resources as $r) : if (empty($r['url'])) { continue; } ?>
                            <a class="card-body-sm row" href="<?= esc($r['url'], 'attr') ?>" target="_blank" rel="noopener"><?= icon('external') ?><span class="grow"><?= esc($r['label'] ?: $r['url']) ?></span></a>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endif ?>

            <?php if ($prompts) : ?>
                <section class="journal-prompts" aria-labelledby="journal-h">
                    <div class="row between">
                        <div><h2 id="journal-h" class="mb-0" style="font-size:1.45rem">Your journal</h2><p class="text-sm text-muted mb-0">Saved privately to your <a href="<?= site_url('journal') ?>">Prayer Journal</a> as you type.</p></div>
                        <span class="badge badge-accent"><?= icon('lock') ?>Private</span>
                    </div>
                    <?php foreach ($prompts as $p) : [$kLabel, $kIcon, $kClass] = $kinds[$p['kind']] ?? $kinds['note']; $existing = $answers[$p['label']] ?? null; ?>
                        <div class="prompt-card" data-autosave-scope>
                            <div class="prompt-card-head">
                                <span class="kind-icon <?= $kClass ?>"><?= icon($kIcon) ?></span>
                                <div class="grow"><h4><?= esc($p['label']) ?></h4><?php if ($p['hint']) : ?><div class="help"><?= esc($p['hint']) ?></div><?php endif ?></div>
                            </div>
                            <textarea class="textarea" name="body" aria-label="<?= esc($p['label'], 'attr') ?>" placeholder="<?= $p['kind'] === 'prayer' ? 'Lord, I bring before You…' : ($p['kind'] === 'gratitude' ? 'Today I am thankful for…' : 'What is God saying to you?') ?>"
                                data-autosave-url="<?= site_url('study/' . $course['slug'] . '/lesson/' . $lesson['id'] . '/journal') ?>"
                                data-autosave-extra="<?= json_attr(['prompt' => $p['key']]) ?>"><?= esc((string) ($existing['body'] ?? '')) ?></textarea>
                            <div class="prompt-card-foot">
                                <span class="save-state <?= $existing ? 'is-saved' : '' ?>"><?= $existing ? icon('check') . ' Saved ' . time_ago($existing['updated_at'] ?? $existing['created_at']) : 'Not started' ?></span>
                                <?php if ($p['kind'] === 'prayer') : ?><span><?= icon('info') ?> Mark it answered later in your journal</span><?php endif ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </section>
            <?php endif ?>

            <section class="mt-4" id="discussion">
                <div class="tabs" role="tablist">
                    <button class="tab is-active" data-tab="tab-notes" role="tab"><?= icon('note') ?>My notes</button>
                    <?php if ($settings['discussions']) : ?><button class="tab" data-tab="tab-discuss" role="tab"><?= icon('messages') ?>Discussion <span class="badge"><?= count($threads) ?></span></button><?php endif ?>
                </div>
                <div class="tab-panel is-active" id="tab-notes" data-autosave-scope>
                    <textarea class="textarea" name="body" rows="5" placeholder="Private notes for this lesson — only you can see them." style="font-family:var(--reading-font)"
                        data-autosave-url="<?= site_url('study/' . $course['slug'] . '/lesson/' . $lesson['id'] . '/note') ?>"><?= esc((string) ($note['body'] ?? '')) ?></textarea>
                    <div class="row between mt-1 text-xs text-muted"><span class="save-state"><?= $note ? 'Saved ' . time_ago($note['updated_at']) : '' ?></span><a href="<?= site_url('study/' . $course['slug'] . '/notes') ?>">All my notes for this course <?= icon('arrow-right') ?></a></div>
                </div>
                <?php if ($settings['discussions']) : ?>
                    <div class="tab-panel" id="tab-discuss">
                        <form method="post" action="<?= site_url('study/' . $course['slug'] . '/lesson/' . $lesson['id'] . '/discuss') ?>" class="card card-body mb-2">
                            <?= csrf_field() ?>
                            <div class="row items-start row-nowrap"><?= avatar((int) auth()->id(), display_name()) ?>
                                <div class="grow"><textarea class="textarea" name="body" rows="3" placeholder="Share an insight or ask a question about this lesson…" required></textarea>
                                    <div class="row end mt-1"><button class="btn btn-primary btn-sm" type="submit"><?= icon('send') ?>Post</button></div></div>
                            </div>
                        </form>
                        <?php if ($threads === []) : ?><p class="text-sm text-muted">No comments yet. Be the first to share.</p><?php endif ?>
                        <div class="stack">
                            <?php foreach ($threads as $t) : $tName = display_name(['id' => $t['user_id'], 'username' => $t['username']]); ?>
                                <div class="card card-body">
                                    <div class="row items-start row-nowrap"><?= avatar((int) $t['user_id'], $tName) ?>
                                        <div class="grow">
                                            <div class="row gap-sm"><strong><?= esc($tName) ?></strong><span class="text-xs text-muted"><?= time_ago($t['created_at']) ?></span></div>
                                            <p class="mb-1 mt-1" style="white-space:pre-wrap"><?= esc($t['body']) ?></p>
                                            <?php foreach ($t['replies'] as $r) : $rName = display_name(['id' => $r['user_id'], 'username' => $r['username']]); ?>
                                                <div class="row items-start row-nowrap mt-1 panel"><?= avatar((int) $r['user_id'], $rName, 'avatar-sm') ?>
                                                    <div><div class="row gap-sm"><strong class="text-sm"><?= esc($rName) ?></strong><span class="text-xs text-muted"><?= time_ago($r['created_at']) ?></span></div><p class="mb-0 text-sm" style="white-space:pre-wrap"><?= esc($r['body']) ?></p></div>
                                                </div>
                                            <?php endforeach ?>
                                            <details class="mt-1"><summary class="text-sm text-brand" style="cursor:pointer">Reply</summary>
                                                <form method="post" action="<?= site_url('study/' . $course['slug'] . '/lesson/' . $lesson['id'] . '/discuss') ?>" class="mt-1">
                                                    <?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= $t['id'] ?>">
                                                    <textarea class="textarea" name="body" rows="2" required></textarea>
                                                    <button class="btn btn-sm mt-1" type="submit">Reply</button>
                                                </form>
                                            </details>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endif ?>
            </section>
        </div>

        <nav class="lesson-nav no-print" aria-label="Lesson navigation">
            <?php if ($prev) : ?><a class="btn btn-ghost" href="<?= $itemUrl($prev) ?>" data-key-prev><?= icon('arrow-left') ?><span class="hide-sm">Previous</span></a><?php else : ?><span></span><?php endif ?>
            <?php if ($enrollment) : ?>
                <form method="post" action="<?= site_url('study/' . $course['slug'] . '/lesson/' . $lesson['id'] . '/complete') ?>" class="row gap-sm">
                    <?= csrf_field() ?>
                    <?php if ($done) : ?>
                        <input type="hidden" name="undo" value="1">
                        <button class="btn btn-soft" type="submit" title="Mark as not complete"><?= icon('check-circle') ?>Completed</button>
                    <?php else : ?>
                        <input type="hidden" name="next" value="<?= $next && ! $next['locked'] ? $itemUrl($next) : '' ?>">
                        <button class="btn btn-primary" type="submit"><?= icon('check') ?><?= $next ? 'Complete & continue' : 'Complete lesson' ?></button>
                    <?php endif ?>
                </form>
            <?php else : ?>
                <span class="badge badge-warning"><?= icon('eye') ?>Preview mode</span>
            <?php endif ?>
            <?php if ($next) : ?><a class="btn btn-ghost <?= $next['locked'] && ! $preview ? 'is-disabled' : '' ?>" href="<?= $itemUrl($next) ?>" data-key-next><span class="hide-sm">Next</span><?= icon('arrow-right') ?></a><?php else : ?><a class="btn btn-ghost" href="<?= site_url('study/' . $course['slug']) ?>"><?= icon('home') ?><span class="hide-sm">Overview</span></a><?php endif ?>
        </nav>
    </div>
</div>
<?= $this->endSection() ?>
