<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Membership plans</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$memberCourses = array_filter($courses, static fn ($c) => \App\Modules\Lms\Services\CourseAccess::inMembership($c));
$modal = static function (array $p): string {
    $id = $p['id'] ?? 0;
    ob_start(); ?>
    <dialog class="modal" id="plan-<?= $id ?>">
        <form method="post" action="<?= site_url('studio/plans') ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <div class="modal-head"><h3><?= $id ? 'Edit plan' : 'New plan' ?></h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
            <div class="modal-body">
                <div class="field"><label class="label">Plan name</label><input class="input" name="name" value="<?= esc($p['name'] ?? '', 'attr') ?>" required placeholder="e.g. Prayer Circle membership"></div>
                <div class="grid grid-3">
                    <div class="field"><label class="label">Type</label><select class="select" name="type"><option value="subscription" <?= ($p['type'] ?? '') !== 'lifetime' ? 'selected' : '' ?>>Recurring</option><option value="lifetime" <?= ($p['type'] ?? '') === 'lifetime' ? 'selected' : '' ?>>One-off (lifetime)</option></select></div>
                    <div class="field"><label class="label">Billing</label><select class="select" name="billing_interval"><option value="month">Monthly</option><option value="year" <?= ($p['billing_interval'] ?? '') === 'year' ? 'selected' : '' ?>>Yearly</option></select></div>
                    <div class="field"><label class="label">Price (<?= esc(store_currency()) ?>)</label><input class="input" name="price" inputmode="decimal" value="<?= number_format((int) ($p['price_cents'] ?? 0) / 100, 2, '.', '') ?>"></div>
                </div>
                <div class="field"><label class="label">Student price (<?= esc(store_currency()) ?>) <span class="opt">(optional)</span></label>
                    <input class="input" name="student_price" inputmode="decimal" placeholder="e.g. 9.99 — leave empty for no student price" value="<?= isset($p['student_price_cents']) && $p['student_price_cents'] !== null && $p['student_price_cents'] !== '' ? number_format((int) $p['student_price_cents'] / 100, 2, '.', '') : '' ?>">
                    <p class="help">Charged instead of the price above to members with a verified student status (Settings → Students).</p></div>
            </div>
            <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save plan</button></div>
        </form>
    </dialog>
    <?php return (string) ob_get_clean();
};
?>
<div class="studio-head">
    <div><h1>Membership plans</h1><p>Plans unlock every course whose access is set to <em>Membership</em>.</p></div>
    <div class="row"><a class="btn" href="<?= site_url('pricing') ?>" target="_blank" rel="noopener"><?= icon('external') ?>Pricing page</a><button class="btn btn-primary" type="button" data-modal-open="plan-0"><?= icon('plus') ?>New plan</button></div>
</div>

