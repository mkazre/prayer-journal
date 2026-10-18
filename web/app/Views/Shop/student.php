<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $per = static fn (array $p) => $p['type'] === 'subscription' ? ' / ' . esc($p['billing_interval'] ?: 'month') : ' once'; ?>
<div class="page-head-band"><div class="container page-head" style="max-width:820px">
    <span class="eyebrow"><?= icon('graduation') ?>Students</span>
    <h1 class="mt-1">Your Faith Journey, at a Student-Friendly Price</h1>
    <p>Grow in faith, deepen your prayer life and engage with God's Word throughout your studies. Verified students at South African universities, universities of technology, TVET colleges and recognised colleges pay a special membership price.</p>
</div></div>
<div class="container" style="max-width:820px">
    <?php if ($current) : ?>
        <div class="card card-body stack">
            <div class="row gap-sm"><span class="feature-icon" style="margin:0;background:var(--success-soft);color:var(--success)"><?= icon('check-circle', 'icon-lg') ?></span>
                <div><h3 class="mb-0">You're a verified student</h3><p class="text-2 mb-0"><?= $current['institution'] ? esc($current['institution']) . ' · ' : '' ?>valid until <strong><?= date('j F Y', strtotime($current['expires_at'])) ?></strong></p></div></div>
            <?php if ($plans) : ?>
                <div class="stack" style="gap:.6rem">
                    <?php foreach ($plans as $p) : ?>
                        <div class="row between card card-body" style="padding:1rem 1.25rem">
                            <div><strong><?= esc($p['name']) ?></strong><div class="text-sm text-muted">Standard <s><?= money((int) $p['price_cents'], $p['currency']) ?></s></div></div>
                            <div class="row gap-sm"><span class="price"><?= money((int) $p['student_price_cents'], $p['currency']) ?><span class="text-muted text-sm"><?= $per($p) ?></span></span><a class="btn btn-primary" href="<?= site_url('checkout/plan/' . $p['id']) ?>">Choose</a></div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
            <p class="help mb-0">Student status is renewed each academic year. We'll remind you two weeks before it ends.</p>
        </div>
    <?php elseif (! $configured) : ?>
        <div class="card empty"><div class="feature-icon gold"><?= icon('graduation', 'icon-lg') ?></div><h3>Student verification opens soon</h3><p>We're finishing the set-up. Please check back shortly.</p></div>
    <?php else : ?>
        <div class="card card-body stack">
            <?php if ($latest && $latest['status'] === 'pending') : ?>
                <div class="alert alert-info"><?= icon('clock') ?><div><strong>Verification in progress.</strong> If you finished the steps, give it a moment and <a href="<?= site_url('account/student/return') ?>">check again</a>.</div></div>
            <?php elseif ($latest && $latest['status'] === 'rejected') : ?>
                <div class="alert alert-warning"><?= icon('alert') ?><div>We couldn't confirm current student registration last time. You can try again with your institution details.</div></div>
            <?php elseif ($latest && $latest['status'] === 'expired') : ?>
                <div class="alert alert-warning"><?= icon('alert') ?><div>Your student status has expired. Verify again for the new academic year to keep the student price.</div></div>
            <?php endif ?>
            <h3 class="mb-0">Verify your student status</h3>
            <ol class="text-2" style="margin:0;padding-left:1.2rem">
                <li>Choose your institution and confirm your details with our verification partner, SheerID.</li>
                <li>They check that you're <strong>currently registered</strong> — no paperwork for us to review.</li>
                <li>Come back here and choose your membership at the student price.</li>
            </ol>
            <?php if ($plans) : ?>
                <div class="row gap-sm text-sm"><?php foreach ($plans as $p) : ?><span class="badge badge-accent"><?= esc($p['name']) ?>: <?= money((int) $p['student_price_cents'], $p['currency']) ?><?= $per($p) ?> for students</span><?php endforeach ?></div>
            <?php endif ?>
            <form method="post" action="<?= site_url('account/student/verify') ?>"><?= csrf_field() ?><button class="btn btn-primary btn-lg" type="submit"><?= icon('graduation') ?>Verify student status</button></form>
            <p class="help mb-0">Your details are shared only with SheerID for this check, and handled under POPIA. Student status is reviewed each academic year.</p>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
