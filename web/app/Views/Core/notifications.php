<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $icons = ['announcement' => 'megaphone', 'assignment' => 'file-text', 'graded' => 'award', 'order' => 'package', 'prayer' => 'hands', 'quiz' => 'list-checks']; ?>
<div class="container container-narrow" style="max-width:760px">
    <div class="page-head">
        <h1>Notifications</h1>
        <p>Updates on your courses, orders and prayer requests.</p>
    </div>
    <?php if ($items === []) : ?>
        <div class="card empty"><div class="feature-icon"><?= icon('bell', 'icon-lg') ?></div><h3>You’re all caught up</h3><p>When someone prays for your request, an instructor grades your work or an order ships, it shows up here.</p></div>
    <?php else : ?>
        <div class="card divider-list">
            <?php foreach ($items as $n) : ?>
                <div class="card-body-sm row row-nowrap items-start <?= $n['read_at'] ? '' : 'is-unread' ?>">
                    <span class="feature-icon feature-icon-sm"><?= icon($icons[$n['type']] ?? 'bell') ?></span>
                    <div class="grow"><div class="text-sm"><?= esc($n['body']) ?></div><div class="text-xs text-muted mt-1"><?= time_ago($n['created_at']) ?></div></div>
                    <?php if (! $n['read_at']) : ?><span class="badge badge-brand">New</span><?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
