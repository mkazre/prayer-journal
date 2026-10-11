<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><span><?= esc($course['title']) ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Studio/courses/_header', ['course' => $course, 'tab' => $tab]) ?>
<div class="split">
    <div class="stack">
        <?php if ($items === []) : ?><div class="card empty"><div class="feature-icon"><?= icon('megaphone', 'icon-lg') ?></div><h3>No announcements yet</h3><p>Announcements appear on the course home and notify every learner.</p></div><?php endif ?>
        <?php foreach ($items as $a) : ?>
            <div class="card card-body">
                <div class="row between"><strong><?= esc($a['title']) ?></strong><div class="row gap-sm"><span class="text-xs text-muted"><?= time_ago($a['created_at']) ?></span>
                    <form method="post" data-confirm="Delete this announcement?"><?= csrf_field() ?><input type="hidden" name="delete" value="<?= $a['id'] ?>"><button class="btn btn-ghost btn-icon btn-sm" type="submit" aria-label="Delete"><?= icon('trash') ?></button></form></div></div>
                <p class="text-2 mb-0 mt-1" style="white-space:pre-wrap"><?= esc($a['body']) ?></p>
            </div>
        <?php endforeach ?>
    </div>
    <form class="card card-body sticky" method="post">
        <?= csrf_field() ?>
        <h3>New announcement</h3>
        <div class="field"><label class="label">Title</label><input class="input" name="title" required maxlength="200"></div>
        <div class="field"><label class="label">Message</label><textarea class="textarea" name="body" rows="6" required></textarea></div>
        <button class="btn btn-primary btn-block" type="submit"><?= icon('send') ?>Post to all learners</button>
    </form>
</div>
<?= $this->endSection() ?>
