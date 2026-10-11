<?= $this->extend('layouts/bare') ?>
<?= $this->section('content') ?>
<?php $maxAttempts = (int) $quiz['max_attempts']; ?>
<div class="player">
    <?= view('Lms/study/_sidebar', compact('course', 'sections', 'current', 'enrollment', 'preview', 'settings')) ?>
    <div class="player-main">
        <?= view('Lms/study/_top', ['course' => $course, 'current' => $current, 'topActions' => $secondsLeft !== null && ! empty($start) ? '<span class="quiz-timer" data-timer-seconds="' . $secondsLeft . '" data-form="quiz-form">' . icon('clock') . '<span></span></span>' : '']) ?>
        <div class="player-content">
            <header class="lesson-head mb-3">
                <span class="eyebrow"><?= icon('list-checks') ?>Quiz · <?= count($quiz['questions']) ?> questions · pass mark <?= (int) $quiz['pass_percent'] ?>%</span>
                <h1 class="mt-1"><?= esc($quiz['title']) ?></h1>
                <?php if ($quiz['instructions']) : ?><p class="text-2"><?= esc($quiz['instructions']) ?></p><?php endif ?>
            </header>

            <?php if (empty($start)) : ?>
                <div class="card card-body">
                    <div class="grid grid-3">
                        <div><div class="stat-label"><?= icon('trophy') ?>Best score</div><div class="stat-value"><?= $best ? (int) $best['score_percent'] . '%' : '—' ?></div></div>
                        <div><div class="stat-label"><?= icon('repeat') ?>Attempts</div><div class="stat-value"><?= $used ?><?= $maxAttempts ? ' / ' . $maxAttempts : '' ?></div></div>
                        <div><div class="stat-label"><?= icon('clock') ?>Time limit</div><div class="stat-value" style="font-size:1.3rem"><?= $quiz['time_limit_mins'] ? (int) $quiz['time_limit_mins'] . ' min' : 'None' ?></div></div>
                    </div>
                    <?php if ($best && $best['passed']) : ?><div class="alert alert-success mt-2"><?= icon('check-circle') ?><div>You've passed this quiz. You can retake it to improve your score.</div></div><?php endif ?>
                    <div class="row mt-3"><a class="btn btn-primary btn-lg" href="?start=1&restart=1"><?= icon('play') ?><?= $used ? 'Retake quiz' : 'Start quiz' ?></a><?php if ($next) : ?><a class="btn btn-ghost" href="<?= site_url('study/' . $course['slug'] . '/' . $next['kind'] . '/' . $next['id']) ?>">Skip for now <?= icon('arrow-right') ?></a><?php endif ?></div>
                </div>
                <?php if ($history) : ?>
                    <div class="table-wrap mt-3"><table class="table">
                        <thead><tr><th>Attempt</th><th>Date</th><th class="num">Score</th><th>Result</th></tr></thead>
                        <tbody><?php foreach ($history as $i => $h) : ?><tr><td>#<?= count($history) - $i ?></td><td><?= nice_date($h['submitted_at'], 'j M Y, H:i') ?></td><td class="num"><?= (int) $h['score_percent'] ?>%</td><td><?= $h['passed'] ? '<span class="badge badge-success">Passed</span>' : '<span class="badge badge-danger">Not yet</span>' ?></td></tr><?php endforeach ?></tbody>
                    </table></div>
                <?php endif ?>
            <?php else : ?>
                <form method="post" id="quiz-form" class="card" onsubmit="return confirm('Submit your answers?')">
                    <?= csrf_field() ?>
                    <?php foreach ($quiz['questions'] as $n => $q) : $type = $q['type'] ?: 'single'; ?>
                        <fieldset class="question" style="border:0;margin:0">
                            <div class="row items-start row-nowrap gap-lg">
                                <span class="qnum"><?= $n + 1 ?></span>
                                <div class="grow">
                                    <legend><h3 class="mb-1"><?= esc($q['question']) ?></h3></legend>
                                    <div class="text-xs text-muted"><?= $type === 'multiple' ? 'Select all that apply' : ($type === 'short_text' ? 'Type your answer' : 'Choose one') ?> · <?= (int) $q['points'] ?> point<?= (int) $q['points'] === 1 ? '' : 's' ?></div>
                                    <?php if ($type === 'short_text') : ?>
                                        <input class="input mt-1" name="answer[<?= $q['id'] ?>]" autocomplete="off" placeholder="Your answer">
                                    <?php else : ?>
                                        <?php foreach ($q['options'] as $o) : ?>
                                            <label class="option">
                                                <input type="<?= $type === 'multiple' ? 'checkbox' : 'radio' ?>" name="answer[<?= $q['id'] ?>]<?= $type === 'multiple' ? '[]' : '' ?>" value="<?= $o['id'] ?>">
                                                <span><?= esc($o['option_text']) ?></span>
                                            </label>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                </div>
                            </div>
                        </fieldset>
                    <?php endforeach ?>
                    <div class="card-footer row between"><span class="text-sm text-muted">Pass mark <?= (int) $quiz['pass_percent'] ?>%</span><button class="btn btn-primary btn-lg" type="submit"><?= icon('send') ?>Submit answers</button></div>
                </form>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
