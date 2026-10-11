<?php
/** @var array<string,int> $series date => value; @var bool $isMoney */
$max = max(1, ...array_values($series ?: [0]));
$keys = array_keys($series);
?>
<div class="chart-bars" role="img" aria-label="<?= esc($label ?? 'Chart', 'attr') ?>">
    <?php foreach ($series as $d => $v) : ?>
        <div class="bar" style="height:<?= max(1, round($v / $max * 100)) ?>%;<?= $v === 0 ? 'opacity:.25' : '' ?>" data-tip="<?= date('j M', strtotime($d)) ?>: <?= ! empty($isMoney) ? money($v) : $v ?>"></div>
    <?php endforeach ?>
</div>
<div class="chart-axis"><span><?= $keys ? date('j M', strtotime($keys[0])) : '' ?></span><span><?= $keys ? date('j M', strtotime(end($keys))) : '' ?></span></div>
