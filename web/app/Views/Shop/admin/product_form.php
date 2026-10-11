<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="pj-card">
    <h1>New Product</h1>
    <form method="post">
        <?= csrf_field() ?>
        <p><label>Title<br><input type="text" name="title" required></label></p>
        <p><label>Description<br><textarea name="description" rows="4"></textarea></label></p>
        <p><label>Price (USD)<br><input type="number" step="0.01" name="price" required></label></p>
        <p><label>Initial Stock<br><input type="number" name="stock" value="0"></label></p>
        <button class="pj-btn" type="submit">Create</button>
    </form>
</div>
<?= $this->endSection() ?>
