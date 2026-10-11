<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Discounts</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Models\DiscountModel;

$dt    = static fn (?string $v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
$modal = static function (array $d) use ($dt): string {
    $id = $d['id'] ?? 0;
    ob_start(); ?>
    <dialog class="modal" id="discount-<?= $id ?>">
        <form method="post" action="<?= site_url('studio/discounts') ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <div class="modal-head"><h3><?= $id ? 'Edit ' . esc($d['code']) : 'New discount code' ?></h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
            <div class="modal-body">
                <div class="field"><label class="label">Code <span class="opt">(leave blank to generate one)</span></label><input class="input" name="code" value="<?= esc($d['code'] ?? '', 'attr') ?>" style="text-transform:uppercase" placeholder="e.g. WELCOME10" maxlength="40"></div>
                <div class="grid grid-2">
                    <div class="field"><label class="label">Type</label><select class="select" name="type"><?php foreach (DiscountModel::TYPES as $k => $l) : ?><option value="<?= $k ?>" <?= ($d['type'] ?? 'percent') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></div>
                    <div class="field"><label class="label">Value <span class="opt">(% or amount)</span></label><input class="input" name="value" inputmode="decimal" value="<?= isset($d['type']) ? ($d['type'] === 'fixed' ? number_format((int) $d['value'] / 100, 2, '.', '') : (int) $d['value']) : '10' ?>"></div>
                </div>
                <div class="field"><label class="label">Internal description <span class="opt">(optional)</span></label><input class="input" name="description" value="<?= esc((string) ($d['description'] ?? ''), 'attr') ?>" placeholder="e.g. Newsletter sign-up offer"></div>
                <div class="grid grid-2">
                    <div class="field"><label class="label">Minimum order</label><input class="input" name="min_subtotal" inputmode="decimal" value="<?= isset($d['min_subtotal_cents']) ? number_format((int) $d['min_subtotal_cents'] / 100, 2, '.', '') : '' ?>" placeholder="No minimum"></div>
                    <div class="field"><label class="label">Usage limit</label><input class="input" type="number" min="1" name="max_redemptions" value="<?= esc((string) ($d['max_redemptions'] ?? ''), 'attr') ?>" placeholder="Unlimited"></div>
                    <div class="field"><label class="label">Starts</label><input class="input" type="datetime-local" name="starts_at" value="<?= $dt($d['starts_at'] ?? null) ?>"></div>
                    <div class="field"><label class="label">Ends</label><input class="input" type="datetime-local" name="expires_at" value="<?= $dt($d['expires_at'] ?? null) ?>"></div>
                </div>
            </div>
            <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save discount</button></div>
        </form>
    </dialog>
    <?php return (string) ob_get_clean();
};
?>
<div class="studio-head">
    <div><h1>Discounts</h1><p>Codes customers enter at checkout — percentage, fixed amount or free shipping.</p></div>
    <button class="btn btn-primary" type="button" data-modal-open="discount-0"><?= icon('plus') ?>Create discount</button>
</div>
<?php if ($discounts === []) : ?>
    <div class="card empty"><div class="feature-icon"><?= icon('percent', 'icon-lg') ?></div><h3>No discount codes</h3><p>Reward your community with a welcome offer or a seasonal sale.</p><button class="btn btn-primary" type="button" data-modal-open="discount-0"><?= icon('plus') ?>Create discount</button></div>
<?php else : ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Code</th><th>Offer</th><th>Status</th><th>Used</th><th>Active dates</th><th class="num">Discount given</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($discounts as $d) : ?>
            <tr>
                <td><button class="btn btn-sm btn-soft" type="button" data-copy="<?= esc($d['code'], 'attr') ?>" title="Copy code"><?= icon('copy') ?><?= esc($d['code']) ?></button><?= $d['description'] ? '<div class="text-xs text-muted mt-1">' . esc($d['description']) . '</div>' : '' ?></td>
                <td class="text-sm"><?= esc(DiscountModel::describe($d)) ?><?= $d['min_subtotal_cents'] ? '<div class="text-xs text-muted">Orders over ' . money((int) $d['min_subtotal_cents']) . '</div>' : '' ?></td>
                <td><?= $d['problem'] === null ? '<span class="badge badge-dot badge-success">Active</span>' : '<span class="badge badge-dot badge-warning" title="' . esc($d['problem'], 'attr') . '">' . esc(rtrim(str_replace(['This code ', 'This discount '], '', $d['problem']), '.')) . '</span>' ?></td>
                <td class="text-sm"><?= (int) $d['times_redeemed'] ?><?= $d['max_redemptions'] ? ' / ' . (int) $d['max_redemptions'] : '' ?></td>
                <td class="text-sm"><?= $d['starts_at'] ? nice_date($d['starts_at'], 'j M Y') : 'Now' ?> – <?= $d['expires_at'] ? nice_date($d['expires_at'], 'j M Y') : 'No end' ?></td>
                <td class="num"><?= money((int) $d['sales']) ?></td>
                <td class="text-right nowrap">
                    <form method="post" action="<?= site_url('studio/discounts/' . $d['id'] . '/toggle') ?>" style="display:inline" data-ajax data-ajax-reload><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" aria-label="<?= $d['is_active'] ? 'Pause' : 'Activate' ?>" title="<?= $d['is_active'] ? 'Pause' : 'Activate' ?>"><?= icon($d['is_active'] ? 'eye-off' : 'eye') ?></button></form>
                    <button class="btn btn-sm btn-ghost btn-icon" type="button" data-modal-open="discount-<?= $d['id'] ?>" aria-label="Edit"><?= icon('edit') ?></button>
                    <form method="post" action="<?= site_url('studio/discounts/' . $d['id'] . '/delete') ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" data-confirm="Delete <?= esc($d['code'], 'attr') ?>?" aria-label="Delete"><?= icon('trash') ?></button></form>
                    <?= $modal($d) ?>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table></div>
<?php endif ?>
<?= $modal([]) ?>
<?= $this->endSection() ?>
