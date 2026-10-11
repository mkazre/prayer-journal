<?php if (session('error') !== null) : ?>
    <div class="alert alert-danger mb-2"><?= icon('alert') ?><div><?= esc(session('error')) ?></div></div>
<?php elseif (session('errors') !== null) : ?>
    <div class="alert alert-danger mb-2"><?= icon('alert') ?><div>
        <?php foreach ((array) session('errors') as $error) : ?><?= esc($error) ?><br><?php endforeach ?>
    </div></div>
<?php endif ?>
<?php if (session('message') !== null) : ?>
    <div class="alert alert-success mb-2"><?= icon('check-circle') ?><div><?= esc(session('message')) ?></div></div>
<?php endif ?>
