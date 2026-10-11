<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($title) ?></h1>
    <form method="post">
        <?= csrf_field() ?>
        <p><label>Title<br><input type="text" name="title" required></label></p>
        <p><label>Content<br><textarea name="content" rows="6"></textarea></label></p>
        <p><label>Video URL<br><input type="text" name="video_url"></label></p>
        <p><label>Bible Reference (e.g. John 3:16)<br><input type="text" name="bible_reference"></label></p>
        <p><label>Position<br><input type="number" name="position" value="0"></label></p>
        <button class="pj-btn" type="submit">Add Lesson</button>
    </form>
</div>
<?= $this->endSection() ?>
