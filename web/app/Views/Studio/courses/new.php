<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><span>New</span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div style="max-width:640px;margin:2rem auto">
    <div class="text-center mb-3"><div class="feature-icon" style="margin:0 auto 1rem"><?= icon('graduation', 'icon-lg') ?></div><h1 style="font-family:var(--font-display)">Create a course</h1><p class="text-muted">Start with a title. You'll add modules, lessons, quizzes and settings next.</p></div>
    <form class="card card-body" method="post">
        <?= csrf_field() ?>
        <div class="field"><label class="label" for="title">Course title</label><input class="input input-lg" id="title" name="title" value="<?= esc((string) old('title'), 'attr') ?>" required minlength="3" maxlength="200" placeholder="e.g. Foundations of Prayer" autofocus></div>
        <div class="field"><label class="label" for="summary">One-line summary <span class="opt">(optional)</span></label><input class="input" id="summary" name="summary" maxlength="500" value="<?= esc((string) old('summary'), 'attr') ?>" placeholder="What will learners gain?"></div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Create course <?= icon('arrow-right') ?></button>
    </form>
</div>
<?= $this->endSection() ?>
