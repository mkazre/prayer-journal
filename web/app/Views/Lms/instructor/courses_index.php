<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>My Courses</h1>
    <a class="pj-btn pj-btn-gold" href="<?= site_url('instructor/courses/create') ?>">+ New Course</a>
    <table style="width:100%;margin-top:1rem;border-collapse:collapse">
        <?php foreach ($courses as $c) : ?>
            <tr style="border-bottom:1px solid #eee">
                <td style="padding:.5rem 0"><?= esc($c['title']) ?></td>
                <td><?= esc($c['status']) ?></td>
                <td><a href="<?= site_url('instructor/courses/' . $c['id'] . '/edit') ?>">Edit</a></td>
                <td><a href="<?= site_url('instructor/courses/' . $c['id'] . '/gradebook') ?>">Gradebook</a></td>
            </tr>
        <?php endforeach ?>
    </table>
</div>
<?= $this->endSection() ?>
