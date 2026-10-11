<?php $kinds = \App\Modules\Lms\Models\JournalEntryModel::KINDS; ?>
<dialog class="modal" id="new-entry" aria-labelledby="new-entry-title">
    <form method="post" action="<?= site_url('journal/create') ?>" data-ajax data-ajax-reload data-ajax-close>
        <?= csrf_field() ?>
        <div class="modal-head"><h3 id="new-entry-title"><?= icon('feather') ?> New journal entry</h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
        <div class="modal-body">
            <div class="field">
                <span class="label">Type</span>
                <div class="segmented" role="radiogroup">
                    <?php foreach ($kinds as $k => [$label, $ic]) : ?>
                        <label><input type="radio" name="kind" value="<?= $k ?>" <?= $k === 'prayer' ? 'checked' : '' ?>><span><?= icon($ic) ?><?= esc($label) ?></span></label>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="field"><label class="label" for="ne-title">Title <span class="opt">(optional)</span></label><input class="input" id="ne-title" name="title" maxlength="200" placeholder="e.g. Healing for Mum"></div>
            <div class="field"><label class="label" for="ne-body">Entry</label><textarea class="textarea" id="ne-body" name="body" rows="6" required placeholder="Write your prayer, meditation or note…" style="font-family:var(--reading-font)"></textarea></div>
            <div class="form-grid">
                <div class="field"><label class="label" for="ne-scripture">Supporting scripture <span class="opt">(optional)</span></label><input class="input" id="ne-scripture" name="scripture" maxlength="150" placeholder="e.g. Philippians 4:19"></div>
                <div class="field"><label class="label" for="ne-date">Date</label><input class="input" id="ne-date" type="date" name="entry_date" value="<?= date('Y-m-d') ?>"></div>
            </div>
        </div>
        <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('save') ?>Save entry</button></div>
    </form>
</dialog>
