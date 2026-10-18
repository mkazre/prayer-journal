<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head-band"><div class="container page-head text-center" style="max-width:760px">
    <span class="eyebrow"><?= icon('sparkles') ?>Membership</span>
    <h1 class="mt-1">Grow deeper, together</h1>
    <p style="margin:0 auto">Membership unlocks every members-only course, with new studies added over time. Cancel any time.</p>
</div></div>
<div class="container">
    <?php if ($active) : ?><div class="alert alert-success mb-3"><?= icon('check-circle') ?><div>You have an active membership. Enjoy every course!</div></div><?php endif ?>
    <?php if ($plans === []) : ?>
        <div class="card empty"><div class="feature-icon gold"><?= icon('sparkles', 'icon-lg') ?></div><h3>Membership is coming soon</h3><p>In the meantime, many courses are free — including My Prayer Journal.</p><a class="btn btn-primary" href="<?= site_url('courses') ?>">Browse courses</a></div>
    <?php else : ?>
        <div class="grid grid-3" style="max-width:1000px;margin:0 auto">
            <?php foreach ($plans as $i => $plan) : $featured = count($plans) > 1 && $i === 1; ?>
                <div class="card card-body stack" style="<?= $featured ? 'border:2px solid var(--brand-500);box-shadow:var(--shadow)' : '' ?>">
                    <div class="row between"><h3 class="mb-0"><?= esc($plan['name']) ?></h3><?php if ($featured) : ?><span class="badge badge-brand">Most popular</span><?php endif ?></div>
                    <?php $hasStudent = $plan['student_price_cents'] !== null && $plan['student_price_cents'] !== ''; $per = $plan['type'] === 'subscription' ? '/ ' . esc($plan['billing_interval'] ?: 'month') : 'one-time'; ?>
                    <?php if ($hasStudent && $student) : ?>
                        <div><span class="price" style="font-size:2.2rem"><?= money((int) $plan['student_price_cents'], $plan['currency']) ?></span><span class="text-muted"> <?= $per ?></span> <span class="badge badge-success"><?= icon('graduation') ?>Student price</span></div>
                        <div class="text-sm text-muted">Standard price <s><?= money((int) $plan['price_cents'], $plan['currency']) ?></s> — you're a verified student.</div>
                    <?php else : ?>
                        <div><span class="price" style="font-size:2.2rem"><?= money((int) $plan['price_cents'], $plan['currency']) ?></span><span class="text-muted"> <?= $per ?></span></div>
                        <?php if ($hasStudent && $studentEnabled) : ?>
                            <a class="text-sm" href="<?= site_url('account/student') ?>"><?= icon('graduation') ?> Students pay <?= money((int) $plan['student_price_cents'], $plan['currency']) ?> <?= $per ?> — verify your student status</a>
                        <?php endif ?>
                    <?php endif ?>
                    <ul class="checklist text-sm">
                        <li><?= icon('check') ?>Every members-only course</li>
                        <li><?= icon('check') ?>Certificates of completion</li>
                        <li><?= icon('check') ?>Private prayer journal &amp; Bible tools</li>
                        <li><?= icon('check') ?><?= $plan['type'] === 'lifetime' ? 'Pay once, keep access forever' : 'Cancel any time' ?></li>
                    </ul>
                    <a class="btn <?= $featured ? 'btn-primary' : '' ?> btn-lg btn-block" href="<?= site_url('checkout/plan/' . $plan['id']) ?>">Choose <?= esc($plan['name']) ?></a>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
    <?php if ($courses) : ?>
        <section class="section-sm"><h2 style="font-size:1.4rem">Included with membership</h2><div class="grid grid-3"><?php foreach ($courses as $c) : ?><?= view('partials/course_card', ['course' => $c]) ?><?php endforeach ?></div></section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
