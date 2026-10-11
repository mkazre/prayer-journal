<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$kinds = \App\Modules\Lms\Models\JournalEntryModel::KINDS;
$q     = static fn (array $over) => site_url('journal') . '?' . http_build_query(array_filter(array_merge($filters, ['page' => null], $over)));
$pct   = ($stats['prayers'] ?? 0) > 0 ? (int) round($stats['answered'] / $stats['prayers'] * 100) : 0;
?>
<div class="page-head-band">
    <div class="container page-head">
        <div class="row between items-start">
            <div>
                <span class="eyebrow"><?= icon('notebook') ?>Private to you</span>
                <h1 class="mt-1">My Prayer Journal</h1>
                <p>Your prayer points, meditations and gratitude in one place. Tick off prayers as God answers — and look back on His faithfulness.</p>
            </div>
            <div class="row">
                <a class="btn" href="<?= site_url('journal/print') ?>" target="_blank" rel="noopener"><?= icon('printer') ?>Print / PDF</a>
                <button class="btn btn-primary" type="button" data-modal-open="new-entry"><?= icon('plus') ?>New entry</button>
            </div>
        </div>
        <div class="grid grid-4 mt-3">
            <div class="card stat"><div class="stat-label"><?= icon('notebook') ?>Entries</div><div class="stat-value"><?= (int) ($stats['total'] ?? 0) ?></div></div>
            <div class="card stat"><div class="stat-label"><?= icon('hands') ?>Prayer points</div><div class="stat-value"><?= (int) ($stats['prayers'] ?? 0) ?></div></div>
            <div class="card stat"><div class="stat-label"><?= icon('check-circle') ?>Answered</div><div class="stat-value"><?= (int) ($stats['answered'] ?? 0) ?> <span class="text-sm text-success"><?= $pct ?>%</span></div></div>
            <div class="card stat"><div class="stat-label"><?= icon('sunrise') ?>Gratitude notes</div><div class="stat-value"><?= (int) ($stats['gratitude'] ?? 0) ?></div></div>
        </div>
    </div>
</div>

