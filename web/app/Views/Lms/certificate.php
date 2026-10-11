<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container section-sm">
    <div class="row between mb-2 no-print">
        <div><span class="badge badge-success"><?= icon('shield-check') ?>Verified certificate</span><span class="text-sm text-muted" style="margin-left:.5rem">Code <?= esc($cert['certificate_code']) ?></span></div>
        <div class="row gap-sm">
            <button class="btn" type="button" data-copy="<?= current_url() ?>"><?= icon('link') ?>Copy link</button>
            <button class="btn" type="button" data-share="I completed <?= esc($cert['course_title'], 'attr') ?>!" data-share-title="My certificate"><?= icon('share') ?>Share</button>
            <button class="btn btn-primary" type="button" data-print><?= icon('printer') ?>Print / Save PDF</button>
        </div>
    </div>
    <div class="certificate" style="--cert-accent:<?= esc($settings['accent_color'], 'attr') ?>;--cert-brand:<?= esc($settings['brand_color'], 'attr') ?>">
        <div class="cert-inner">
            <img src="<?= logo_url() ?>" alt="<?= esc(site_name()) ?> logo" width="72" height="72" style="border-radius:16px">
            <div class="cert-kicker"><?= esc(site_name()) ?></div>
            <h1 class="cert-title"><?= esc($settings['certificate_title']) ?></h1>
            <div style="font-size:1rem;color:#5b5468">This is to certify that</div>
            <div class="cert-name"><?= esc($name) ?></div>
            <div style="font-size:1rem;color:#5b5468;max-width:70%"><?= esc($settings['certificate_message']) ?></div>
            <div class="cert-course"><?= esc($cert['course_title']) ?></div>
            <div class="cert-foot">
                <div class="sig"><strong><?= nice_date($cert['issued_at'], 'j F Y') ?></strong>Date of completion</div>
                <div class="seal"><?= icon('award', 'icon-xl') ?></div>
                <div class="sig"><strong style="font-family:var(--font-display);font-style:italic;font-size:1.15rem"><?= esc($settings['certificate_signatory'] ?: ($cert['author_name'] ?: site_name())) ?></strong><?= esc($settings['certificate_role']) ?></div>
            </div>
            <div style="font-family:var(--font-body);font-size:.7rem;color:#8a8496;margin-top:1rem">Verify at <?= esc(site_url('certificates/' . $cert['certificate_code'])) ?></div>
        </div>
    </div>
    <?php if ($isOwner) : ?><p class="text-center mt-3 no-print"><a href="<?= site_url('study/' . $cert['slug']) ?>"><?= icon('arrow-left') ?> Back to the course</a></p><?php endif ?>
</div>
<?= $this->endSection() ?>
