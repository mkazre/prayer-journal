<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/blog?archive=' . $archive['id']) ?>">Blog · <?= esc($archive['title']) ?></a><?= icon('chevron-right') ?><span><?= $post['id'] ? esc($post['title']) : 'New post' ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$p      = $post;
$v      = static fn ($k) => esc((string) old($k, $p[$k] ?? ''), 'attr');
$access = (string) old('access', $p['access']);
$kindIcon = ['pdf' => 'file-text', 'image' => 'image', 'video' => 'video', 'audio' => 'headphones', 'file' => 'paperclip'];
$archiveLevel = \App\Modules\Blog\Services\BlogAccess::LEVELS[$archive['access']] ?? $archive['access'];
?>
<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="studio-head">
        <div><h1><?= $p['id'] ? esc($p['title']) : 'New post' ?></h1><?php if ($p['id']) : ?><p><a href="<?= site_url('blog/' . $archive['slug'] . '/' . $p['slug']) ?>" target="_blank" rel="noopener">View post <?= icon('external') ?></a></p><?php endif ?></div>
        <div class="row">
            <?php if ($p['id']) : ?><button class="btn btn-ghost" type="submit" form="del-form"><?= icon('trash') ?>Delete</button><?php endif ?>
            <button class="btn btn-primary" type="submit"><?= icon('save') ?>Save</button>
        </div>
    </div>
    <div class="split">
        <div class="stack stack-lg">
            <section class="card card-body">
                <div class="field"><label class="label" for="title">Title</label><input class="input input-lg" id="title" name="title" value="<?= $v('title') ?>" required maxlength="200" placeholder="e.g. Kilometre 1: Starting well"></div>
                <div class="field"><label class="label" for="excerpt">Introduction <span class="opt">(shown to everyone, and on the archive page)</span></label><textarea class="textarea" id="excerpt" name="excerpt" rows="2" maxlength="500"><?= esc((string) old('excerpt', $p['excerpt'] ?? '')) ?></textarea></div>
                <div class="field"><label class="label">Text</label><textarea name="body" data-editor placeholder="Write the post…"><?= esc((string) old('body', $p['body'] ?? '')) ?></textarea></div>
            </section>

            <section class="card card-body">
                <div class="row between mb-1"><h3 class="mb-0"><?= icon('download') ?> Free resources</h3><span class="text-xs text-muted">Only readers with access can open or download these</span></div>
                <?php if ($resources) : ?>
                    <div class="table-wrap mb-2"><table class="table table-compact">
                        <thead><tr><th>Title shown to readers</th><th>Type</th><th class="num">Size</th><th style="width:80px">Order</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($resources as $r) : ?>
                                <tr>
                                    <td><input class="input input-sm" name="resource_title[<?= $r['id'] ?>]" value="<?= esc($r['title'], 'attr') ?>" maxlength="200"></td>
                                    <td class="text-sm nowrap"><?= icon($kindIcon[$r['kind']] ?? 'file') ?> <?= esc(strtoupper(pathinfo($r['file_path'], PATHINFO_EXTENSION))) ?></td>
                                    <td class="num text-sm nowrap"><?= number_format($r['size_bytes'] / 1048576, 1) ?> MB</td>
                                    <td><input class="input input-sm" type="number" name="resource_position[<?= $r['id'] ?>]" value="<?= (int) $r['position'] ?>"></td>
                                    <td class="text-right"><button class="btn btn-ghost btn-icon btn-sm" type="button" data-del-resource="<?= site_url('studio/blog/resources/' . $r['id'] . '/delete') ?>" aria-label="Remove"><?= icon('trash') ?></button></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table></div>
                <?php endif ?>
                <input class="sr-only" type="file" id="resources" name="resources[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.mp4,.mp3,.m4a,.zip,.epub">
                <div class="dropzone" data-for="resources"><?= icon('upload') ?><span data-file-label data-default="Drop files or click to upload">Drop files or click to upload</span><span class="text-xs">PDF, images, MP4 video, MP3/M4A audio, ZIP or EPUB · up to 200 MB each (your hosting may set a lower limit)</span></div>
            </section>
        </div>

        <aside class="stack stack-lg sticky">
            <section class="card card-body stack">
                <div class="field"><span class="label">Status</span><div class="segmented" style="width:100%"><label class="grow"><input type="radio" name="status" value="published" <?= old('status', $p['status']) === 'published' ? 'checked' : '' ?>><span style="justify-content:center">Published</span></label><label class="grow"><input type="radio" name="status" value="draft" <?= old('status', $p['status']) === 'draft' ? 'checked' : '' ?>><span style="justify-content:center">Draft</span></label></div></div>
                <div class="field"><label class="label" for="published_at">Publish date</label><input class="input" type="datetime-local" id="published_at" name="published_at" value="<?= $p['published_at'] ? date('Y-m-d\TH:i', strtotime($p['published_at'])) : '' ?>"><span class="help">Leave empty to publish now. A future date schedules the post.</span></div>
                <div class="field"><label class="label" for="archive_id">Archive</label><select class="select" id="archive_id" name="archive_id"><?php foreach ($archives as $a) : ?><option value="<?= $a['id'] ?>" <?= (int) $a['id'] === (int) $archive['id'] ? 'selected' : '' ?>><?= esc($a['title']) ?></option><?php endforeach ?></select></div>
            </section>
            <section class="card card-body stack">
                <h3 class="mb-0"><?= icon('lock') ?> Access</h3>
                <label class="check"><input type="radio" name="access" value="inherit" <?= $access === 'inherit' ? 'checked' : '' ?>>Same as archive <span class="text-muted">(<?= esc($archiveLevel) ?>)</span></label>
                <label class="check"><input type="radio" name="access" value="members" <?= $access === 'members' ? 'checked' : '' ?>>Signed-in members</label>
                <label class="check"><input type="radio" name="access" value="customers" <?= $access === 'customers' ? 'checked' : '' ?>>Customers only — bought a product, course or membership</label>
            </section>
            <section class="card card-body">
                <h3>Featured image</h3>
                <div class="course-cover mb-2" id="img-preview" style="border-radius:12px;<?= cover_gradient($p['title'] ?: 'post') ?>">
                    <?php if ($p['featured_image']) : ?><img src="<?= base_url($p['featured_image']) ?>" alt=""><?php else : ?><img alt="" hidden><?php endif ?>
                </div>
                <input class="sr-only" type="file" id="featured_image" name="featured_image" accept="image/*">
                <div class="dropzone" data-for="featured_image" data-preview="img-preview"><?= icon('upload') ?><span data-file-label data-default="Drop an image or click to upload">Drop an image or click to upload</span><span class="text-xs">16:9 works best · max 8 MB</span></div>
                <?php if ($p['featured_image']) : ?><label class="check mt-1 text-sm"><input type="checkbox" name="remove_image" value="1">Remove image</label><?php endif ?>
            </section>
            <button class="btn btn-primary btn-lg btn-block" type="submit"><?= icon('save') ?>Save post</button>
        </aside>
    </div>
</form>
<?php if ($p['id']) : ?>
    <form id="del-form" method="post" action="<?= site_url('studio/blog/posts/' . $p['id'] . '/delete') ?>" data-confirm="Delete this post and its files? This cannot be undone."><?= csrf_field() ?></form>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('[data-del-resource]').forEach(function (b) {
    b.addEventListener('click', function () {
        if (!confirm('Remove this file? Readers will no longer be able to download it.')) { return; }
        PJ.post(b.getAttribute('data-del-resource'), {}).then(function () { b.closest('tr').remove(); PJ.toast('Resource removed'); });
    });
});
</script>
<?= $this->endSection() ?>
