<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/products') ?>">Products</a><?= icon('chevron-right') ?><span>Inventory</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="studio-head"><div><h1>Inventory</h1><p>Stock levels for every tracked variant, lowest first.</p></div>
    <form method="get" class="input-icon"><?= icon('search') ?><input class="input" type="search" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Search by product or SKU" style="width:260px"></form></div>
<div class="split">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Product</th><th>SKU</th><th class="num">In stock</th><th>Adjust</th></tr></thead>
        <tbody>
            <?php if ($rows === []) : ?><tr><td colspan="4" class="text-muted">No tracked products.</td></tr><?php endif ?>
            <?php foreach ($rows as $r) : $label = implode(' / ', array_filter([$r['option1'], $r['option2'], $r['option3']])); ?>
                <tr>
                    <td><a href="<?= site_url('studio/products/' . $r['product_id']) ?>" class="fw-600"><?= esc($r['title']) ?></a><?= $label ? '<div class="text-xs text-muted">' . esc($label) . '</div>' : '' ?></td>
                    <td class="text-sm"><?= esc($r['sku']) ?></td>
                    <td class="num"><span class="badge <?= (int) $r['qty'] <= 0 ? 'badge-danger' : ((int) $r['qty'] <= 5 ? 'badge-warning' : 'badge-success') ?>" data-qty-of="<?= $r['id'] ?>"><?= (int) $r['qty'] ?></span></td>
                    <td>
                        <form method="post" action="<?= site_url('studio/inventory/adjust') ?>" class="row row-nowrap gap-xs" data-ajax data-ajax-reload>
                            <?= csrf_field() ?><input type="hidden" name="variant_id" value="<?= $r['id'] ?>">
                            <select class="select input-sm" name="mode" style="width:auto"><option value="add">Add</option><option value="set">Set to</option></select>
                            <input class="input input-sm" type="number" name="quantity" value="0" style="width:80px">
                            <select class="select input-sm" name="reason" style="width:auto"><option value="received">Received</option><option value="correction">Correction</option><option value="damaged">Damaged</option><option value="returned">Returned</option></select>
                            <button class="btn btn-sm" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table></div>
    <aside class="card">
        <div class="card-header"><h2><?= icon('history') ?> Recent movements</h2></div>
        <div class="divider-list">
            <?php if ($moves === []) : ?><div class="card-body-sm text-sm text-muted">No stock movements yet.</div><?php endif ?>
            <?php foreach ($moves as $m) : ?>
                <div class="card-body-sm text-sm row between"><span><span class="fw-600"><?= esc($m['sku']) ?></span> · <?= esc($m['reason']) ?><?= $m['reference'] ? ' <span class="text-muted">' . esc($m['reference']) . '</span>' : '' ?><br><span class="text-xs text-muted"><?= time_ago($m['created_at']) ?></span></span><strong class="<?= (int) $m['change'] < 0 ? 'text-danger' : 'text-success' ?>"><?= (int) $m['change'] > 0 ? '+' : '' ?><?= (int) $m['change'] ?></strong></div>
            <?php endforeach ?>
        </div>
    </aside>
</div>
<?= $this->endSection() ?>
