<?= $this->extend('layouts/bare') ?>
<?= $this->section('content') ?>
<?php $passed = (bool) $result['passed']; $show = (int) $quiz['show_answers'] === 1; ?>
<div class="player">
    <?= view('Lms/study/_sidebar', compact('course', 'sections', 'current', 'enrollment', 'preview', 'settings')) ?>
    <div class="player-main">
        <?= view('Lms/study/_top', ['course' => $course, 'current' => $current]) ?>
        <div class="player-content">
            <div class="card result-hero">
                <div class="feature-icon <?= $passed ? '' : 'gold' ?>" style="margin:0 auto 1rem;width:4rem;height:4rem;<?= $passed ? 'background:var(--success-soft);color:var(--success)' : '' ?>"><?= icon($passed ? 'trophy' : 'refresh', 'icon-xl') ?></div>
                <div class="result-score" style="color:<?= $passed ? 'var(--success)' : 'var(--warning)' ?>"><?= (int) $result['score_percent'] ?>%</div>
                <h2 class="mt-1"><?= $passed ? 'Well done — you passed!' : 'Not quite yet' ?></h2>
                <p class="text-2"><?= (int) $result['earned'] ?> of <?= (int) $result['total'] ?> points · pass mark <?= (int) $quiz['pass_percent'] ?>%<?= $attemptsLeft !== null ? ' · ' . $attemptsLeft . ' attempt' . ($attemptsLeft === 1 ? '' : 's') . ' left' : '' ?></p>
                <div class="row center">
                    <?php if (! $passed && ($attemptsLeft === null || $attemptsLeft > 0)) : ?><a class="btn btn-primary" href="<?= site_url('study/' . $course['slug'] . '/quiz/' . $quiz['id']) ?>?start=1&restart=1"><?= icon('repeat') ?>Try again</a><?php endif ?>
                    <?php if ($next) : ?><a class="btn <?= $passed ? 'btn-primary' : '' ?>" href="<?= site_url('study/' . $course['slug'] . '/' . $next['kind'] . '/' . $next['id']) ?>">Continue <?= icon('arrow-right') ?></a><?php endif ?>
                </div>
            </div>
            <?php if ($show) : ?>
                <h2 class="mt-4" style="font-size:1.35rem">Review your answers</h2>
                <div class="card">
                    <?php foreach ($quiz['questions'] as $n => $q) : $r = $result['review'][$q['id']] ?? null; $type = $q['type'] ?: 'single'; ?>
                        <div class="question">
                            <div class="row items-start row-nowrap gap-lg">
                                <span class="qnum" style="<?= $r && $r['correct'] ? 'background:var(--success-soft);color:var(--success)' : 'background:var(--danger-soft);color:var(--danger)' ?>"><?= icon($r && $r['correct'] ? 'check' : 'x') ?></span>
                                <div class="grow">
                                    <h3 class="mb-1"><?= ($n + 1) . '. ' . esc($q['question']) ?></h3>
                                    <?php if ($type === 'short_text') : ?>
                                        <div class="option <?= $r && $r['correct'] ? 'is-correct' : 'is-wrong' ?>">Your answer: <strong><?= esc((string) ($r['given'] ?? '—')) ?></strong></div>
                                        <?php if (! ($r['correct'] ?? false)) : ?><div class="option is-correct">Accepted: <strong><?= esc(str_replace('|', ' / ', (string) $q['correct_text'])) ?></strong></div><?php endif ?>
                                    <?php else : ?>
                                        <?php foreach ($q['options'] as $o) :
                                            $picked  = in_array((int) $o['id'], array_map('intval', (array) ($r['given'] ?? [])), true);
                                            $correct = (int) $o['is_correct'] === 1; ?>
                                            <div class="option <?= $correct ? 'is-correct' : ($picked ? 'is-wrong' : '') ?>">
                                                <?= icon($correct ? 'check-circle' : ($picked ? 'x' : 'circle')) ?><span class="grow"><?= esc($o['option_text']) ?></span><?php if ($picked) : ?><span class="badge">Your answer</span><?php endif ?>
                                            </div>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                    <?php if (! empty($q['explanation'])) : ?><div class="alert mt-1"><?= icon('info') ?><div><?= scripture_links(esc($q['explanation'])) ?></div></div><?php endif ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
