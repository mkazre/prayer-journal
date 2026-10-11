<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Prayer wall</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php use App\Modules\Core\Models\PrayerRequestModel; ?>
<div class="studio-head">
    <div><h1>Prayer wall</h1><p>Moderate community prayer requests. Removing a request deletes it for everyone.</p></div>
    <a class="btn" href="<?= site_url('prayer-requests') ?>" target="_blank" rel="noopener"><?= icon('external') ?>View wall</a>
</div>
<?php if ($requests === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('hands', 'icon-lg') ?></div><h3>No requests yet</h3><p>Requests shared by the community appear here.</p></div>
<?php else : ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Request</th><th>From</th><th>Category</th><th class="num">Prayed</th><th>Posted</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($requests as $r) : ?>
            <tr id="pr-<?= $r['id'] ?>">
                <td style="max-width:460px"><?= esc(excerpt($r['body'], 220)) ?><?= ! empty($r['is_answered']) ? ' <span class="badge badge-success">' . icon('check') . 'Answered</span>' : '' ?></td>
                <td class="text-sm"><?= esc($r['username']) ?><?= $r['is_anonymous'] ? '<div class="text-xs text-muted">Shown as anonymous</div>' : '' ?></td>
                <td class="text-sm"><?= esc(PrayerRequestModel::CATEGORIES[$r['category'] ?? 'general'] ?? 'General') ?></td>
                <td class="num"><?= (int) $r['prayer_count'] ?></td>
                <td class="text-sm nowrap"><?= time_ago($r['created_at']) ?></td>
                <td class="text-right"><form method="post" action="<?= site_url('studio/prayer/' . $r['id'] . '/delete') ?>" data-ajax data-ajax-reload><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" data-confirm="Remove this request?" aria-label="Remove"><?= icon('trash') ?></button></form></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table></div>
<?php endif ?>
<?= $this->endSection() ?>
