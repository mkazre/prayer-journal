<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Users &amp; roles</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$roleTone = ['superadmin' => 'badge-danger', 'admin' => 'badge-brand', 'instructor' => 'badge-accent', 'vendor' => 'badge-info', 'student' => ''];
$link     = static fn (?string $g) => site_url('studio/users') . '?' . http_build_query(array_filter(['group' => $g, 'q' => $q]));
?>
<div class="studio-head">
    <div><h1>Users &amp; roles</h1><p>Roles control what each person can do. Instructors build courses, vendors manage products, admins manage everything.</p></div>
    <form method="get" class="input-icon"><?php if ($group) : ?><input type="hidden" name="group" value="<?= esc($group, 'attr') ?>"><?php endif ?><?= icon('search') ?><input class="input" type="search" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Name or email" style="width:260px"></form>
</div>
<div class="toolbar">
    <div class="chips">
        <a class="chip <?= $group === '' ? 'is-active' : '' ?>" href="<?= $link(null) ?>">Everyone</a>
        <?php foreach ($groups as $k => $g) : ?><a class="chip <?= $group === $k ? 'is-active' : '' ?>" href="<?= $link($k) ?>"><?= esc($g['title']) ?></a><?php endforeach ?>
    </div>
    <span class="text-sm text-muted"><?= count($users) ?> shown</span>
</div>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Person</th><th>Roles</th><th class="num">Courses</th><th>Joined</th><th>Last active</th><th></th></tr></thead>
    <tbody>
        <?php if ($users === []) : ?><tr><td colspan="6" class="text-muted">No one matches.</td></tr><?php endif ?>
        <?php foreach ($users as $u) : $mine = array_filter(explode(',', (string) $u['groups_list'])); $name = display_name(['id' => $u['id'], 'username' => $u['username']]); ?>
            <tr>
                <td><div class="row row-nowrap"><?= avatar((int) $u['id'], $name, 'avatar-sm') ?><div><div class="fw-600"><?= esc($name) ?></div><div class="text-xs text-muted"><?= esc((string) $u['email']) ?></div></div></div></td>
                <td><?php foreach ($mine as $g) : ?><span class="badge <?= $roleTone[$g] ?? '' ?>"><?= esc($groups[$g]['title'] ?? $g) ?></span> <?php endforeach ?></td>
                <td class="num"><?= (int) $u['courses'] ?></td>
                <td class="text-sm nowrap"><?= nice_date($u['created_at'], 'j M Y') ?></td>
                <td class="text-sm nowrap text-muted"><?= $u['last_active'] ? time_ago($u['last_active']) : 'Never' ?></td>
                <td class="text-right">
                    <button class="btn btn-sm" type="button" data-modal-open="roles-<?= $u['id'] ?>"><?= icon('shield') ?>Roles</button>
                    <dialog class="modal" id="roles-<?= $u['id'] ?>">
                        <form method="post" action="<?= site_url('studio/users/' . $u['id'] . '/groups') ?>" data-ajax data-ajax-reload data-ajax-close>
                            <?= csrf_field() ?>
                            <div class="modal-head"><h3>Roles for <?= esc($name) ?></h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
                            <div class="modal-body stack text-left">
                                <?php foreach ($groups as $k => $g) : ?>
                                    <label class="check"><input type="checkbox" name="groups[]" value="<?= $k ?>" <?= in_array($k, $mine, true) ? 'checked' : '' ?>><span><strong><?= esc($g['title']) ?></strong><br><span class="text-sm text-muted"><?= esc($g['description']) ?></span></span></label>
                                <?php endforeach ?>
                            </div>
                            <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save roles</button></div>
                        </form>
                    </dialog>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table></div>
<p class="text-sm text-muted mt-2"><?= icon('info') ?> Create an admin from the server with <code>php spark app:admin --email you@example.com</code>.</p>
<?= $this->endSection() ?>
