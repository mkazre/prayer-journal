<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
use App\Modules\Core\Models\PrayerRequestModel;

$me   = auth()->loggedIn() ? (int) auth()->id() : 0;
$link = static fn (array $o) => site_url('prayer-requests') . '?' . http_build_query(array_filter(array_merge(['category' => $filters['category'], 'status' => $filters['status'], 'mine' => $filters['mine'] ? 1 : null], $o)));
?>
<div class="container">
    <div class="page-head row between items-end">
        <div>
            <span class="eyebrow"><?= icon('hands') ?>Community</span>
            <h1>Prayer Wall</h1>
            <p class="mb-1" style="font-family:var(--font-display);font-style:italic;font-size:1.15rem">“Carry each other’s burdens.” — <?= scripture_links('Galatians 6:2') ?></p>
            <p class="mb-1" style="max-width:640px">A place where faith meets compassion. Share your prayer requests, stand in prayer with others and experience the power of a community united in faith.</p>
            <p class="fw-600 mb-0">Share a prayer. Lift someone up. Stand together in faith.</p>
        </div>
        <div class="row gap-lg text-center">
            <div><div class="stat-value"><?= number_format($stats['requests']) ?></div><div class="text-xs text-muted">requests</div></div>
            <div><div class="stat-value"><?= number_format($stats['prayers']) ?></div><div class="text-xs text-muted">prayers prayed</div></div>
            <div><div class="stat-value text-success"><?= number_format($stats['answered']) ?></div><div class="text-xs text-muted">answered</div></div>
        </div>
    </div>

    <div class="split split-reverse">
        <div class="stack">
            <div class="toolbar">
                <div class="chips">
                    <a class="chip <?= ! $filters['category'] && ! $filters['status'] && ! $filters['mine'] ? 'is-active' : '' ?>" href="<?= site_url('prayer-requests') ?>">All</a>
                    <a class="chip <?= $filters['status'] === 'answered' ? 'is-active' : '' ?>" href="<?= $link(['status' => $filters['status'] === 'answered' ? null : 'answered']) ?>"><?= icon('check-circle') ?>Answered</a>
                    <?php if ($me) : ?><a class="chip <?= $filters['mine'] ? 'is-active' : '' ?>" href="<?= $link(['mine' => $filters['mine'] ? null : 1]) ?>"><?= icon('user') ?>Mine</a><?php endif ?>
                    <?php foreach (PrayerRequestModel::CATEGORIES as $k => $l) : ?><a class="chip <?= $filters['category'] === $k ? 'is-active' : '' ?>" href="<?= $link(['category' => $filters['category'] === $k ? null : $k]) ?>"><?= esc($l) ?></a><?php endforeach ?>
                </div>
            </div>

            <?php if ($requests === []) : ?>
                <div class="card empty"><div class="feature-icon"><?= icon('hands', 'icon-lg') ?></div><h3>No requests here yet</h3><p>Be the first to share — the community will pray with you.</p></div>
            <?php endif ?>

            <?php foreach ($requests as $r) : $own = $me && (int) $r['user_id'] === $me; $name = $r['is_anonymous'] ? 'Anonymous' : display_name(['id' => $r['user_id'], 'username' => $r['username']]); ?>
                <article class="card card-body prayer-card <?= $r['is_answered'] ? 'is-answered' : '' ?>" id="request-<?= $r['id'] ?>">
                    <div class="row row-nowrap between items-start">
                        <div class="row row-nowrap">
                            <?= $r['is_anonymous'] ? '<span class="avatar avatar-sm">' . icon('user') . '</span>' : avatar((int) $r['user_id'], $name, 'avatar-sm') ?>
                            <div><div class="fw-600 text-sm"><?= esc($name) ?><?= $own ? ' <span class="text-muted">(you)</span>' : '' ?></div><div class="text-xs text-muted"><?= time_ago($r['created_at']) ?> · <?= esc(PrayerRequestModel::CATEGORIES[$r['category'] ?? 'general'] ?? 'General') ?></div></div>
                        </div>
                        <?php if ($r['is_answered']) : ?><span class="badge badge-success"><?= icon('check-circle') ?>Answered</span><?php endif ?>
                    </div>
                    <p class="body mb-0"><?= esc($r['body']) ?></p>
                    <?php if ($r['is_answered'] && $r['answer_note']) : ?>
                        <blockquote class="testimony"><?= icon('quote') ?><span><?= esc($r['answer_note']) ?></span></blockquote>
                    <?php endif ?>
                    <div class="row between">
                        <?php if ($me) : ?>
                            <form method="post" action="<?= site_url('prayer-requests/' . $r['id'] . '/pray') ?>" data-ajax class="pray-form">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm <?= ! empty($r['i_prayed']) ? 'btn-soft' : '' ?>" type="submit" <?= ! empty($r['i_prayed']) ? 'disabled' : '' ?>><?= icon('hands') ?><span><?= ! empty($r['i_prayed']) ? 'You prayed' : 'I prayed' ?></span></button>
                            </form>
                        <?php else : ?>
                            <a class="btn btn-sm" href="<?= site_url('login') ?>"><?= icon('hands') ?>Sign in to pray</a>
                        <?php endif ?>
                        <span class="text-sm text-muted"><span data-count><?= (int) $r['prayer_count'] ?></span> <?= (int) $r['prayer_count'] === 1 ? 'person has' : 'people have' ?> prayed</span>
                    </div>
                    <?php if ($own || is_staff('superadmin', 'admin', 'manager')) : ?>
                        <div class="row gap-xs pt-2" style="border-top:1px solid var(--border)">
                            <?php if ($own) : ?>
                                <?php if ($r['is_answered']) : ?>
                                    <form method="post" action="<?= site_url('prayer-requests/' . $r['id'] . '/answered') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit"><?= icon('refresh') ?>Still praying</button></form>
                                <?php else : ?>
                                    <button class="btn btn-sm btn-ghost" type="button" data-modal-open="answer-<?= $r['id'] ?>"><?= icon('check-circle') ?>Mark answered</button>
                                    <dialog class="modal" id="answer-<?= $r['id'] ?>">
                                        <form method="post" action="<?= site_url('prayer-requests/' . $r['id'] . '/answered') ?>">
                                            <?= csrf_field() ?>
                                            <div class="modal-head"><h3>Share how God answered</h3><button class="btn btn-ghost btn-icon btn-sm" type="button" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
                                            <div class="modal-body"><div class="field"><label class="label">Your testimony <span class="opt">(optional)</span></label><textarea class="textarea" name="answer_note" rows="4" maxlength="1000" placeholder="A few words to encourage others…"></textarea></div></div>
                                            <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('check') ?>Mark answered</button></div>
                                        </form>
                                    </dialog>
                                <?php endif ?>
                            <?php endif ?>
                            <form method="post" action="<?= site_url('prayer-requests/' . $r['id'] . '/delete') ?>" class="ml-auto"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit" data-confirm="Remove this request?"><?= icon('trash') ?>Remove</button></form>
                        </div>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
        </div>

        <aside class="stack">
            <?php if ($me) : ?>
                <form class="card card-body" method="post" action="<?= site_url('prayer-requests/create') ?>">
                    <?= csrf_field() ?>
                    <h2 class="h-sm"><?= icon('feather') ?> Share a request</h2>
                    <div class="field"><textarea class="textarea" name="body" rows="5" maxlength="2000" required placeholder="What would you like the community to pray about?"></textarea></div>
                    <div class="field"><label class="label" for="pr-category">Category</label><select class="select" id="pr-category" name="category"><?php foreach (PrayerRequestModel::CATEGORIES as $k => $l) : ?><option value="<?= $k ?>"><?= esc($l) ?></option><?php endforeach ?></select></div>
                    <label class="check text-sm mb-2"><input type="checkbox" name="is_anonymous" value="1"><span>Post anonymously — your name is hidden from everyone.</span></label>
                    <button class="btn btn-primary btn-block" type="submit"><?= icon('send') ?>Share request</button>
                </form>
            <?php else : ?>
                <div class="card card-body text-center">
                    <div class="feature-icon center"><?= icon('hands', 'icon-lg') ?></div>
                    <h3>Join the prayer wall</h3>
                    <p class="text-sm text-muted">Create a free account to share requests and let others know you prayed.</p>
                    <a class="btn btn-primary btn-block" href="<?= site_url('register') ?>">Create free account</a>
                    <a class="btn btn-ghost btn-block mt-1" href="<?= site_url('login') ?>">Sign in</a>
                </div>
            <?php endif ?>
            <div class="card card-body text-sm">
                <h3 class="h-sm"><?= icon('shield') ?> Prayer Wall Guidelines</h3>
                <ul class="list-check">
                    <li><strong>Respect privacy:</strong> Protect others' personal information.</li>
                    <li><strong>Pray with love:</strong> Encourage and uplift without judging, correcting or debating.</li>
                    <li><strong>Share testimonies:</strong> Mark answered prayers and celebrate God's faithfulness.</li>
                </ul>
                <p class="fw-600 mb-0" style="color:var(--accent-ink)">Together in prayer, united in faith, strengthened by love.</p>
            </div>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.addEventListener('pj:success', function (e) {
    var form = e.target.closest && e.target.closest('.pray-form');
    if (!form) { return; }
    var card = form.closest('.prayer-card'), btn = form.querySelector('button');
    if (e.detail && typeof e.detail.count === 'number') { card.querySelector('[data-count]').textContent = e.detail.count; }
    btn.disabled = true; btn.classList.add('btn-soft'); btn.querySelector('span').textContent = 'You prayed';
});
</script>
<?= $this->endSection() ?>
