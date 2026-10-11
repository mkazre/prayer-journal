<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>🙏 Prayer Requests</h1>

<?php if (auth()->loggedIn()) : ?>
<div class="pj-card" style="margin-bottom:1.5rem">
    <form method="post" action="<?= site_url('prayer-requests/create') ?>">
        <?= csrf_field() ?>
        <textarea name="body" rows="3" placeholder="Share what's on your heart..." required></textarea>
        <p><label><input type="checkbox" name="is_anonymous" value="1"> Post anonymously</label></p>
        <button class="pj-btn pj-btn-gold" type="submit">Share Request</button>
    </form>
</div>
<?php endif ?>

<?php foreach ($requests as $r) : ?>
    <div class="pj-card" style="margin-bottom:1rem">
        <p><?= nl2br(esc($r['body'])) ?></p>
        <p style="color:var(--pj-gray);font-size:.85rem">— <?= $r['is_anonymous'] ? 'Anonymous' : esc($r['username']) ?></p>
        <?php if (auth()->loggedIn()) : ?>
            <form method="post" action="<?= site_url('prayer-requests/' . $r['id'] . '/pray') ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="pj-btn" type="submit">🙏 I'm praying (<?= esc($r['prayer_count']) ?>)</button>
            </form>
        <?php else : ?>
            <span class="pj-badge">🙏 <?= esc($r['prayer_count']) ?> praying</span>
        <?php endif ?>
    </div>
<?php endforeach ?>
<?= $this->endSection() ?>
