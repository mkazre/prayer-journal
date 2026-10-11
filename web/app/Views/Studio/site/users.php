<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Users &amp; roles</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$roleTone = ['superadmin' => 'badge-danger', 'admin' => 'badge-brand', 'manager' => 'badge-brand', 'instructor' => 'badge-accent', 'vendor' => 'badge-info', 'student' => ''];
$link     = static fn (array $o) => site_url('studio/users') . '?' . http_build_query(array_filter(array_merge(['group' => $group, 'q' => $q, 'status' => $status], $o)));
$me       = (int) auth()->id();
$iAmSuper = auth()->user()->inGroup('superadmin');

/** Role checkboxes shared by the add and edit dialogs. */
$roleBoxes = static function (array $checked) use ($assignable): string {
    ob_start(); ?>
    <fieldset class="field">
        <legend class="label">Roles</legend>
        <div class="stack" style="gap:.6rem">
            <?php foreach ($assignable as $k => $g) : ?>
                <label class="check"><input type="checkbox" name="groups[]" value="<?= esc($k, 'attr') ?>" <?= in_array($k, $checked, true) ? 'checked' : '' ?>><span><strong><?= esc($g['title']) ?></strong><br><span class="text-sm text-muted"><?= esc($g['description']) ?></span></span></label>
            <?php endforeach ?>
        </div>
    </fieldset>
    <?php return (string) ob_get_clean();
};
?>
<div class="studio-head">
    <div><h1>Users &amp; roles</h1><p>Create accounts for customers and staff, choose what each person can do, reset passwords and suspend access.</p></div>
    <button class="btn btn-primary" type="button" data-modal-open="user-new"><?= icon('plus') ?>Add user</button>
</div>

<div class="toolbar">
    <div class="chips">
        <a class="chip <?= $group === '' && $status === '' ? 'is-active' : '' ?>" href="<?= site_url('studio/users') . ($q ? '?q=' . rawurlencode($q) : '') ?>">Everyone</a>
        <?php foreach ($groups as $k => $g) : ?><a class="chip <?= $group === $k ? 'is-active' : '' ?>" href="<?= $link(['group' => $k, 'status' => null]) ?>"><?= esc($g['title']) ?> <span class="text-muted"><?= (int) ($counts[$k] ?? 0) ?></span></a><?php endforeach ?>
        <a class="chip <?= $status === 'suspended' ? 'is-active' : '' ?>" href="<?= $link(['status' => 'suspended', 'group' => null]) ?>"><?= icon('lock') ?>Suspended</a>
    </div>
    <form method="get" class="input-icon"><?php if ($group) : ?><input type="hidden" name="group" value="<?= esc($group, 'attr') ?>"><?php endif ?><?= icon('search') ?><input class="input input-sm" type="search" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Name, username or email" style="width:240px"></form>
</div>

