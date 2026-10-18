<footer class="site-footer no-print">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="brand mb-2" href="<?= site_url('/') ?>"><img src="<?= logo_url() ?>" alt="<?= esc(site_name()) ?> logo" width="40" height="40"><span><?= esc(site_name()) ?></span></a>
                <p class="fw-700 mt-2 mb-1" style="font-family:var(--font-display);font-size:1.15rem">Your Home for a Deeper Walk with God</p>
                <p class="text-2 mb-1" style="max-width:340px"><?= esc(site_setting('footer_blurb', 'A home for deeper prayer: guided courses, the whole Bible, a private prayer journal and resources for every season of faith.')) ?></p>
                <p class="fw-600 mb-0" style="max-width:340px;color:var(--accent-ink)">One place to pray, study, reflect and grow closer to God.</p>
                <div class="row gap-sm mt-2">
                    <?php if ($email = site_setting('contact_email')) : ?><a class="btn btn-sm btn-ghost" href="mailto:<?= esc($email, 'attr') ?>"><?= icon('mail') ?><?= esc($email) ?></a><?php endif ?>
                </div>
            </div>
            <div>
                <h4>Learn</h4>
                <ul>
                    <li><a href="<?= site_url('courses') ?>">All courses</a></li>
                    <li><a href="<?= site_url('my-learning') ?>">My learning</a></li>
                    <li><a href="<?= site_url('journal') ?>">Prayer journal</a></li>
                    <li><a href="<?= site_url('pricing') ?>">Membership</a></li>
                    <?php foreach (\App\Modules\Blog\Models\BlogArchiveModel::menu() as $a) : ?>
                        <li><a href="<?= site_url('blog/' . $a['slug']) ?>"><?= esc($a['title']) ?></a></li>
                    <?php endforeach ?>
                </ul>
            </div>
            <div>
                <h4>Scripture</h4>
                <ul>
                    <li><a href="<?= site_url('bible') ?>">Read the Bible</a></li>
                    <li><a href="<?= site_url('bible/plans') ?>">Reading plans</a></li>
                    <li><a href="<?= site_url('bible/search') ?>">Search scripture</a></li>
                    <li><a href="<?= site_url('prayer-requests') ?>">Prayer wall</a></li>
                </ul>
            </div>
            <div>
                <h4>Shop</h4>
                <ul>
                    <li><a href="<?= site_url('shop') ?>">All products</a></li>
                    <li><a href="<?= site_url('cart') ?>">Cart</a></li>
                    <li><a href="<?= site_url('my-orders') ?>">Track an order</a></li>
                    <li><a href="<?= site_url('wishlist') ?>">Wishlist</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= esc(site_setting('company_name', site_name())) ?>. All rights reserved.</span>
            <span>Scripture quotations from the King James Version (public domain).</span>
        </div>
    </div>
</footer>
