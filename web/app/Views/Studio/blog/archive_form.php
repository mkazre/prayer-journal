<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/blog') ?>">Blog</a><?= icon('chevron-right') ?><span><?= $archive['id'] ? esc($archive['title']) : 'New archive' ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$a = $archive;
$v = static fn ($k) => esc((string) old($k, $a[$k] ?? ''), 'attr');
$access = (string) old('access', $a['access']);
?>
<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="studio-head">
        <div><h1><?= $a['id'] ? esc($a['title']) : 'New archive' ?></h1><p>An archive is a series of posts, such as Prayerthon. It gets its own page listing every post.</p></div>
        <div class="row">
            <?php if ($a['id']) : ?><button class="btn btn-ghost" type="submit" form="del-form"><?= icon('trash') ?>Delete</button><?php endif ?>
            <button class="btn btn-primary" type="submit"><?= icon('save') ?>Save</button>
        </div>
    </div>
    <div class="split">
        <div class="stack stack-lg">
            <section class="card card-body">
                <div class="field"><label class="label" for="title">Title</label><input class="input input-lg" id="title" name="title" value="<?= $v('title') ?>" required maxlength="150" placeholder="e.g. Prayerthon"></div>
                <div class="field"><label class="label" for="description">Description</label><textarea class="textarea" id="description" name="description" rows="3" placeholder="Shown at the top of the archive page."><?= esc((string) old('description', $a['description'] ?? '')) ?></textarea></div>
                <div class="field"><label class="label" for="slug">Web address</label><div class="input-group"><span class="input-addon"><?= esc(site_url('blog/')) ?></span><input class="input" id="slug" name="slug" value="<?= $v('slug') ?>" data-slug-from="#title"></div>
                    <?php if ($a['slug'] === 'prayerthon') : ?><p class="help">The home-page Prayerthon block links to this address — keep it as <code>prayerthon</code>.</p><?php endif ?></div>
            </section>
            <section class="card card-body">
                <h3><?= icon('lock') ?> Who can read the posts</h3>
                <p class="help mb-2">Anyone can see post titles, images and introductions. The full text and the downloads need this level. Individual posts can override it.</p>
                <?php foreach (\App\Modules\Blog\Services\BlogAccess::LEVELS as $k => $label) : ?>
                    <label class="radio-card mb-1"><input type="radio" name="access" value="<?= $k ?>" <?= $access === $k ? 'checked' : '' ?>><span><strong><?= esc($label) ?></strong><br><span class="text-sm text-muted"><?= $k === 'members' ? 'Free for anyone with an account.' : 'Free for people who have bought a product, a course or a membership. Others are invited to upgrade.' ?></span></span></label>
                <?php endforeach ?>
            </section>
        </div>
        <aside class="stack stack-lg sticky">
            <section class="card card-body stack">
                <div class="field"><span class="label">Status</span><div class="segmented" style="width:100%"><label class="grow"><input type="radio" name="status" value="published" <?= old('status', $a['status']) === 'published' ? 'checked' : '' ?>><span style="justify-content:center">Published</span></label><label class="grow"><input type="radio" name="status" value="draft" <?= old('status', $a['status']) === 'draft' ? 'checked' : '' ?>><span style="justify-content:center">Draft</span></label></div></div>
                <label class="switch"><input type="checkbox" name="show_in_menu" value="1" <?= old('show_in_menu', $a['show_in_menu']) ? 'checked' : '' ?>><span class="track"></span>Show in top &amp; bottom menus</label>
                <div class="field"><label class="label" for="position">Menu order</label><input class="input" type="number" id="position" name="position" value="<?= (int) old('position', $a['position']) ?>"></div>
            </section>
            <section class="card card-body">
                <h3>Cover image</h3>
                <div class="course-cover mb-2" id="cover-preview" style="border-radius:12px;<?= cover_gradient($a['title'] ?: 'blog') ?>">
                    <?php if ($a['cover_path']) : ?><img src="<?= base_url($a['cover_path']) ?>" alt=""><?php else : ?><img alt="" hidden><?php endif ?>
                </div>
                <input class="sr-only" type="file" id="cover" name="cover" accept="image/*">
                <div class="dropzone" data-for="cover" data-preview="cover-preview"><?= icon('upload') ?><span data-file-label data-default="Drop an image or click to upload">Drop an image or click to upload</span><span class="text-xs">16:9 works best · max 8 MB</span></div>
                <?php if ($a['cover_path']) : ?><label class="check mt-1 text-sm"><input type="checkbox" name="remove_cover" value="1">Remove cover</label><?php endif ?>
            </section>
        </aside>
    </div>
</form>
<?php if ($a['id']) : ?>
    <form id="del-form" method="post" action="<?= site_url('studio/blog/archives/' . $a['id'] . '/delete') ?>" data-confirm="Delete this archive and ALL its posts and files? This cannot be undone."><?= csrf_field() ?></form>
<?php endif ?>
<?= $this->endSection() ?>
