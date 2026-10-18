<?php
/**
 * The one branded email layout every notification uses. Table-based with
 * inline styles so it renders in Gmail, Outlook and phone mail apps.
 *
 * @var string      $subject
 * @var string|null $toName
 */
$brand   = preg_match('/^#[0-9a-f]{6}$/i', (string) site_setting('brand_color')) ? site_setting('brand_color') : '#232d47';
$accent  = preg_match('/^#[0-9a-f]{6}$/i', (string) site_setting('accent_color')) ? site_setting('accent_color') : '#c9a227';
$name    = site_name();
$heading = $heading ?? $subject;
$tone    = $tone ?? 'brand';
$toneBg  = ['brand' => $brand, 'success' => '#1e8a5a', 'warning' => '#b7791f', 'danger' => '#c0392b'][$tone] ?? $brand;
$font    = "-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
$btn     = static fn (string $label, string $url, bool $primary = true) => '<a href="' . esc($url, 'attr') . '" style="display:inline-block;padding:13px 26px;border-radius:10px;font-family:' . $font . ';font-size:15px;font-weight:700;text-decoration:none;' . ($primary ? 'background:' . $brand . ';color:#ffffff;' : 'background:#eff1f6;color:' . $brand . ';') . '">' . esc($label) . '</a>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= esc($subject) ?></title>
</head>
<body style="margin:0;padding:0;background:#f1f3f8;-webkit-text-size-adjust:100%;">
    <?php if (! empty($preheader)) : ?><div style="display:none;max-height:0;overflow:hidden;opacity:0;"><?= esc($preheader) ?></div><?php endif ?>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f3f8;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">
                    <tr>
                        <td style="padding:0 4px 18px;">
                            <a href="<?= esc(base_url(), 'attr') ?>" style="text-decoration:none;color:#12151c;font-family:<?= $font ?>;font-size:18px;font-weight:800;">
                                <img src="<?= esc(logo_url(), 'attr') ?>" width="44" height="44" alt="<?= esc($name, 'attr') ?>" style="vertical-align:middle;border-radius:10px;border:0;margin-right:10px;"><?= esc($name) ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;border-radius:18px;border:1px solid #e0e4ef;overflow:hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td style="height:5px;background:<?= $toneBg ?>;font-size:0;line-height:0;">&nbsp;</td></tr>
                                <tr>
                                    <td style="padding:32px 32px 8px;font-family:<?= $font ?>;color:#12151c;">
                                        <h1 style="margin:0 0 14px;font-size:24px;line-height:1.3;font-weight:800;color:#12151c;"><?= esc($heading) ?></h1>
                                        <?php if (! empty($toName)) : ?><p style="margin:0 0 14px;font-size:16px;line-height:1.6;color:#353a48;">Hi <?= esc(strtok($toName, ' ')) ?>,</p><?php endif ?>
                                        <?php foreach ($paragraphs ?? [] as $p) : ?><p style="margin:0 0 14px;font-size:16px;line-height:1.6;color:#353a48;"><?= $p ?></p><?php endforeach ?>
                                    </td>
                                </tr>
                                <?php if (! empty($rows)) : ?>
                                    <tr><td style="padding:6px 32px 10px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f6f7fa;border-radius:12px;">
                                            <?php foreach ($rows as $label => $value) : ?>
                                                <tr>
                                                    <td style="padding:10px 16px;font-family:<?= $font ?>;font-size:14px;color:#676c79;border-bottom:1px solid #e7ebf4;"><?= esc($label) ?></td>
                                                    <td align="right" style="padding:10px 16px;font-family:<?= $font ?>;font-size:14px;color:#12151c;font-weight:700;border-bottom:1px solid #e7ebf4;"><?= $value ?></td>
                                                </tr>
                                            <?php endforeach ?>
                                        </table>
                                    </td></tr>
                                <?php endif ?>
                                <?php if (! empty($items)) : ?>
                                    <tr><td style="padding:6px 32px 4px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <?php foreach ($items as $i) : ?>
                                                <tr>
                                                    <td style="padding:10px 0;border-bottom:1px solid #e7ebf4;font-family:<?= $font ?>;">
                                                        <div style="font-size:15px;font-weight:700;color:#12151c;"><?= esc($i['title']) ?></div>
                                                        <?php if (! empty($i['meta'])) : ?><div style="font-size:13px;color:#676c79;margin-top:2px;"><?= $i['meta'] ?></div><?php endif ?>
                                                        <?php if (isset($i['progress'])) : $pct = max(0, min(100, (int) $i['progress'])); ?>
                                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px;"><tr>
                                                                <?php if ($pct > 0) : ?><td width="<?= $pct ?>%" style="height:8px;background:<?= $accent ?>;border-radius:8px 0 0 8px;font-size:0;line-height:0;">&nbsp;</td><?php endif ?>
                                                                <?php if ($pct < 100) : ?><td style="height:8px;background:#e7ebf4;border-radius:<?= $pct > 0 ? '0 8px 8px 0' : '8px' ?>;font-size:0;line-height:0;">&nbsp;</td><?php endif ?>
                                                            </tr></table>
                                                        <?php endif ?>
                                                        <?php if (! empty($i['link'])) : ?><div style="margin-top:8px;"><a href="<?= esc($i['link'][1], 'attr') ?>" style="font-size:14px;font-weight:700;color:<?= $brand ?>;text-decoration:none;"><?= esc($i['link'][0]) ?> &rarr;</a></div><?php endif ?>
                                                    </td>
                                                    <td align="right" valign="top" style="padding:10px 0;border-bottom:1px solid #e7ebf4;font-family:<?= $font ?>;font-size:15px;font-weight:700;color:#12151c;white-space:nowrap;"><?= esc($i['amount'] ?? '') ?></td>
                                                </tr>
                                            <?php endforeach ?>
                                            <?php foreach ($totals ?? [] as $label => $value) : $last = $label === array_key_last($totals); ?>
                                                <tr>
                                                    <td style="padding:<?= $last ? '12px' : '6px' ?> 0 4px;font-family:<?= $font ?>;font-size:<?= $last ? '16px' : '14px' ?>;color:<?= $last ? '#12151c' : '#676c79' ?>;font-weight:<?= $last ? '800' : '400' ?>;"><?= esc($label) ?></td>
                                                    <td align="right" style="padding:<?= $last ? '12px' : '6px' ?> 0 4px;font-family:<?= $font ?>;font-size:<?= $last ? '16px' : '14px' ?>;color:#12151c;font-weight:<?= $last ? '800' : '600' ?>;"><?= esc($value) ?></td>
                                                </tr>
                                            <?php endforeach ?>
                                        </table>
                                    </td></tr>
                                <?php endif ?>
                                <?php if (! empty($verse)) : ?>
                                    <tr><td style="padding:14px 32px 4px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#faf3dc;border-radius:12px;border-left:4px solid <?= $accent ?>;"><tr><td style="padding:14px 18px;font-family:Georgia,serif;font-size:16px;line-height:1.6;color:#353a48;font-style:italic;">“<?= esc($verse['text']) ?>”<div style="font-family:<?= $font ?>;font-style:normal;font-size:13px;font-weight:700;color:#7a5b00;margin-top:6px;"><?= esc($verse['reference']) ?></div></td></tr></table>
                                    </td></tr>
                                <?php endif ?>
                                <?php if (! empty($button)) : ?>
                                    <tr><td style="padding:18px 32px 6px;">
                                        <?= $btn($button[0], $button[1]) ?>
                                        <?php if (! empty($secondary)) : ?>&nbsp;&nbsp;<?= $btn($secondary[0], $secondary[1], false) ?><?php endif ?>
                                    </td></tr>
                                <?php endif ?>
                                <?php if (! empty($note)) : ?>
                                    <tr><td style="padding:16px 32px 4px;font-family:<?= $font ?>;font-size:14px;line-height:1.6;color:#676c79;"><?= $note ?></td></tr>
                                <?php endif ?>
                                <tr><td style="padding:22px 32px 30px;font-family:<?= $font ?>;font-size:15px;line-height:1.6;color:#353a48;">With grace,<br><strong><?= esc($name) ?></strong></td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 16px 0;font-family:<?= $font ?>;font-size:12px;line-height:1.6;color:#828897;">
                            <?= esc($footer ?? 'You are receiving this email because you have an account at ' . $name . '.') ?><br>
                            <a href="<?= esc(site_url('account#preferences'), 'attr') ?>" style="color:<?= $brand ?>;text-decoration:none;">Email preferences</a>
                            &nbsp;·&nbsp;<a href="<?= esc(base_url(), 'attr') ?>" style="color:<?= $brand ?>;text-decoration:none;"><?= esc(preg_replace('#^https?://(www\.)?|/$#', '', base_url())) ?></a>
                            <?php if ($company = site_setting('company_name')) : ?><br><?= esc($company) ?><?php endif ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
