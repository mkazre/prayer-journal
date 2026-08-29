<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="pj-card">
    <h1>Welcome, <?= esc($user->username ?? $user->email) ?></h1>
    <p>Your roles: <?= esc(implode(', ', $roles) ?: 'student') ?></p>

    <?php if (in_array('instructor', $roles, true) || in_array('admin', $roles, true) || in_array('superadmin', $roles, true)) : ?>
        <p>Instructor tools (course authoring, grading) will appear here.</p>
    <?php endif ?>

    <?php if (in_array('vendor', $roles, true) || in_array('admin', $roles, true) || in_array('superadmin', $roles, true)) : ?>
        <p>Store tools (products, orders) will appear here.</p>
    <?php endif ?>

    <p>Your enrolled courses will appear here once the LMS module is built.</p>
</div>
<?= $this->endSection() ?>
