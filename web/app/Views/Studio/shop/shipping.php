<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Shipping &amp; tax</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Shop\Services\Pricing;

$amt   = static fn ($c) => $c === null ? '' : number_format((int) $c / 100, 2, '.', '');
$close = '<button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close">' . icon('x') . '</button>';
$countryNames = static fn (string $codes) => implode(', ', array_map(static fn ($c) => $c === '*' ? 'Rest of world' : (Pricing::COUNTRIES[$c] ?? $c), array_filter(explode(',', $codes))));
?>
<div class="studio-head">
    <div><h1>Shipping &amp; tax</h1><p>Zones decide which rates a customer sees, based on their delivery country. Use <code>*</code> for “everywhere else”.</p></div>
    <button class="btn btn-primary" type="button" data-modal-open="zone-0"><?= icon('plus') ?>Add zone</button>
</div>

<div class="alert alert-info mb-3"><?= icon('info') ?><div>Free shipping over <strong><?= site_setting('free_shipping_over_cents') ? money((int) site_setting('free_shipping_over_cents')) : 'no threshold' ?></strong> and prices <?= site_setting('prices_include_tax', '1') ? 'including' : 'excluding' ?> tax are set in <a href="<?= site_url('studio/settings#store') ?>">Store settings</a>.</div></div>

<div class="stack mb-4">
    <?php if ($zones === []) : ?><div class="card empty"><div class="feature-icon"><?= icon('truck', 'icon-lg') ?></div><h3>No shipping zones</h3><p>Without zones, physical products can’t be checked out.</p></div><?php endif ?>
    <?php foreach ($zones as $z) : ?>
        <section class="card">
            <div class="card-header">
                <div><h2><?= icon('globe') ?> <?= esc($z['name']) ?></h2><div class="text-sm text-muted"><?= esc($countryNames($z['countries'])) ?></div></div>
                <div class="row gap-xs">
                    <button class="btn btn-sm" type="button" data-modal-open="rate-<?= $z['id'] ?>-0"><?= icon('plus') ?>Rate</button>
                    <button class="btn btn-sm btn-ghost btn-icon" type="button" data-modal-open="zone-<?= $z['id'] ?>" aria-label="Edit zone"><?= icon('edit') ?></button>
                    <form method="post" action="<?= site_url('studio/shipping/zones/' . $z['id'] . '/delete') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" data-confirm="Delete this zone and its rates?" aria-label="Delete zone"><?= icon('trash') ?></button></form>
                </div>
            </div>
            <div class="divider-list">
                <?php if ($z['rates'] === []) : ?><div class="card-body-sm text-sm text-muted">No rates — customers in this zone can’t check out physical items.</div><?php endif ?>
                <?php foreach ($z['rates'] as $r) : ?>
                    <div class="card-body-sm row between">
                        <span><span class="fw-600"><?= esc($r['name']) ?></span><?= $r['min_order_cents'] ? '<span class="text-xs text-muted"> · orders over ' . money((int) $r['min_order_cents']) . '</span>' : '' ?></span>
                        <span class="row gap-xs"><strong><?= (int) $r['price_cents'] ? money((int) $r['price_cents']) : 'Free' ?></strong>
                            <button class="btn btn-sm btn-ghost btn-icon" type="button" data-modal-open="rate-<?= $z['id'] ?>-<?= $r['id'] ?>" aria-label="Edit rate"><?= icon('edit') ?></button>
                            <form method="post" action="<?= site_url('studio/shipping/rates/' . $r['id'] . '/delete') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" data-confirm="Delete this rate?" aria-label="Delete rate"><?= icon('trash') ?></button></form>
                        </span>
                    </div>
                <?php endforeach ?>
            </div>
        </section>
        <?php foreach (array_merge($z['rates'], [['id' => 0]]) as $r) : ?>
            <dialog class="modal" id="rate-<?= $z['id'] ?>-<?= $r['id'] ?>">
                <form method="post" action="<?= site_url('studio/shipping/rates') ?>">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="zone_id" value="<?= $z['id'] ?>">
                    <div class="modal-head"><h3><?= $r['id'] ? 'Edit rate' : 'New rate' ?> · <?= esc($z['name']) ?></h3><?= $close ?></div>
                    <div class="modal-body">
                        <div class="field"><label class="label">Name</label><input class="input" name="name" value="<?= esc($r['name'] ?? '', 'attr') ?>" required placeholder="e.g. Standard courier (3–5 days)"></div>
                        <div class="grid grid-2">
                            <div class="field"><label class="label">Price (<?= esc(store_currency()) ?>)</label><input class="input" name="price" inputmode="decimal" value="<?= $amt($r['price_cents'] ?? 0) ?>"></div>
                            <div class="field"><label class="label">Only for orders over <span class="opt">(optional)</span></label><input class="input" name="min_order" inputmode="decimal" value="<?= $amt($r['min_order_cents'] ?? null) ?>"></div>
                        </div>
                    </div>
                    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save rate</button></div>
                </form>
            </dialog>
        <?php endforeach ?>
    <?php endforeach ?>