<div class="table-wrap"><table class="table">
    <thead><tr><th>Person</th><th>Roles</th><th class="num">Courses</th><th class="num">Orders</th><th>Joined</th><th>Last active</th><th></th></tr></thead>
    <tbody>
        <?php if ($users === []) : ?><tr><td colspan="7" class="text-muted">No one matches.</td></tr><?php endif ?>
        <?php foreach ($users as $u) :
            $mine      = array_values(array_filter(explode(',', (string) $u['groups_list'])));
            $name      = $u['display_name'] ?: $u['username'];
            $suspended = $u['status'] === 'banned';
            $locked    = in_array('superadmin', $mine, true) && ! $iAmSuper; ?>
            <tr>
                <td><div class="row row-nowrap"><?= avatar((int) $u['id'], $name, 'avatar-sm') ?><div><div class="fw-600"><?= esc($name) ?><?= (int) $u['id'] === $me ? ' <span class="text-muted">(you)</span>' : '' ?></div><div class="text-xs text-muted">@<?= esc($u['username']) ?> · <?= esc((string) $u['email']) ?></div></div></div></td>
                <td>
                    <?php foreach ($mine as $g) : ?><span class="badge <?= $roleTone[$g] ?? '' ?>"><?= esc($groups[$g]['title'] ?? $g) ?></span> <?php endforeach ?>
                    <?php if ($suspended) : ?><span class="badge badge-danger"><?= icon('lock') ?>Suspended</span><?php endif ?>
                </td>
                <td class="num"><?= (int) $u['courses'] ?></td>
                <td class="num"><?= (int) $u['orders'] ?></td>
                <td class="text-sm nowrap"><?= nice_date($u['created_at'], 'j M Y') ?></td>
                <td class="text-sm nowrap text-muted"><?= $u['last_active'] ? time_ago($u['last_active']) : 'Never' ?></td>
                <td class="text-right nowrap">
                    <?php if (! $locked) : ?>
                        <button class="btn btn-sm" type="button" data-modal-open="user-<?= $u['id'] ?>"><?= icon('edit') ?>Edit</button>
                        <?php if ((int) $u['id'] !== $me) : ?>
                            <form method="post" action="<?= site_url('studio/users/' . $u['id'] . '/suspend') ?>" style="display:inline" data-ajax data-ajax-reload><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" title="<?= $suspended ? 'Restore access' : 'Suspend' ?>" aria-label="<?= $suspended ? 'Restore access' : 'Suspend' ?>" <?= $suspended ? '' : 'data-confirm="Suspend ' . esc($u['username'], 'attr') . '? They will be signed out and unable to sign in."' ?>><?= icon($suspended ? 'unlock' : 'lock') ?></button></form>
                            <form method="post" action="<?= site_url('studio/users/' . $u['id'] . '/delete') ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" aria-label="Delete" data-confirm="Delete <?= esc($u['username'], 'attr') ?>? Their orders and history are kept for your records, but they can no longer sign in."><?= icon('trash') ?></button></form>
                        <?php endif ?>

                        <dialog class="modal" id="user-<?= $u['id'] ?>">
                            <form method="post" action="<?= site_url('studio/users/' . $u['id']) ?>" class="text-left">
                                <?= csrf_field() ?>
                                <div class="modal-head"><h3>Edit <?= esc($name) ?></h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
                                <div class="modal-body">
                                    <div class="grid grid-2">
                                        <div class="field"><label class="label">Display name</label><input class="input" name="display_name" value="<?= esc((string) $u['display_name'], 'attr') ?>" maxlength="120"></div>
                                        <div class="field"><label class="label">Username</label><input class="input" name="username" value="<?= esc($u['username'], 'attr') ?>" required pattern="[A-Za-z0-9._\-]{3,30}"></div>
                                    </div>
                                    <div class="field"><label class="label">Email</label><input class="input" type="email" name="email" value="<?= esc((string) $u['email'], 'attr') ?>" required></div>
                                    <div class="field"><label class="label">New password <span class="opt">(leave blank to keep the current one)</span></label><input class="input" type="text" name="password" minlength="8" autocomplete="new-password"><span class="help">Setting a new password signs them out of the app and other devices.</span></div>
                                    <?= $roleBoxes($mine) ?>
                                </div>
                                <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('save') ?>Save changes</button></div>
                            </form>
                        </dialog>
                    <?php else : ?>
                        <span class="text-xs text-muted">Superadmin only</span>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table></div>

<dialog class="modal" id="user-new">
    <form method="post" action="<?= site_url('studio/users') ?>">
        <?= csrf_field() ?>
        <div class="modal-head"><h3>Add a user</h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
        <div class="modal-body">
            <div class="grid grid-2">
                <div class="field"><label class="label">Full name</label><input class="input" name="display_name" maxlength="120" placeholder="e.g. Thandi Mokoena" autofocus></div>
                <div class="field"><label class="label">Email</label><input class="input" type="email" name="email" required placeholder="name@example.com"></div>
                <div class="field"><label class="label">Username <span class="opt">(optional)</span></label><input class="input" name="username" pattern="[A-Za-z0-9._\-]{3,30}" placeholder="Made from the email if blank"></div>
                <div class="field"><label class="label">Password <span class="opt">(optional)</span></label><input class="input" type="text" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to generate one"></div>
            </div>
            <?= $roleBoxes(['student']) ?>
            <p class="help mb-0">The account is active immediately. If you leave the password blank, a temporary password is shown once after saving. Send it to the person, and they can change it under Account settings.</p>
        </div>
        <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('plus') ?>Create account</button></div>
    </form>
</dialog>
<?= $this->endSection() ?>
