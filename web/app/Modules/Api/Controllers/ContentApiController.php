<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\SignedLink;
use App\Modules\Blog\Models\BlogArchiveModel;
use App\Modules\Blog\Models\BlogPostModel;
use App\Modules\Blog\Services\BlogAccess;

/**
 * Mobile endpoints for the blog (e.g. Prayerthon) and the customer's
 * library of purchased digital products. Protected files are handed out as
 * short-lived signed web links the app opens in its in-app browser.
 */
class ContentApiController extends BaseApiController
{
    public function archives()
    {
        return $this->ok(array_map(fn ($a) => $this->archiveJson($a), (new BlogArchiveModel())->published()));
    }

    public function archive(string $slug)
    {
        $archive = (new BlogArchiveModel())->where(['slug' => $slug, 'status' => 'published'])->first();
        if (! $archive) {
            return $this->fail('Not found.', 404);
        }
        $model  = new BlogPostModel();
        $posts  = $model->forArchive((int) $archive['id']);
        $counts = $model->resourceCounts(array_column($posts, 'id'));

        return $this->ok($this->archiveJson($archive) + ['posts' => array_map(static fn ($p) => [
            'slug' => $p['slug'], 'title' => $p['title'], 'excerpt' => $p['excerpt'],
            'image_url' => $p['featured_image'] ? base_url($p['featured_image']) : null,
            'published_at' => $p['published_at'], 'resource_count' => $counts[(int) $p['id']] ?? 0,
        ], $posts)]);
    }

    public function post(string $archiveSlug, string $slug)
    {
        $archive = (new BlogArchiveModel())->where(['slug' => $archiveSlug, 'status' => 'published'])->first();
        $model   = new BlogPostModel();
        $post    = $archive ? $model->where(['archive_id' => $archive['id'], 'slug' => $slug, 'status' => 'published'])->where('published_at <=', date('Y-m-d H:i:s'))->first() : null;
        if (! $post) {
            return $this->fail('Not found.', 404);
        }
        $user    = $this->optionalUser();
        $userId  = $user ? (int) $user->id : null;
        $canRead = BlogAccess::canRead($post, $archive, $userId, $userId ? BlogAccess::isStaffUser($userId) : false);

        return $this->ok([
            'archive'   => ['slug' => $archive['slug'], 'title' => $archive['title']],
            'slug'      => $post['slug'],
            'title'     => $post['title'],
            'excerpt'   => $post['excerpt'],
            'image_url' => $post['featured_image'] ? base_url($post['featured_image']) : null,
            'published_at' => $post['published_at'],
            'access'    => BlogAccess::level($post, $archive),
            'can_read'  => $canRead,
            'body'      => $canRead ? scripture_links(clean_html((string) $post['body'])) : null,
            'web_url'   => site_url('blog/' . $archive['slug'] . '/' . $post['slug']),
            'resources' => array_map(static fn ($r) => [
                'id' => (int) $r['id'], 'title' => $r['title'], 'kind' => $r['kind'], 'size_bytes' => (int) $r['size_bytes'],
                // Only readers with access get a link; it expires after two hours.
                'url' => $canRead ? site_url('blog/resources/' . $r['id']) . '?' . SignedLink::sign('blog-resource:' . $r['id'], (int) $userId) : null,
            ], $model->resources((int) $post['id'])),
        ]);
    }

    /**
     * Everything digital the signed-in customer has bought, with links to
     * read (flipbook) and/or download.
     */
    public function library()
    {
        $userId = (int) $this->currentUser()->id;
        $rows   = db_connect()->table('order_items oi')
            ->select('oi.id AS item_id, oi.order_id, oi.title_snapshot, o.order_number, o.created_at, p.slug, p.flipbook, p.allow_download, v.digital_file_path')
            ->select('(SELECT i.path FROM product_images i WHERE i.product_id = oi.product_id ORDER BY i.position, i.id LIMIT 1) AS image', false)
            ->join('orders o', 'o.id = oi.order_id')
            ->join('products p', 'p.id = oi.product_id')
            ->join('product_variants v', 'v.id = oi.variant_id', 'left')
            ->where('o.user_id', $userId)
            ->whereIn('o.status', ['paid', 'processing', 'shipped', 'completed'])
            ->where('p.product_type', 'digital')
            ->orderBy('o.created_at', 'DESC')
            ->get()->getResultArray();

        $items = [];
        foreach ($rows as $r) {
            if (! $r['digital_file_path']) {
                continue;
            }
            $pdf      = strtolower(pathinfo($r['digital_file_path'], PATHINFO_EXTENSION)) === 'pdf';
            $canRead  = (int) $r['flipbook'] === 1 && $pdf;
            $canDl    = (int) $r['allow_download'] === 1 || ! $canRead;
            $o        = (int) $r['order_id'];
            $i        = (int) $r['item_id'];
            $items[]  = [
                'order_id' => $o, 'item_id' => $i, 'order_number' => $r['order_number'], 'title' => $r['title_snapshot'],
                'image_url' => $r['image'] ? base_url($r['image']) : null, 'purchased_at' => $r['created_at'], 'product_slug' => $r['slug'],
                'read_url' => $canRead ? site_url('orders/' . $o . '/read/' . $i) . '?' . SignedLink::sign('flipbook:' . $o . ':' . $i, $userId) : null,
                'download_url' => $canDl ? site_url('orders/' . $o . '/download/' . $i) . '?' . SignedLink::sign('download:' . $o . ':' . $i, $userId) : null,
            ];
        }

        return $this->ok($items);
    }

    private function archiveJson(array $a): array
    {
        return [
            'slug' => $a['slug'], 'title' => $a['title'], 'description' => $a['description'],
            'cover_url' => $a['cover_path'] ? base_url($a['cover_path']) : null, 'access' => $a['access'],
        ];
    }
}
