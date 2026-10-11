<?php /** @var array $quote */ ?>
<div class="summary-row"><span>Subtotal</span><span><?= money($quote['subtotal']) ?></span></div>
<?php if ($quote['discount'] > 0 || ($quote['discount_row']['type'] ?? '') === 'shipping') : ?>
    <div class="summary-row text-success"><span><?= icon('tag') ?> <?= esc($quote['discount_row']['code']) ?></span><span><?= $quote['discount'] > 0 ? '−' . money($quote['discount']) : 'Free shipping' ?></span></div>
<?php endif ?>
<?php if ($quote['needs_shipping']) : ?>
    <div class="summary-row"><span>Shipping<?= ! empty($quote['shipping_rate']['name']) && empty($hideRateName) ? ' · ' . esc($quote['shipping_rate']['name']) : '' ?></span><span><?= ! $quote['shipping_available'] ? 'Unavailable' : ($quote['shipping'] === 0 ? 'Free' : money($quote['shipping'])) ?></span></div>
<?php endif ?>
<?php if ($quote['tax'] > 0 && ! $quote['tax_included']) : ?><div class="summary-row"><span><?= esc($quote['tax_label']) ?></span><span><?= money($quote['tax']) ?></span></div><?php endif ?>
<div class="summary-row total"><span>Total</span><span><?= money($quote['total']) ?></span></div>
<?php if ($quote['tax'] > 0 && $quote['tax_included']) : ?><div class="text-xs text-muted text-right">Includes <?= money($quote['tax']) ?> <?= esc($quote['tax_label']) ?></div><?php endif ?>
