<?php
/**
 * @var array $course
 * @var array $sections annotated curriculum
 * @var array|null $current
 * @var array|null $enrollment
 * @var bool $preview
 */
$percent = (int) ($enrollment['progress_percent'] ?? 0);
$isCur   = static fn ($it) => $current && $it['kind'] === $current['kind'] && $it['id'] === $current['id'];
?>
<aside class="player-side" id="player-side" data-drawer aria-label="Course contents">
    <div class="player-side-head">
        <div class="row between row-nowrap mb-1">
            <a class="text-xs fw-600 text-muted" href="<?= site_url('my-learning') ?>"><?= icon('arrow-left') ?> My learning</a>
            <button class="btn btn-ghost btn-icon btn-sm" type="button" data-drawer-close aria-label="Close contents" style="margin:-.3rem -.4rem"><?= icon('x') ?></button>
        </div>
        <h2><a href="<?= site_url('study/' . $course['slug']) ?>" style="color:inherit"><?= esc($course['title']) ?></a></h2>
        <?php if ($preview) : ?>
            <span class="badge badge-warning"><?= icon('eye') ?>Instructor preview</span>
        <?php elseif (($settings['show_progress'] ?? true)) : ?>
            <div class="progress progress-sm"><span style="width:<?= $percent ?>%"></span></div>
            <div class="row between text-xs text-muted mt-1"><span><?= $percent ?>% complete</span><a href="<?= site_url('study/' . $course['slug'] . '/notes') ?>"><?= icon('note') ?> My notes</a></div>
        <?php endif ?>
    </div>
    <nav class="curriculum">
        <?php foreach ($sections as $si => $s) :
            $open = $current ? count(array_filter($s['items'], $isCur)) > 0 : $si === 0; ?>
            <details class="cur-section" <?= $open ? 'open' : '' ?>>
                <summary>
                    <div><?= esc($s['title']) ?><small><?= (int) ($s['done_count'] ?? 0) ?>/<?= count($s['items']) ?> complete</small></div>
                    <?= icon('chevron-down', 'chev') ?>
                </summary>
                <?php foreach ($s['items'] as $it) :
                    $classes = 'cur-item' . ($isCur($it) ? ' is-current' : '') . ($it['done'] ? ' is-done' : '') . ($it['locked'] ? ' is-locked' : '');
                    $href    = site_url('study/' . $course['slug'] . '/' . $it['kind'] . '/' . $it['id']); ?>
                    <a class="<?= $classes ?>" href="<?= $it['locked'] && ! $preview ? '#' : $href ?>" <?= $it['locked'] && ! $preview ? 'aria-disabled="true" title="' . esc((string) $it['reason'], 'attr') . '"' : '' ?> <?= $isCur($it) ? 'aria-current="page"' : '' ?>>
                        <span class="state"><?= $it['done'] ? icon('check') : ($it['locked'] && ! $preview ? icon('lock') : '') ?></span>
                        <span class="grow">
                            <?= esc($it['title']) ?>
                            <span class="meta"><?= icon(lesson_icon($it['type'])) ?> <?= lesson_type_label($it['type']) ?><?= $it['duration'] ? ' · ' . $it['duration'] . ' min' : '' ?><?= $it['locked'] && $it['reason'] && ! $preview ? ' · ' . esc($it['reason']) : '' ?></span>
                        </span>
                    </a>
                <?php endforeach ?>
            </details>
        <?php endforeach ?>
    </nav>
</aside>
