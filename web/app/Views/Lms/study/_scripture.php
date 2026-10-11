<?php if (! empty($verses)) : ?>
    <div class="devotion-scripture">
        <?= icon('book-open', 'icon-lg') ?>
        <div class="grow">
            <div class="row between"><span class="eyebrow">Key scripture · <?= esc($verses['label']) ?></span><a class="text-xs fw-600" href="<?= $verses['url'] ?>">Read in context <?= icon('arrow-right') ?></a></div>
            <p class="mb-0 mt-1" style="font-family:var(--reading-font);font-size:1.12rem;line-height:1.7">
                <?php foreach ($verses['verses'] as $v) : ?><sup class="text-xs fw-700" style="color:var(--accent-ink);margin-right:.2em"><?= (int) $v['verse'] ?></sup><?= esc($v['text']) ?> <?php endforeach ?>
            </p>
        </div>
    </div>
<?php elseif (! empty($reference)) : ?>
    <p class="eyebrow mb-2"><?= icon('book-open') ?> <?= scripture_links(esc($reference)) ?></p>
<?php endif ?>
