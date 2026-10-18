<?= $this->extend('layouts/bare') ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Lms\Services\Curriculum;

$percent  = (int) ($enrollment['progress_percent'] ?? 0);
$complete = ($enrollment['status'] ?? '') === 'completed';
?>
<div class="player">
    <?= view('Lms/study/_sidebar', compact('course', 'sections', 'current', 'enrollment', 'preview', 'settings')) ?>
    <div class="player-main">
        <?= view('Lms/study/_top', ['course' => $course, 'current' => null]) ?>
        <div class="player-content wide">
            <section class="welcome-band" style="background:radial-gradient(600px 300px at 100% 0%, color-mix(in srgb, <?= esc($settings['accent_color'], 'attr') ?> 40%, transparent), transparent 60%), linear-gradient(135deg, #0f1321, <?= esc($settings['brand_color'], 'attr') ?>)">
                <div class="row between items-start gap-xl">
                    <div class="grow" style="max-width:620px">
                        <span class="eyebrow" style="color:var(--accent)"><?= icon('graduation') ?><?= $preview ? 'Instructor preview' : ($complete ? 'Completed' : 'Your course') ?></span>
                        <h1 class="mt-1" style="font-size:clamp(1.7rem,2.6vw,2.4rem)"><?= esc($course['title']) ?></h1>
                        <?php if ($complete && $settings['completion_message']) : ?>
                            <p><?= esc($settings['completion_message']) ?></p>
                        <?php elseif ($settings['welcome_message']) : ?>
                            <p><?= esc($settings['welcome_message']) ?></p>
                        <?php endif ?>
                        <div class="row mt-2">
                            <?php if ($next) : ?>
                                <a class="btn btn-accent btn-lg" href="<?= site_url('study/' . $course['slug'] . '/' . $next['kind'] . '/' . $next['id']) ?>"><?= icon('play') ?><?= $percent > 0 ? 'Continue: ' . esc(mb_strimwidth($next['title'], 0, 38, '…')) : 'Start the course' ?></a>
                            <?php endif ?>
                            <?php if ($certificate) : ?>
                                <a class="btn btn-light btn-lg" href="<?= site_url('certificates/' . $certificate['certificate_code']) ?>"><?= icon('award') ?>View certificate</a>
                            <?php endif ?>
                        </div>
                    </div>
                    <?php if (! $preview && $settings['show_progress']) : ?>
                        <div class="text-center">
                            <div class="ring" style="--p:<?= $percent ?>;--size:120px;background:conic-gradient(var(--accent) calc(var(--p) * 1%), rgba(255,255,255,.15) 0)">
                                <span style="font-size:1.6rem;color:var(--text)"><?= $percent ?>%</span>
                            </div>
                            <div class="text-sm mt-1" style="color:rgba(255,255,255,.75)">complete</div>
                        </div>
                    <?php endif ?>
                </div>
            </section>

            <div class="grid grid-4 mt-3">
                <div class="card stat"><div class="stat-label"><?= icon('layers') ?>Lessons</div><div class="stat-value"><?= Curriculum::countItems($sections, 'lesson') ?></div></div>
                <div class="card stat"><div class="stat-label"><?= icon('clock') ?>Total time</div><div class="stat-value" style="font-size:1.35rem"><?= Curriculum::duration(Curriculum::totalMinutes($sections)) ?></div></div>
                <a class="card card-hover stat" href="<?= site_url('journal?course=' . $course['id']) ?>"><div class="stat-label"><?= icon('notebook') ?>Journal entries</div><div class="stat-value"><?= $journalCount ?></div></a>
                <a class="card card-hover stat" href="<?= site_url('study/' . $course['slug'] . '/notes') ?>"><div class="stat-label"><?= icon('note') ?>Notes</div><div class="stat-value"><?= $noteCount ?></div></a>
            </div>

            <?php if ($announcements) : ?>
                <section class="card mt-3">
                    <div class="card-header"><h2><?= icon('megaphone') ?> Announcements</h2></div>
                    <div class="divider-list">
                        <?php foreach ($announcements as $a) : ?>
                            <div class="card-body"><div class="row between"><strong><?= esc($a['title']) ?></strong><span class="text-xs text-muted"><?= time_ago($a['created_at']) ?></span></div><p class="text-2 mb-0 mt-1" style="white-space:pre-wrap"><?= esc($a['body']) ?></p></div>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endif ?>

            <section class="mt-3">
                <h2 style="font-size:1.35rem">Course outline</h2>
                <div class="stack">
                    <?php foreach ($sections as $i => $s) : $pct = count($s['items']) ? (int) round(($s['done_count'] ?? 0) / count($s['items']) * 100) : 0; ?>
                        <div class="card">
                            <div class="card-header">
                                <div class="row row-nowrap"><span class="qnum"><?= $i + 1 ?></span><div><h3 class="mb-0" style="font-size:1rem;font-family:var(--font-body)"><?= esc($s['title']) ?></h3><?php if ($s['summary']) : ?><div class="text-sm text-muted"><?= esc($s['summary']) ?></div><?php endif ?></div></div>
                                <div class="ring" style="--p:<?= $pct ?>;--size:44px"><span style="font-size:.66rem"><?= $pct ?>%</span></div>
                            </div>
                            <div class="card-body-sm">
                                <div class="chips">
                                    <?php foreach (array_slice($s['items'], 0, 8) as $it) : ?>
                                        <a class="chip" href="<?= site_url('study/' . $course['slug'] . '/' . $it['kind'] . '/' . $it['id']) ?>" style="<?= $it['done'] ? 'border-color:var(--success);color:var(--success)' : '' ?>"><?= icon($it['done'] ? 'check' : ($it['locked'] && ! $preview ? 'lock' : lesson_icon($it['type']))) ?><?= esc(mb_strimwidth($it['title'], 0, 34, '…')) ?></a>
                                    <?php endforeach ?>
                                    <?php if (count($s['items']) > 8) : ?><span class="chip" style="cursor:default">+<?= count($s['items']) - 8 ?> more</span><?php endif ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
