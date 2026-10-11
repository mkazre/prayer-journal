<div class="player-top no-print">
    <button class="btn btn-ghost btn-icon btn-sm" type="button" data-toggle-class="player-collapsed" data-remember="player-collapsed" aria-label="Toggle contents" title="Toggle contents"><?= icon('sidebar') ?></button>
    <button class="btn btn-ghost btn-sm" type="button" data-drawer-open="player-side" style="display:none" id="contents-btn"><?= icon('list') ?>Contents</button>
    <div class="breadcrumbs mb-0 grow truncate">
        <a href="<?= site_url('study/' . $course['slug']) ?>"><?= esc($course['title']) ?></a>
        <?php if (! empty($current['section_title'])) : ?><?= icon('chevron-right') ?><span class="truncate"><?= esc($current['section_title']) ?></span><?php endif ?>
    </div>
    <?= $topActions ?? '' ?>
</div>
<style>@media (max-width: 960px) { #contents-btn { display: inline-flex !important; } [data-toggle-class="player-collapsed"] { display: none; } }</style>
<script>try { if (localStorage.getItem('pj-player-collapsed') === '1') { document.body.classList.add('player-collapsed'); } } catch (e) {}</script>