</div>

<?php foreach (array_merge($zones, [['id' => 0]]) as $z) : ?>
    <dialog class="modal" id="zone-<?= $z['id'] ?>">
        <form method="post" action="<?= site_url('studio/shipping/zones') ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $z['id'] ?>">
            <div class="modal-head"><h3><?= $z['id'] ? 'Edit zone' : 'New zone' ?></h3><?= $close ?></div>
            <div class="modal-body">
                <div class="field"><label class="label">Zone name</label><input class="input" name="name" value="<?= esc($z['name'] ?? '', 'attr') ?>" required placeholder="e.g. Southern Africa"></div>
                <div class="field"><label class="label">Country codes</label><input class="input" name="countries" value="<?= esc($z['countries'] ?? '', 'attr') ?>" required placeholder="ZA,BW,NA or *"><p class="help">Comma-separated ISO codes. <code>*</code> matches every country not in another zone.</p></div>
            </div>
            <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save zone</button></div>
        </form>
    </dialog>
<?php endforeach ?>

<section id="tax" class="card">
    <div class="card-header"><h2><?= icon('percent') ?> Tax rates</h2></div>
    <div class="card-body">
        <form method="post" action="<?= site_url('studio/shipping/taxes') ?>" class="row gap-sm mb-2">
            <?= csrf_field() ?>
            <input class="input input-sm" name="country" placeholder="Country (ZA)" maxlength="2" required style="width:120px;text-transform:uppercase">
            <input class="input input-sm" name="region" placeholder="Region (optional)" style="width:180px">
            <div class="input-icon"><input class="input input-sm" type="number" name="rate" min="0" max="100" placeholder="Rate %" required style="width:110px"></div>
            <button class="btn btn-sm" type="submit"><?= icon('plus') ?>Add rate</button>
        </form>
        <?php if ($taxes === []) : ?><p class="text-sm text-muted mb-0">No tax rules — no tax will be charged.</p><?php else : ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Country</th><th>Region</th><th class="num">Rate</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($taxes as $t) : ?>
                    <tr><td><?= esc(Pricing::COUNTRIES[$t['country']] ?? $t['country']) ?> <span class="text-muted text-xs"><?= esc($t['country']) ?></span></td><td class="text-sm"><?= esc((string) ($t['region'] ?: 'All regions')) ?></td><td class="num"><?= (int) $t['rate_percent'] ?>%</td>
                        <td class="text-right"><form method="post" action="<?= site_url('studio/shipping/taxes/' . $t['id'] . '/delete') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost btn-icon" type="submit" data-confirm="Delete this tax rule?" aria-label="Delete"><?= icon('trash') ?></button></form></td></tr>
                <?php endforeach ?>
            </tbody>
        </table></div>
        <?php endif ?>
    </div>
</section>
<?= $this->endSection() ?>
