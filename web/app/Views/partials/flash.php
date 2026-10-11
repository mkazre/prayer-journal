<?php
$session = session();
foreach (['message' => 'success', 'error' => 'error', 'info' => 'info'] as $key => $type) :
    $value = $session->getFlashdata($key);
    if (is_string($value) && $value !== '') : ?>
        <span hidden data-flash="<?= esc($value, 'attr') ?>" data-flash-type="<?= $type ?>"></span>
    <?php endif;
endforeach;
$errors = $session->getFlashdata('errors');
if (is_array($errors)) :
    foreach ($errors as $err) : ?>
        <span hidden data-flash="<?= esc((string) $err, 'attr') ?>" data-flash-type="error"></span>
    <?php endforeach;
endif ?>
<noscript>
    <?php if ($m = $session->getFlashdata('message')) : ?><div class="container mt-2"><div class="alert alert-success"><?= esc($m) ?></div></div><?php endif ?>
    <?php if ($m = $session->getFlashdata('error')) : ?><div class="container mt-2"><div class="alert alert-danger"><?= esc($m) ?></div></div><?php endif ?>
</noscript>
