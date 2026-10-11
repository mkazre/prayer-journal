<?= $this->extend('layouts/studio') ?>
<?= $this->section('crumbs') ?><a href="<?= site_url('studio/courses') ?>">Courses</a><?= icon('chevron-right') ?><span><?= esc($course['title']) ?></span><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Studio/courses/_header', ['course' => $course, 'tab' => $tab]) ?>
<?php $c = $course; $v = static fn ($k) => esc((string) old($k, $c[$k] ?? ''), 'attr'); ?>
<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="split">
        <div class="stack stack-lg">
            <section class="card card-body">
                <h3>Basics</h3>
                <div class="field"><label class="label" for="title">Title</label><input class="input input-lg" id="title" name="title" value="<?= $v('title') ?>" required maxlength="200"></div>
                <div class="field"><label class="label" for="slug">URL</label><div class="input-group"><span class="input-addon"><?= esc(site_url('courses/')) ?></span><input class="input" id="slug" name="slug" value="<?= $v('slug') ?>" data-slug-from="#title"></div></div>
                <div class="field"><label class="label" for="subtitle">Subtitle</label><input class="input" id="subtitle" name="subtitle" value="<?= $v('subtitle') ?>" maxlength="255" placeholder="A short promise shown under the title"></div>
                <div class="field"><label class="label" for="summary">Summary</label><textarea class="textarea" id="summary" name="summary" rows="2" maxlength="500" data-count="summary-count"><?= esc((string) old('summary', $c['summary'] ?? '')) ?></textarea><span class="help" id="summary-count"></span></div>
                <div class="field"><label class="label">Description</label><textarea name="description" data-editor placeholder="Describe the course — what it covers and how it works."><?= esc((string) old('description', $c['description'] ?? '')) ?></textarea></div>
            </section>
            <section class="card card-body">
                <h3>Landing page lists</h3>
                <p class="help mb-2">One item per line.</p>
                <div class="field"><label class="label" for="wyl">What learners will learn</label><textarea class="textarea" id="wyl" name="what_you_learn" rows="5"><?= esc((string) old('what_you_learn', $c['what_you_learn'] ?? '')) ?></textarea></div>
                <div class="form-grid">
                    <div class="field"><label class="label" for="req">Requirements</label><textarea class="textarea" id="req" name="requirements" rows="4"><?= esc((string) old('requirements', $c['requirements'] ?? '')) ?></textarea></div>
                    <div class="field"><label class="label" for="aud">Who it's for</label><textarea class="textarea" id="aud" name="audience" rows="4"><?= esc((string) old('audience', $c['audience'] ?? '')) ?></textarea></div>
                </div>
            </section>
            <section class="card card-body">
                <h3>Author</h3>
                <div class="field"><label class="label" for="author_name">Author name</label><input class="input" id="author_name" name="author_name" value="<?= $v('author_name') ?>" maxlength="150"></div>
                <div class="field"><label class="label" for="author_bio">Author bio</label><textarea class="textarea" id="author_bio" name="author_bio" rows="3"><?= esc((string) old('author_bio', $c['author_bio'] ?? '')) ?></textarea></div>
            </section>
        </div>
        <aside class="stack stack-lg">
            <section class="card card-body">
                <h3>Cover image</h3>
                <div class="course-cover mb-2" id="cover-preview" style="border-radius:12px;<?= course_cover_style($c) ?>">
                    <?php if ($c['thumbnail_path']) : ?><img src="<?= base_url($c['thumbnail_path']) ?>" alt=""><?php else : ?><img alt="" hidden><div class="cover-art"><span><?= esc($c['title']) ?></span></div><?php endif ?>
                </div>
                <input class="sr-only" type="file" id="cover" name="cover" accept="image/*">
                <div class="dropzone" data-for="cover" data-preview="cover-preview"><?= icon('upload') ?><span data-file-label data-default="Drop an image or click to upload">Drop an image or click to upload</span><span class="text-xs">16:9 works best · max 5 MB</span></div>
                <?php if ($c['thumbnail_path']) : ?><label class="check mt-1 text-sm"><input type="checkbox" name="remove_cover" value="1">Remove cover</label><?php endif ?>
            </section>
            <section class="card card-body">
                <h3>Pricing &amp; access</h3>
                <div class="field"><span class="label">Access</span>
                    <div class="segmented" style="width:100%">
                        <?php foreach (['free' => 'Free', 'paid' => 'One-time price', 'subscription' => 'Membership'] as $k => $l) : ?><label class="grow"><input type="radio" name="access_mode" value="<?= $k ?>" <?= ($c['access_mode'] ?? 'free') === $k ? 'checked' : '' ?>><span style="justify-content:center"><?= $l ?></span></label><?php endforeach ?>
                    </div>
                </div>
                <div class="field"><label class="label" for="price">Price (<?= esc(store_currency()) ?>)</label><input class="input" id="price" name="price" type="number" min="0" step="0.01" value="<?= number_format(((int) $c['price_cents']) / 100, 2, '.', '') ?>"><span class="help">Used when access is "One-time price".</span></div>
            </section>
            <section class="card card-body">
                <h3>Organisation</h3>
                <div class="field"><label class="label" for="category_id">Category</label>
                    <select class="select" id="category_id" name="category_id"><option value="">None</option><?php foreach ($categories as $cat) : ?><option value="<?= $cat['id'] ?>" <?= (int) $c['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['name']) ?></option><?php endforeach ?></select>
                    <input class="input input-sm mt-1" name="new_category" placeholder="…or create a new category"></div>
                <div class="form-grid">
                    <div class="field"><label class="label" for="level">Level</label><select class="select" id="level" name="level"><?php foreach (\App\Modules\Lms\Models\CourseModel::LEVELS as $k => $l) : ?><option value="<?= $k ?>" <?= $c['level'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></div>
                    <div class="field"><label class="label" for="language">Language</label><input class="input" id="language" name="language" value="<?= $v('language') ?>"></div>
                </div>
                <div class="field"><label class="label" for="tags">Tags</label><input class="input" id="tags" name="tags" value="<?= $v('tags') ?>" placeholder="prayer, devotional"></div>
                <div class="field"><label class="label" for="intro">Intro video URL</label><input class="input" id="intro" name="intro_video_url" value="<?= $v('intro_video_url') ?>" placeholder="YouTube or Vimeo link"></div>
                <?php if (is_staff('superadmin', 'admin', 'manager')) : ?><label class="switch"><input type="checkbox" name="is_featured" value="1" <?= $c['is_featured'] ? 'checked' : '' ?>><span class="track"></span>Feature on the home page</label><?php endif ?>
            </section>
            <section class="card card-body text-sm">
                <div class="summary-row"><span>Lessons</span><strong><?= (int) $stats['lesson_count'] ?></strong></div>
                <div class="summary-row"><span>Learners</span><strong><?= (int) $stats['student_count'] ?></strong></div>
                <div class="summary-row"><span>Rating</span><strong><?= $stats['avg_rating'] ? number_format((float) $stats['avg_rating'], 1) : '—' ?></strong></div>
            </section>
            <button class="btn btn-primary btn-lg btn-block" type="submit"><?= icon('save') ?>Save details</button>
        </aside>
    </div>
</form>
<?= $this->endSection() ?>
