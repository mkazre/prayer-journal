<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio') ?>">Studio</a><?= icon('chevron-right') ?><span>Reports</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$gross   = (int) $summary['gross'];
$count   = (int) $summary['orders'];
$aov     = $count ? intdiv($gross, $count) : 0;
$net     = $gross - (int) $summary['tax'] - (int) $summary['shipping'];
$csv     = 'data:text/csv;charset=utf-8,' . rawurlencode("date,sales\n" . implode("\n", array_map(static fn ($d, $v) => $d . ',' . number_format($v / 100, 2, '.', ''), array_keys($series), $series)));
?>
<div class="studio-head">
    <div><h1>Reports</h1><p>Paid orders and sign-ups over the last <?= $days ?> days.</p></div>
    <div class="row gap-sm">
        <div class="chips"><?php foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '12 months'] as $k => $l) : ?><a class="chip <?= $days === $k ? 'is-active' : '' ?>" href="<?= site_url('studio/reports?days=' . $k) ?>"><?= $l ?></a><?php endforeach ?></div>
        <a class="btn btn-sm" href="<?= $csv ?>" download="sales-<?= $days ?>-days.csv"><?= icon('download') ?>Export CSV</a>
    </div>
</div>

<div class="grid grid-4">
    <div class="card stat"><div class="stat-label"><?= icon('wallet') ?>Gross sales</div><div class="stat-value"><?= money($gross) ?></div><div class="stat-delta text-muted"><?= $count ?> paid orders</div></div>
    <div class="card stat"><div class="stat-label"><?= icon('receipt') ?>Average order</div><div class="stat-value"><?= money($aov) ?></div><div class="stat-delta text-muted">Net of tax &amp; shipping: <?= money($net) ?></div></div>
    <div class="card stat"><div class="stat-label"><?= icon('graduation') ?>Courses &amp; memberships</div><div class="stat-value"><?= money($courseSales + $planSales) ?></div><div class="stat-delta text-muted"><?= money($courseSales) ?> courses · <?= money($planSales) ?> plans</div></div>
    <div class="card stat"><div class="stat-label"><?= icon('users') ?>New people</div><div class="stat-value"><?= number_format($newUsers) ?></div><div class="stat-delta text-muted"><?= number_format($enrolls) ?> course enrollments</div></div>
</div>

<section class="card mt-3">
    <div class="card-header"><h2><?= icon('chart') ?> Sales over time</h2><span class="text-sm text-muted">Discounts given: <?= money((int) $summary['discounts']) ?> · Tax collected: <?= money((int) $summary['tax']) ?></span></div>
    <div class="card-body"><?= view('Studio/_bars', ['series' => $series, 'isMoney' => true, 'label' => 'Daily sales']) ?></div>
</section>

<div class="grid grid-2 mt-3">
    <section class="card">
        <div class="card-header"><h2><?= icon('trophy') ?> Top products</h2></div>
        <?php if ($top === []) : ?><div class="card-body text-sm text-muted">No sales in this period.</div><?php else : ?>
        <div class="table-wrap" style="border:0;border-radius:0"><table class="table">
            <thead><tr><th>Product</th><th class="num">Units</th><th class="num">Revenue</th></tr></thead>
            <tbody><?php foreach ($top as $t) : ?><tr><td><?= esc($t['title_snapshot']) ?></td><td class="num"><?= (int) $t['units'] ?></td><td class="num"><?= money((int) $t['revenue']) ?></td></tr><?php endforeach ?></tbody>
        </table></div>
        <?php endif ?>
    </section>
    <section class="card">
        <div class="card-header"><h2><?= icon('percent') ?> Discount codes used</h2><a class="btn btn-sm btn-ghost" href="<?= site_url('studio/discounts') ?>">Manage</a></div>
        <?php if ($codes === []) : ?><div class="card-body text-sm text-muted">No codes redeemed in this period.</div><?php else : ?>
        <div class="table-wrap" style="border:0;border-radius:0"><table class="table">
            <thead><tr><th>Code</th><th class="num">Orders</th><th class="num">Discount given</th></tr></thead>
            <tbody><?php foreach ($codes as $c) : ?><tr><td class="fw-600"><?= esc($c['code']) ?></td><td class="num"><?= (int) $c['uses'] ?></td><td class="num"><?= money((int) $c['total']) ?></td></tr><?php endforeach ?></tbody>
        </table></div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