<?php if ($plans === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('repeat', 'icon-lg') ?></div><h3>No plans yet</h3><p>Offer a monthly or yearly membership that includes your premium courses.</p></div>
<?php else : ?>
<div class="grid grid-3">
    <?php foreach ($plans as $p) : ?>
        <article class="card card-body">
            <div class="row between"><span class="badge <?= $p['is_active'] ? 'badge-success' : '' ?> badge-dot"><?= $p['is_active'] ? 'Live' : 'Hidden' ?></span><span class="text-xs text-muted"><?= $p['type'] === 'lifetime' ? 'One-off' : 'Billed ' . ($p['billing_interval'] === 'year' ? 'yearly' : 'monthly') ?></span></div>
            <h3 class="mt-2 mb-0"><?= esc($p['name']) ?></h3>
            <div class="stat-value mt-1"><?= money((int) $p['price_cents'], $p['currency']) ?><span class="text-sm text-muted"><?= $p['type'] === 'lifetime' ? '' : ' / ' . ($p['billing_interval'] === 'year' ? 'year' : 'month') ?></span></div>
            <?php if ($p['student_price_cents'] !== null && $p['student_price_cents'] !== '') : ?><div class="text-sm"><span class="badge badge-accent"><?= icon('graduation') ?>Students: <?= money((int) $p['student_price_cents'], $p['currency']) ?></span></div><?php endif ?>
            <p class="text-sm text-muted"><?= icon('users') ?> <?= (int) $p['members'] ?> active member<?= (int) $p['members'] === 1 ? '' : 's' ?></p>
            <div class="row gap-xs">
                <button class="btn btn-sm" type="button" data-modal-open="plan-<?= $p['id'] ?>"><?= icon('edit') ?>Edit</button>
                <form method="post" action="<?= site_url('studio/plans/' . $p['id'] . '/toggle') ?>" data-ajax data-ajax-reload><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit"><?= icon($p['is_active'] ? 'eye-off' : 'eye') ?><?= $p['is_active'] ? 'Hide' : 'Make live' ?></button></form>
            </div>
            <?= $modal($p) ?>
        </article>
    <?php endforeach ?>
</div>
<?php endif ?>

<section class="card mt-3">
    <div class="card-header"><h2><?= icon('users') ?> Members</h2><span class="text-sm text-muted">Billing runs through PayFast. Missed payments get reminders, then a <?= \App\Modules\Shop\Services\Membership::GRACE_DAYS ?>-day grace period, then access is suspended automatically.</span></div>
    <?php if ($members === []) : ?><div class="card-body text-sm text-muted">No members yet.</div><?php else : ?>
    <div class="table-wrap" style="border:0;border-radius:0"><table class="table">
        <thead><tr><th>Member</th><th>Plan</th><th>Status</th><th>Next payment / ends</th><th>Last payment</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($members as $m) : [$label, $tone] = ['active' => ['Active', 'badge-success'], 'past_due' => ['Payment overdue', 'badge-warning'], 'cancelled' => ['Cancelled', ''], 'expired' => ['Ended / suspended', 'badge-danger']][$m['status']] ?? [ucfirst($m['status']), '']; ?>
                <tr>
                    <td class="text-sm"><span class="fw-600"><?= esc($m['display_name'] ?: $m['username']) ?></span><div class="text-xs text-muted"><?= esc((string) $m['email']) ?></div></td>
                    <td class="text-sm"><?= esc($m['plan_name']) ?><div class="text-xs text-muted"><?= money($m['amount_cents'] !== null ? (int) $m['amount_cents'] : (int) $m['price_cents'], $m['currency']) ?><?= ! empty($m['is_student']) ? ' · student' : '' ?><?= $m['renews'] ? ' · auto-renews' : '' ?></div></td>
                    <td><span class="badge badge-dot <?= $tone ?>"><?= $label ?></span></td>
                    <td class="text-sm nowrap"><?= $m['expires_at'] ? nice_date($m['expires_at'], 'j M Y') : 'Lifetime' ?></td>
                    <td class="text-sm nowrap text-muted"><?= $m['last_payment_at'] ? nice_date($m['last_payment_at'], 'j M Y') : '—' ?></td>
                    <td class="text-right"><?php if (in_array($m['status'], ['active', 'past_due'], true)) : ?><form method="post" action="<?= site_url('studio/memberships/' . $m['id'] . '/cancel') ?>" data-ajax data-ajax-reload><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit" data-confirm="Cancel this membership? Billing stops; access continues to the end of the paid period."><?= icon('x') ?>Cancel</button></form><?php endif ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table></div>
    <?php endif ?>
</section>

<section class="card mt-3">
    <div class="card-header"><h2><?= icon('graduation') ?> Included courses</h2><span class="text-sm text-muted"><?= count($memberCourses) ?> of <?= count($courses) ?></span></div>
    <div class="card-body">
        <?php if ($memberCourses === []) : ?><p class="text-sm text-muted mb-0">No courses use membership access yet. Change a course’s access under <em>Course details → Access</em>.</p><?php else : ?>
            <div class="chips"><?php foreach ($memberCourses as $c) : ?><a class="chip" href="<?= site_url('studio/courses/' . $c['id']) ?>"><?= icon('book-open') ?><?= esc($c['title']) ?></a><?php endforeach ?></div>
        <?php endif ?>
    </div>
</section>
<?= $modal([]) ?>
<?= $this->endSection() ?>