<div class="container">
    <div class="toolbar">
        <div class="chips">
            <a class="chip <?= $filters['kind'] === '' && $filters['status'] === '' ? 'is-active' : '' ?>" href="<?= $q(['kind' => null, 'status' => null]) ?>">All</a>
            <?php foreach ($kinds as $k => [$label, $ic]) : ?>
                <a class="chip <?= $filters['kind'] === $k ? 'is-active' : '' ?>" href="<?= $q(['kind' => $k, 'status' => null]) ?>"><?= icon($ic) ?><?= esc($label) ?>s</a>
            <?php endforeach ?>
            <a class="chip <?= $filters['status'] === 'open' ? 'is-active' : '' ?>" href="<?= $q(['status' => 'open', 'kind' => null]) ?>"><?= icon('clock') ?>Still praying</a>
            <a class="chip <?= $filters['status'] === 'answered' ? 'is-active' : '' ?>" href="<?= $q(['status' => 'answered', 'kind' => null]) ?>"><?= icon('check-circle') ?>Answered</a>
        </div>
        <form class="row gap-sm" method="get" action="<?= site_url('journal') ?>">
            <?php foreach (['kind', 'status'] as $k) : if ($filters[$k] !== '') : ?><input type="hidden" name="<?= $k ?>" value="<?= esc($filters[$k], 'attr') ?>"><?php endif; endforeach ?>
            <?php if ($courses) : ?>
                <select class="select input-sm" name="course" data-autosubmit style="width:auto;max-width:220px" aria-label="Filter by course">
                    <option value="">All sources</option>
                    <?php foreach ($courses as $c) : ?><option value="<?= $c['id'] ?>" <?= $filters['course'] === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['title']) ?></option><?php endforeach ?>
                </select>
            <?php endif ?>
            <div class="input-icon"><?= icon('search') ?><input class="input input-sm" type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Search your journal" style="width:220px"></div>
        </form>
    </div>

    <?php if ($grouped === []) : ?>
        <div class="card empty">
            <div class="feature-icon gold"><?= icon('feather', 'icon-lg') ?></div>
            <h3><?= $filters['q'] || $filters['kind'] || $filters['status'] ? 'Nothing matches those filters' : 'Your journal is waiting' ?></h3>
            <p>Write freely, or open a devotion — its prompts save straight into this journal.</p>
            <div class="row center"><button class="btn btn-primary" type="button" data-modal-open="new-entry"><?= icon('plus') ?>Write your first entry</button><a class="btn" href="<?= site_url('my-learning') ?>">Go to my courses</a></div>
        </div>
    <?php endif ?>

    <div class="stack stack-lg">
        <?php foreach ($grouped as $date => $entries) : ?>
            <section>
                <h2 class="text-sm uppercase text-muted mb-1" style="font-family:var(--font-body);letter-spacing:.1em"><?= $date === date('Y-m-d') ? 'Today' : ($date === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('l, j F Y', strtotime($date))) ?></h2>
                <div class="card">
                    <?php foreach ($entries as $e) : [$kLabel, $kIcon, $kClass] = $kinds[$e['kind']] ?? $kinds['note']; ?>
                        <article class="journal-entry <?= $e['is_answered'] ? 'is-answered' : '' ?>" id="entry-<?= $e['id'] ?>">
                            <div class="row items-start row-nowrap gap-lg">
                                <span class="kind-icon <?= $kClass ?>" title="<?= esc($kLabel, 'attr') ?>"><?= icon($kIcon) ?></span>
                                <div class="grow">
                                    <div class="row between">
                                        <div class="row gap-sm">
                                            <strong><?= esc($e['title'] ?: $kLabel) ?></strong>
                                            <?php if ($e['is_answered']) : ?><span class="badge badge-success"><?= icon('check') ?>Answered <?= nice_date($e['answered_at']) ?></span><?php endif ?>
                                        </div>
                                        <div class="dropdown" data-dropdown>
                                            <button class="btn btn-ghost btn-icon btn-sm" type="button" data-dropdown-toggle aria-label="Entry actions"><?= icon('more') ?></button>
                                            <div class="dropdown-menu">
                                                <button class="menu-item" type="button" data-modal-open="edit-<?= $e['id'] ?>"><?= icon('edit') ?>Edit</button>
                                                <?php if ($e['kind'] === 'prayer') : ?>
                                                    <button class="menu-item" type="button" data-modal-open="answer-<?= $e['id'] ?>"><?= icon($e['is_answered'] ? 'refresh' : 'check-circle') ?><?= $e['is_answered'] ? 'Mark as still praying' : 'Mark answered…' ?></button>
                                                <?php endif ?>
                                                <button class="menu-item" type="button" data-copy="<?= esc($e['body'], 'attr') ?>"><?= icon('copy') ?>Copy text</button>
                                                <form method="post" action="<?= site_url('journal/' . $e['id'] . '/delete') ?>" data-confirm="Delete this entry? This cannot be undone.">
                                                    <?= csrf_field() ?><button class="menu-item danger" type="submit"><?= icon('trash') ?>Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="body"><?= esc($e['body']) ?></div>
                                    <?php if ($e['answer_note']) : ?><div class="alert alert-success mb-1" style="padding:.6rem .9rem"><?= icon('sparkles') ?><div><strong>Testimony:</strong> <?= esc($e['answer_note']) ?></div></div><?php endif ?>
                                    <div class="row gap-sm text-xs text-muted">
                                        <?php if ($e['scripture']) : ?><span><?= scripture_links(esc($e['scripture'])) ?></span><?php endif ?>
                                        <?php if ($e['lesson_title']) : ?><a href="<?= site_url('study/' . $e['course_slug'] . '/lesson/' . $e['lesson_id']) ?>"><?= icon('feather') ?> <?= esc($e['lesson_title']) ?></a><?php endif ?>
                                        <span><?= date('H:i', strtotime((string) $e['created_at'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <dialog class="modal" id="edit-<?= $e['id'] ?>">
                            <form method="post" action="<?= site_url('journal/' . $e['id']) ?>" data-ajax data-ajax-reload data-ajax-close>
                                <?= csrf_field() ?>
                                <div class="modal-head"><h3>Edit entry</h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
                                <div class="modal-body">
                                    <div class="field"><span class="label">Type</span><div class="segmented">
                                        <?php foreach ($kinds as $k => [$label, $ic]) : ?><label><input type="radio" name="kind" value="<?= $k ?>" <?= $e['kind'] === $k ? 'checked' : '' ?>><span><?= icon($ic) ?><?= esc($label) ?></span></label><?php endforeach ?>
                                    </div></div>
                                    <div class="field"><label class="label">Title</label><input class="input" name="title" maxlength="200" value="<?= esc((string) $e['title'], 'attr') ?>"></div>
                                    <div class="field"><label class="label">Entry</label><textarea class="textarea" name="body" rows="7" required style="font-family:var(--reading-font)"><?= esc($e['body']) ?></textarea></div>
                                    <div class="form-grid">
                                        <div class="field"><label class="label">Scripture</label><input class="input" name="scripture" maxlength="150" value="<?= esc((string) $e['scripture'], 'attr') ?>"></div>
                                        <div class="field"><label class="label">Date</label><input class="input" type="date" name="entry_date" value="<?= esc((string) ($e['entry_date'] ?: substr((string) $e['created_at'], 0, 10)), 'attr') ?>"></div>
                                    </div>
                                </div>
                                <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div>
                            </form>
                        </dialog>
                        <?php if ($e['kind'] === 'prayer') : ?>
                            <dialog class="modal" id="answer-<?= $e['id'] ?>">
                                <form method="post" action="<?= site_url('journal/' . $e['id'] . '/answered') ?>" data-ajax data-ajax-reload data-ajax-close>
                                    <?= csrf_field() ?>
                                    <div class="modal-head"><h3><?= $e['is_answered'] ? 'Still praying?' : 'Prayer answered' ?></h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
                                    <div class="modal-body">
                                        <?php if ($e['is_answered']) : ?>
                                            <p>This will move the prayer back to "Still praying". Your testimony note is kept.</p>
                                        <?php else : ?>
                                            <p class="text-2">"<?= esc(excerpt($e['body'], 140)) ?>"</p>
                                            <div class="field"><label class="label" for="an-<?= $e['id'] ?>">How did God answer? <span class="opt">(optional)</span></label><textarea class="textarea" id="an-<?= $e['id'] ?>" name="answer_note" rows="4" placeholder="Write your testimony…"></textarea></div>
                                        <?php endif ?>
                                    </div>
                                    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('check') ?><?= $e['is_answered'] ? 'Move back' : 'Mark answered' ?></button></div>
                                </form>
                            </dialog>
                        <?php endif ?>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>
    </div>

    <?php if ($pages > 1) : ?>
        <nav class="pagination" aria-label="Pages">
            <?php for ($i = 1; $i <= $pages; $i++) : ?>
                <a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= $q(['page' => $i]) ?>"><?= $i ?></a>
            <?php endfor ?>
        </nav>
    <?php endif ?>
</div>
<?= $this->include('Lms/journal/_new_entry_modal') ?>
<?= $this->endSection() ?>
