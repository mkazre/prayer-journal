<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1><?= esc($title) ?></h1>
    <?php if (! empty($errors)) : ?>
        <p style="color:crimson"><?= implode('<br>', array_map('esc', $errors)) ?></p>
    <?php endif ?>
    <form method="post">
        <?= csrf_field() ?>
        <p><label>Title<br><input type="text" name="title" value="<?= esc($course['title'] ?? '') ?>" required></label></p>
        <p><label>Summary<br><input type="text" name="summary" value="<?= esc($course['summary'] ?? '') ?>"></label></p>
        <p><label>Description<br><textarea name="description" rows="4"><?= esc($course['description'] ?? '') ?></textarea></label></p>
        <p><label>Price (USD, 0 = free)<br><input type="number" step="0.01" name="price" value="<?= isset($course['price_cents']) ? number_format($course['price_cents'] / 100, 2, '.', '') : '0.00' ?>"></label></p>
        <?php if ($course) : ?>
            <p><label>Status<br>
                <select name="status">
                    <option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </label></p>
        <?php endif ?>
        <button class="pj-btn" type="submit">Save</button>
    </form>

    <?php if ($course && isset($course['id'])) : ?>
        <hr>
        <h2>Lessons</h2>
        <ul>
            <?php foreach ($course['lessons'] ?? [] as $l) : ?>
                <li><?= esc($l['title']) ?></li>
            <?php endforeach ?>
        </ul>
        <a class="pj-btn" href="<?= site_url('instructor/courses/' . $course['id'] . '/lessons/create') ?>">+ Add Lesson</a>

        <h2>Quizzes</h2>
        <ul>
            <?php foreach ($course['quizzes'] ?? [] as $q) : ?>
                <li><?= esc($q['title']) ?></li>
            <?php endforeach ?>
        </ul>
        <a class="pj-btn" href="<?= site_url('instructor/courses/' . $course['id'] . '/quizzes/create') ?>">+ Add Quiz</a>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
