<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($title) ?></h1>
    <table style="width:100%;border-collapse:collapse">
        <tr><th>Student</th><th>Quiz Avg</th><th>Assignment Avg</th><th>Overall</th></tr>
        <?php foreach ($rows as $r) : ?>
            <tr style="border-top:1px solid #eee">
                <td><?= esc($r['username']) ?></td>
                <td><?= esc($r['quiz_avg_percent'] ?? '—') ?>%</td>
                <td><?= esc($r['assignment_avg_percent'] ?? '—') ?>%</td>
                <td><strong><?= esc($r['overall_percent'] ?? '—') ?>%</strong></td>
            </tr>
        <?php endforeach ?>
    </table>
</div>
<?= $this->endSection() ?>
