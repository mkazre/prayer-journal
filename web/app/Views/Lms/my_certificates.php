<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container">
    <div class="page-head"><span class="eyebrow"><?= icon('award') ?>Achievements</span><h1 class="mt-1">My certificates</h1><p>Every certificate has a public link you can share as proof of completion.</p></div>
    <?php if ($certs === []) : ?>
        <div class="card empty"><div class="feature-icon gold"><?= icon('award', 'icon-lg') ?></div><h3>No certificates yet</h3><p>Complete a course to earn your first certificate.</p><a class="btn btn-primary" href="<?= site_url('my-learning') ?>">Continue learning</a></div>
    <?php else : ?>
        <div class="grid grid-3">
            <?php foreach ($certs as $c) : $s = \App\Modules\Lms\Models\CourseModel::settings($c); ?>
                <a class="card card-hover card-body" href="<?= site_url('certificates/' . $c['certificate_code']) ?>" style="color:inherit;border-top:4px solid <?= esc($s['accent_color'], 'attr') ?>">
                    <div class="feature-icon gold"><?= icon('award', 'icon-lg') ?></div>
                    <h3><?= esc($c['course_title']) ?></h3>
                    <p class="text-sm text-muted mb-0">Issued <?= nice_date($c['issued_at']) ?> · <?= esc($c['certificate_code']) ?></p>
                </a>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
