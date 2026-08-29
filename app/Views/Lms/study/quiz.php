<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($quiz['title']) ?></h1>
    <p><?= esc($quiz['instructions']) ?></p>
    <form method="post">
        <?= csrf_field() ?>
        <?php foreach ($quiz['questions'] as $q) : ?>
            <div class="pj-card" style="margin-bottom:1rem">
                <p><strong><?= esc($q['question']) ?></strong></p>
                <?php foreach ($q['options'] as $o) : ?>
                    <p><label><input type="radio" name="answer[<?= $q['id'] ?>]" value="<?= $o['id'] ?>"> <?= esc($o['option_text']) ?></label></p>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>
        <button class="pj-btn pj-btn-gold" type="submit">Submit Quiz</button>
    </form>
</div>
<?= $this->endSection() ?>
