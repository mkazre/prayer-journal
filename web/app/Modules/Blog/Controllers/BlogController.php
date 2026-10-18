<?php

namespace App\Modules\Blog\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Uploads;
use App\Modules\Blog\Models\BlogArchiveModel;
use App\Modules\Blog\Models\BlogPostModel;
use App\Modules\Blog\Services\BlogAccess;

/**
 * Public blog: archives (series), single posts and their resources.
 * Titles, images and intros are public; full text and downloads follow
 * BlogAccess.
 */
class BlogController extends BaseController
{
    private const RANGE_CHUNK = 4 * 1024 * 1024;

    public function index()
    {
        $archives = (new BlogArchiveModel())->published();
        if (count($archives) === 1) {
            return redirect()->to(site_url('blog/' . $archives[0]['slug']));
        }

        return view('Blog/index', ['title' => 'Blog', 'archives' => $archives]);
    }

    public function archive(string $slug): string
    {
        $archive = $this->publishedArchive($slug);
        $posts   = (new BlogPostModel())->forArchive((int) $archive['id']);

        return view('Blog/archive', [
            'title'           => $archive['title'],
            'metaDescription' => excerpt((string) $archive['description'], 160),
            'archive'         => $archive,
            'posts'           => $posts,
            'counts'          => (new BlogPostModel())->resourceCounts(array_column($posts, 'id')),
        ]);
    }

    public function post(string $archiveSlug, string $slug): string
    {
        $archive = $this->publishedArchive($archiveSlug);
        $post    = $this->publishedPost($archive, $slug);
        $model   = new BlogPostModel();
        $others  = array_values(array_filter($model->forArchive((int) $archive['id']), static fn ($p) => (int) $p['id'] !== (int) $post['id']));

        return view('Blog/post', [
            'title'           => $post['title'],
            'metaDescription' => $post['excerpt'] ?: excerpt(strip_tags((string) $post['body']), 160),
            'ogImage'         => $post['featured_image'] ? base_url($post['featured_image']) : null,
            'archive'         => $archive,
            'post'            => $post,
            'level'           => BlogAccess::level($post, $archive),
            'canRead'         => BlogAccess::canRead($post, $archive, auth()->id() ? $this->userId() : null),
            'resources'       => $model->resources((int) $post['id']),
            'others'          => array_slice($others, 0, 3),
        ]);
    }

    /**
     * Streams a resource to someone allowed to read its post. Supports HTTP
     * range requests so audio and video can seek (Safari needs this).
     */
    public function resource(int $id)
    {
        $res = db_connect()->table('blog_resources')->where('id', $id)->get()->getRowArray();
        $post = $res ? (new BlogPostModel())->find($res['post_id']) : null;
        $archive = $post ? (new BlogArchiveModel())->find($post['archive_id']) : null;
        $staff = is_staff();
        if (! $res || ! $post || ! $archive || (! $staff && ($post['status'] !== 'published' || $archive['status'] !== 'published'))) {
            $this->notFound();
        }
        if (! BlogAccess::canRead($post, $archive, $this->userId())) {
            return redirect()->to(site_url('blog/' . $archive['slug'] . '/' . $post['slug']))->with('error', 'That resource is for ' . (BlogAccess::level($post, $archive) === 'customers' ? 'customers' : 'signed-in members') . '.');
        }
        $path = Uploads::path($res['file_path']);
        if (! is_file($path)) {
            $this->notFound();
        }

        $size = filesize($path);
        $mime = $res['mime'] ?: (mime_content_type($path) ?: 'application/octet-stream');
        $name = (url_title($res['title'], '-', true) ?: 'resource') . '.' . pathinfo($path, PATHINFO_EXTENSION);

        if (! preg_match('/^bytes=(\d*)-(\d*)$/', $this->request->getHeaderLine('Range'), $m) || ($m[1] === '' && $m[2] === '')) {
            // Whole file: the framework streams it rather than loading it into memory.
            $download = $this->response->download($path, null)->setFileName($name)->setContentType($mime)
                ->setHeader('Accept-Ranges', 'bytes')->setHeader('Cache-Control', 'private, no-store')->setHeader('X-Content-Type-Options', 'nosniff');

            return $this->request->getGet('download') ? $download : $download->inline();
        }

        // Range request from an audio/video player. Chunks are capped so a
        // long video never has to sit in memory; players ask for the rest.
        if ($m[1] === '') {
            $start = max(0, $size - (int) $m[2]); // "bytes=-500" = the last 500 bytes
            $end   = $size - 1;
        } else {
            $start = (int) $m[1];
            $end   = $m[2] !== '' ? min((int) $m[2], $size - 1) : $size - 1;
        }
        if ($start > $end || $start >= $size) {
            return $this->response->setStatusCode(416)->setHeader('Content-Range', 'bytes */' . $size);
        }
        $end = min($end, $start + self::RANGE_CHUNK - 1);
        $fh  = fopen($path, 'rb');
        fseek($fh, $start);
        $body = (string) stream_get_contents($fh, $end - $start + 1);
        fclose($fh);

        return $this->response->setStatusCode(206)
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Range', "bytes {$start}-{$end}/{$size}")
            ->setHeader('Content-Disposition', 'inline; filename="' . $name . '"')
            ->setHeader('Accept-Ranges', 'bytes')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($body);
    }

    private function publishedArchive(string $slug): array
    {
        $archive = (new BlogArchiveModel())->where('slug', $slug)->first();
        if (! $archive || ($archive['status'] !== 'published' && ! is_staff())) {
            $this->notFound();
        }

        return $archive;
    }

    private function publishedPost(array $archive, string $slug): array
    {
        $post = (new BlogPostModel())->where(['archive_id' => $archive['id'], 'slug' => $slug])->first();
        $live = $post && $post['status'] === 'published' && strtotime((string) $post['published_at']) <= time();
        if (! $post || (! $live && ! is_staff())) {
            $this->notFound();
        }

        return $post;
    }
}
