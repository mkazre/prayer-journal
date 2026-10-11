<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$hour     = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$kinds    = \App\Modules\Lms\Models\JournalEntryModel::KINDS;
?>
<div class="container mt-3">
    <section class="welcome-band">
        <div class="row between items-start gap-lg">
            <div>
                <span class="eyebrow" style="color:var(--accent)"><?= icon('calendar') ?><?= date('l, j F Y') ?></span>
                <h1 class="mt-1 mb-1" style="font-size:clamp(1.8rem,3vw,2.5rem)"><?= $greeting ?>, <?= esc(display_name()) ?>.</h1>
                <p class="mb-0">
                    <?php if ($next) : ?>Your next step: <strong style="color:#fff"><?= esc($next['item']['title']) ?></strong> in <?= esc($next['course']['title']) ?>.
                    <?php else : ?>Pick up a course, open your journal, or spend a few minutes in the Word today.<?php endif ?>
                </p>
            </div>
            <?php if ($streak > 0) : ?>
                <div class="hero-card text-center" style="padding:1rem 1.4rem">
                    <div class="row gap-xs center" style="color:var(--accent)"><?= icon('flame', 'icon-lg') ?><strong style="font-size:1.8rem;color:#fff"><?= $streak ?></strong></div>
                    <div class="text-xs" style="color:rgba(255,255,255,.75)">day reading streak</div>
                </div>
            <?php endif ?>
        </div>
        <div class="row mt-3">
            <?php if ($next) : ?>
                <a class="btn btn-accent" href="<?= site_url('study/' . $next['course']['slug'] . '/' . $next['item']['kind'] . '/' . $next['item']['id']) ?>"><?= icon('play') ?>Continue</a>
            <?php else : ?>
                <a class="btn btn-accent" href="<?= site_url('courses') ?>"><?= icon('graduation') ?>Find a course</a>
            <?php endif ?>
            <button class="btn btn-light" type="button" data-modal-open="new-entry"><?= icon('feather') ?>New journal entry</button>
            <a class="btn btn-light" href="<?= site_url('bible') ?>"><?= icon('book-open') ?>Read the Bible</a>
        </div>
    </section>

    <div class="grid grid-4 mt-3">
        <?php foreach ([
            ['graduation', 'Courses', count($enrollments), 'my-learning'],
            ['check-circle', 'Lessons completed', $lessonsDone, 'my-learning'],
            ['hands', 'Prayers answered', ($journalStats['answered'] ?? 0) . ' / ' . ($journalStats['prayers'] ?? 0), 'journal?status=answered'],
            ['award', 'Certificates', $certificates, 'my-certificates'],
        ] as [$ic, $label, $value, $href]) : ?>
            <a class="card card-hover stat" href="<?= site_url($href) ?>">
                <div class="stat-label"><?= icon($ic) ?><?= esc($label) ?></div>
                <div class="stat-value"><?= esc((string) $value) ?></div>
            </a>
        <?php endforeach ?>
    </div>

    <div class="split mt-3">
        <div class="stack stack-lg">
            <section>
                <div class="section-head mb-2"><h2 class="mb-0" style="font-size:1.35rem">Continue learning</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('my-learning') ?>">All courses <?= icon('arrow-right') ?></a></div>
                <?php if ($enrollments === []) : ?>
                    <div class="card empty">
                        <div class="feature-icon"><?= icon('graduation', 'icon-lg') ?></div>
                        <h3>No courses yet</h3>
                        <p>Start with "My Prayer Journal" — six prayer secrets and a devotion for every day.</p>
                        <a class="btn btn-primary" href="<?= site_url('courses') ?>">Browse courses</a>
                    </div>
                <?php else : ?>
                    <div class="stack stack-sm">
                        <?php foreach (array_slice($enrollments, 0, 3) as $e) : ?>
                            <a class="card card-hover continue-card" href="<?= site_url('study/' . $e['slug']) ?>">
                                <div class="course-cover" style="<?= course_cover_style($e) ?>">
                                    <?php if ($e['thumbnail_path']) : ?><img src="<?= base_url($e['thumbnail_path']) ?>" alt=""><?php else : ?><div class="cover-art"><span><?= esc($e['title']) ?></span></div><?php endif ?>
                                </div>
                                <div>
                                    <h3 class="mb-0" style="font-size:1.05rem;font-family:var(--font-body)"><?= esc($e['title']) ?></h3>
                                    <p class="text-sm text-muted mb-1"><?= $e['last_lesson_title'] ? 'Last opened: ' . esc($e['last_lesson_title']) : 'Not started yet' ?></p>
                                    <div class="progress"><span style="width:<?= (int) $e['progress_percent'] ?>%"></span></div>
                                    <div class="row between text-xs text-muted mt-1"><span><?= (int) $e['progress_percent'] ?>% complete</span>
                                        <?php if ($e['status'] === 'completed') : ?><span class="badge badge-success"><?= icon('award') ?>Completed</span><?php else : ?><span class="text-brand fw-600">Resume <?= icon('arrow-right') ?></span><?php endif ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </section>

            <section class="card">
                <div class="card-header"><h2><?= icon('activity') ?> This week</h2><span class="text-xs text-muted">Bible reading · lessons · journal</span></div>
                <div class="card-body">
                    <div class="grid" style="grid-template-columns:repeat(7,1fr);gap:.5rem">
                        <?php foreach ($week as $d) : $active = $d['read'] || $d['lessons'] || $d['journal']; ?>
                            <div class="text-center">
                                <div class="text-xs text-muted fw-600"><?= date('D', strtotime($d['date'])) ?></div>
                                <div style="margin:.4rem auto;width:2.5rem;height:2.5rem;border-radius:12px;display:grid;place-items:center;background:<?= $active ? 'var(--brand-soft)' : 'var(--surface-2)' ?>;color:<?= $active ? 'var(--brand-500)' : 'var(--muted)' ?>;<?= $d['date'] === date('Y-m-d') ? 'box-shadow:var(--ring)' : '' ?>">
                                    <?= $active ? icon('check') : '<span class="text-xs">' . date('j', strtotime($d['date'])) . '</span>' ?>
                                </div>
                                <div class="row center gap-xs" style="min-height:1rem">
                                    <?php if ($d['read']) : ?><span title="Read the Bible" style="width:6px;height:6px;border-radius:50%;background:var(--brand-500)"></span><?php endif ?>
                                    <?php if ($d['lessons']) : ?><span title="<?= $d['lessons'] ?> lessons" style="width:6px;height:6px;border-radius:50%;background:var(--accent)"></span><?php endif ?>
                                    <?php if ($d['journal']) : ?><span title="<?= $d['journal'] ?> journal entries" style="width:6px;height:6px;border-radius:50%;background:var(--success)"></span><?php endif ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2><?= icon('notebook') ?> Recent journal entries</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('journal') ?>">Open journal <?= icon('arrow-right') ?></a></div>
                <?php if ($recent === []) : ?>
                    <div class="card-body text-muted">Your entries will appear here. Each devotion has prompts for prayer points, meditation and gratitude.</div>
                <?php else : ?>
                    <?php foreach ($recent as $j) : [$kLabel, $kIcon, $kClass] = $kinds[$j['kind']] ?? $kinds['note']; ?>
                        <div class="journal-entry row items-start row-nowrap">
                            <span class="kind-icon <?= $kClass ?>"><?= icon($kIcon) ?></span>
                            <div class="grow">
                                <div class="row between"><strong class="text-sm"><?= esc($j['title'] ?: $kLabel) ?></strong><span class="text-xs text-muted"><?= time_ago($j['updated_at'] ?? $j['created_at']) ?></span></div>
                                <div class="text-2 clamp-2" style="font-family:var(--reading-font)"><?= esc($j['body']) ?></div>
                                <?php if ($j['lesson_title']) : ?><div class="text-xs text-muted mt-1"><?= icon('feather') ?> <?= esc($j['lesson_title']) ?></div><?php endif ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </section>
        </div>

        <aside class="stack stack-lg">
            <?php if ($verse) : ?>
                <div class="verse-card" style="padding:1.6rem">
                    <span class="eyebrow"><?= icon('sunrise') ?>Verse of the day</span>
                    <p class="verse-text" style="font-size:1.15rem">&ldquo;<?= esc($verse['text']) ?>&rdquo;</p>
                    <a class="verse-ref" href="<?= site_url('bible/' . \App\Libraries\Scripture::slug((int) $verse['book_position']) . '/' . $verse['chapter'] . '#v' . $verse['verse']) ?>"><?= esc($verse['book_name']) ?> <?= (int) $verse['chapter'] ?>:<?= (int) $verse['verse'] ?></a>
                </div>
            <?php endif ?>

            <section class="card">
                <div class="card-header"><h2><?= icon('hands') ?> Standing in prayer</h2><span class="badge"><?= count($openPrayers) ?></span></div>
                <?php if ($openPrayers === []) : ?>
                    <div class="card-body text-sm text-muted">No open prayer points. Add one from a devotion or your journal.</div>
                <?php else : ?>
                    <div class="divider-list">
                        <?php foreach ($openPrayers as $p) : ?>
                            <div class="card-body-sm row items-start row-nowrap">
                                <div class="grow text-sm clamp-2"><?= esc($p['body']) ?></div>
                                <form method="post" action="<?= site_url('journal/' . $p['id'] . '/answered') ?>" data-ajax data-ajax-reload>
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-soft" type="submit" title="Mark answered"><?= icon('check') ?>Answered</button>
                                </form>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </section>

            <section class="card">
                <div class="card-header"><h2><?= icon('calendar-check') ?> Reading plans</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('bible/plans') ?>">Browse</a></div>
                <?php if ($plans === []) : ?>
                    <div class="card-body text-sm text-muted">Start a plan — the Gospels in 30 days or Proverbs in a month.</div>
                <?php else : ?>
                    <div class="divider-list">
                        <?php foreach ($plans as $p) : ?>
                            <div class="card-body-sm">
                                <div class="row between"><a class="fw-600 text-sm" href="<?= site_url('bible/plans/' . $p['plan']['slug']) ?>"><?= esc($p['plan']['title']) ?></a><span class="text-xs text-muted">Day <?= min($p['next_day'], (int) $p['plan']['days']) ?> / <?= (int) $p['plan']['days'] ?></span></div>
                                <div class="progress progress-sm mt-1"><span style="width:<?= $p['percent'] ?>%"></span></div>
                                <?php if ($p['today']) : ?>
                                    <div class="chips mt-1">
                                        <?php foreach (array_slice(\App\Modules\Bible\Models\ReadingPlanModel::links($p['today']['readings']), 0, 4) as $l) : ?>
                                            <a class="chip" style="padding:.25rem .6rem;font-size:.78rem" href="<?= $l['url'] ?>"><?= esc($l['label']) ?></a>
                                        <?php endforeach ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </section>

            <?php if ($orders !== []) : ?>
                <section class="card">
                    <div class="card-header"><h2><?= icon('receipt') ?> Recent orders</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('my-orders') ?>">All</a></div>
                    <div class="divider-list">
                        <?php foreach ($orders as $o) : [$sl, $tone] = \App\Modules\Shop\Models\OrderModel::STATUSES[$o['status']] ?? [$o['status'], 'brand']; ?>
                            <a class="card-body-sm row between" href="<?= site_url('orders/' . $o['id']) ?>" style="color:inherit">
                                <span class="text-sm fw-600"><?= esc($o['order_number']) ?></span>
                                <span class="badge badge-<?= $tone ?>"><?= esc($sl) ?></span>
                            </a>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endif ?>

            <?php if (is_staff()) : ?>
                <a class="card card-hover card-body row row-nowrap" href="<?= site_url('studio') ?>" style="color:inherit">
                    <span class="feature-icon" style="margin:0"><?= icon('layout', 'icon-lg') ?></span>
                    <div><strong>Open Studio</strong><div class="text-sm text-muted">Manage courses, products, orders and site settings.</div></div>
                </a>
            <?php endif ?>
        </aside>
    </div>
</div>
<?= $this->include('Lms/journal/_new_entry_modal') ?>
<?= $this->endSection() ?>
