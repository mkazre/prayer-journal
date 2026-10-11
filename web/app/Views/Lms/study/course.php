<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($course['title']) ?></h1>
    <p>Progress: <?= esc($enrollment['progress_percent']) ?>%</p>

    <h2>Lessons</h2>
    <ul>
        <?php foreach ($lessons as $l) : ?>
            <li>
                <a href="<?= site_url('study/' . $course['slug'] . '/lesson/' . $l['id']) ?>"><?= esc($l['title']) ?></a>
                <?= in_array($l['id'], $completed) ? '✓' : '' ?>
            </li>
        <?php endforeach ?>
    </ul>

    <h2>Quizzes</h2>
    <ul>
        <?php foreach ($quizzes as $q) : ?>
            <li><a href="<?= site_url('study/' . $course['slug'] . '/quiz/' . $q['id']) ?>"><?= esc($q['title']) ?></a></li>
        <?php endforeach ?>
    </ul>

    <h2>Assignments</h2>
    <ul>
        <?php foreach ($assignments as $a) : ?>
            <li><a href="<?= site_url('study/' . $course['slug'] . '/assignment/' . $a['id']) ?>"><?= esc($a['title']) ?></a></li>
        <?php endforeach ?>
    </ul>
</div>
<?= $this->endSection() ?>
