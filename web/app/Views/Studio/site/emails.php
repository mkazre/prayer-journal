<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/settings#emails') ?>">Settings</a><?= icon('chevron-right') ?><span>Email log</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $tone = ['sent' => 'badge-success', 'queued' => 'badge-warning', 'failed' => 'badge-danger']; ?>
<div class="studio-head">
    <div><h1>Email log</h1><p>Every email the site sends: receipts, reminders, membership billing notices and alerts. Kept for 90 days.</p></div>
    <a class="btn" href="<?= site_url('studio/settings#emails') ?>"><?= icon('settings') ?>Email settings</a>
</div>
<div class="toolbar">
    <div class="chips">
        <?php foreach (['' => 'All', 'sent' => 'Sent', 'queued' => 'Waiting', 'failed' => 'Failed'] as $k => $l) : ?><a class="chip <?= $status === $k ? 'is-active' : '' ?>" href="<?= site_url('studio/emails') . ($k ? '?status=' . $k : '') ?>"><?= $l ?></a><?php endforeach ?>
    </div>
</div>
<?php if ($rows === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('mail', 'icon-lg') ?></div><h3>No emails yet</h3><p>Emails appear here as soon as the site sends them.</p></div>
<?php else : ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>When</th><th>To</th><th>Subject</th><th>Type</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($rows as $r) : ?>
            <tr>
                <td class="text-sm nowrap"><?= nice_date($r['created_at'], 'j M, H:i') ?></td>
                <td class="text-sm"><?= esc($r['to_name'] ?: $r['to_email']) ?><?= $r['to_name'] ? '<div class="text-xs text-muted">' . esc($r['to_email']) . '</div>' : '' ?></td>
                <td class="text-sm" style="max-width:380px"><?= esc($r['subject']) ?><?= $r['last_error'] && $r['status'] !== 'sent' ? '<div class="text-xs text-danger">' . esc($r['last_error']) . '</div>' : '' ?></td>
                <td class="text-xs text-muted"><?= esc(ucfirst(str_replace('_', ' ', $r['template']))) ?></td>
                <td><span class="badge badge-dot <?= $tone[$r['status']] ?? '' ?>"><?= esc(['queued' => 'Waiting'][$r['status']] ?? ucfirst($r['status'])) ?></span></td>
                <td class="text-right nowrap">
                    <a class="btn btn-sm btn-ghost btn-icon" href="<?= site_url('studio/emails/' . $r['id']) ?>" target="_blank" rel="noopener" aria-label="View" title="View email"><?= icon('eye') ?></a>
                    <?php if ($r['status'] !== 'sent') : ?><form method="post" action="<?= site_url('studio/emails/' . $r['id'] . '/retry') ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" aria-label="Send now" title="Send now"><?= icon('send') ?></button></form><?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table></div>
<?php endif ?>
<?= $this->endSection() ?>
