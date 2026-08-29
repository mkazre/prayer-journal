<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>My Certificates</h1>
<?php foreach ($certs as $c) : ?>
    <div class="pj-card" style="margin-bottom:.5rem">
        <a href="<?= site_url('certificates/' . $c['certificate_code']) ?>"><?= esc($c['course_title']) ?></a>
    </div>
<?php endforeach ?>
<?php if (empty($certs)) : ?><p>Complete a course to earn your first certificate.</p><?php endif ?>
<?= $this->endSection() ?>
