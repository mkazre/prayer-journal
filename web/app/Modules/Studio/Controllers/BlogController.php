<?php

namespace App\Modules\Studio\Controllers;

use App\Libraries\HtmlSanitizer;
use App\Libraries\Uploads;
use App\Modules\Blog\Models\BlogArchiveModel;
use App\Modules\Blog\Models\BlogPostModel;
use App\Modules\Blog\Services\BlogAccess;

/**
 * Blog management: archives (series) and their posts, featured images and
 * downloadable resources. Managers and admins only.
 */
class BlogController extends StudioController
{
    /** Resource uploads: up to 200 MB so short videos fit. */
    private const MAX_RESOURCE_KB = 204800;

    public function index(): string
    {
        $this->requireManager();
        $archives = (new BlogArchiveModel())->orderBy('position')->orderBy('title')->findAll();
        $current  = (int) $this->request->getGet('archive') ?: (int) ($archives[0]['id'] ?? 0);
        $posts    = $current ? (new BlogPostModel())->where('archive_id', $current)->orderBy('COALESCE(published_at, created_at)', 'DESC', false)->findAll() : [];
        $counts   = [];
        foreach (db_connect()->table('blog_posts')->select('archive_id, COUNT(*) AS n')->groupBy('archive_id')->get()->getResultArray() as $r) {
            $counts[(int) $r['archive_id']] = (int) $r['n'];
        }

        return $this->render('blog/index', [
            'title' => 'Blog', 'active' => 'blog', 'archives' => $archives, 'current' => $current, 'posts' => $posts,
            'counts' => $counts, 'resourceCounts' => (new BlogPostModel())->resourceCounts(array_column($posts, 'id')),
        ]);
    }

    // ------------------------------------------------------------------
    // Archives
    // ------------------------------------------------------------------

    public function createArchive()
    {
        $this->requireManager();
        if ($this->request->getMethod() === 'POST') {
            return $this->saveArchive(null);
        }

        return $this->render('blog/archive_form', ['title' => 'New archive', 'active' => 'blog', 'archive' => [
            'id' => null, 'title' => '', 'slug' => '', 'description' => '', 'cover_path' => null, 'access' => 'members', 'status' => 'published', 'show_in_menu' => 0, 'position' => 0,
        ]]);
    }

    public function editArchive(int $id)
    {
        $archive = $this->archiveOr404($id);
        if ($this->request->getMethod() === 'POST') {
            return $this->saveArchive($archive);
        }

        return $this->render('blog/archive_form', ['title' => 'Edit archive', 'active' => 'blog', 'archive' => $archive]);
    }

    public function deleteArchive(int $id)
    {
        $archive = $this->archiveOr404($id);
        $model   = new BlogPostModel();
        foreach ($model->where('archive_id', $id)->findAll() as $post) {
            $this->deletePostFiles($post);
        }
        Uploads::delete($archive['cover_path']);
        (new BlogArchiveModel())->delete($id); // posts and resources cascade

        return $this->done('Archive and its posts deleted.', [], site_url('studio/blog'));
    }

    private function saveArchive(?array $archive)
    {
        $model = new BlogArchiveModel();
        $title = trim((string) $this->post('title'));
        if ($title === '') {
            return redirect()->back()->withInput()->with('error', 'Give the archive a title.');
        }
        $data = [
            'title'        => mb_substr($title, 0, 150),
            'slug'         => $model->uniqueSlug((string) ($this->post('slug') ?: $title), $archive['id'] ?? null),
            'description'  => trim((string) $this->post('description')) ?: null,
            'access'       => isset(BlogAccess::LEVELS[$this->post('access')]) ? $this->post('access') : 'members',
            'status'       => $this->post('status') === 'draft' ? 'draft' : 'published',
            'show_in_menu' => $this->bool('show_in_menu'),
            'position'     => (int) $this->post('position'),
        ];
        try {
            if ($cover = Uploads::image($this->request->getFile('cover'), 'blog', 8192)) {
                Uploads::delete($archive['cover_path'] ?? null);
                $data['cover_path'] = $cover;
            } elseif ($archive && $this->post('remove_cover')) {
                Uploads::delete($archive['cover_path']);
                $data['cover_path'] = null;
            }
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($archive) {
            $model->update($archive['id'], $data);
            $id = (int) $archive['id'];
        } else {
            $id = (int) $model->insert($data, true);
        }

        return redirect()->to(site_url('studio/blog?archive=' . $id))->with('message', $archive ? 'Archive saved.' : 'Archive created — now add posts to it.');
    }

    // ------------------------------------------------------------------
    // Posts
    // ------------------------------------------------------------------

    public function createPost(int $archiveId)
    {
        $archive = $this->archiveOr404($archiveId);
        if ($this->request->getMethod() === 'POST') {
            return $this->savePost($archive, null);
        }

        return $this->postForm($archive, [
            'id' => null, 'archive_id' => $archiveId, 'title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'featured_image' => null,
            'access' => 'inherit', 'status' => 'draft', 'published_at' => null,
        ]);
    }

    public function editPost(int $id)
    {
        $post    = $this->postOr404($id);
        $archive = $this->archiveOr404((int) $post['archive_id']);
        if ($this->request->getMethod() === 'POST') {
            return $this->savePost($archive, $post);
        }

        return $this->postForm($archive, $post);
    }

    public function deletePost(int $id)
    {
        $post = $this->postOr404($id);
        $this->deletePostFiles($post);
        (new BlogPostModel())->delete($id);

        return $this->done('Post deleted.', [], site_url('studio/blog?archive=' . $post['archive_id']));
    }

    public function deleteResource(int $id)
    {
        $this->requireManager();
        $db  = db_connect();
        $res = $db->table('blog_resources')->where('id', $id)->get()->getRowArray();
        if (! $res) {
            $this->notFound();
        }
        $db->table('blog_resources')->where('id', $id)->delete();
        Uploads::delete($res['file_path']);

        return $this->done('Resource removed.');
    }

    private function postForm(array $archive, array $post): string
    {
        return $this->render('blog/post_form', [
            'title'     => $post['id'] ? 'Edit post' : 'New post',
            'active'    => 'blog',
            'archive'   => $archive,
            'archives'  => (new BlogArchiveModel())->orderBy('title')->findAll(),
            'post'      => $post,
            'resources' => $post['id'] ? (new BlogPostModel())->resources((int) $post['id']) : [],
        ]);
    }

    private function savePost(array $archive, ?array $post)
    {
        $model = new BlogPostModel();
        $db    = db_connect();
        $title = trim((string) $this->post('title'));
        if ($title === '') {
            return redirect()->back()->withInput()->with('error', 'Give the post a title.');
        }
        $archiveId = (int) $this->post('archive_id') ?: (int) $archive['id'];
        if (! (new BlogArchiveModel())->find($archiveId)) {
            $archiveId = (int) $archive['id'];
        }
        $status    = $this->post('status') === 'published' ? 'published' : 'draft';
        $published = trim((string) $this->post('published_at'));
        $data      = [
            'archive_id'   => $archiveId,
            'title'        => mb_substr($title, 0, 200),
            'slug'         => $model->uniqueSlug($archiveId, (string) ($this->post('slug') ?: $title), $post['id'] ?? null),
            'excerpt'      => trim(mb_substr((string) $this->post('excerpt'), 0, 500)) ?: null,
            'body'         => HtmlSanitizer::clean((string) $this->post('body')),
            'access'       => in_array($this->post('access'), ['inherit', 'members', 'customers'], true) ? $this->post('access') : 'inherit',
            'status'       => $status,
            'published_at' => $published !== '' ? date('Y-m-d H:i:s', strtotime($published)) : ($post['published_at'] ?? null),
        ];
        if ($status === 'published' && ! $data['published_at']) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        try {
            if ($image = Uploads::image($this->request->getFile('featured_image'), 'blog', 8192)) {
                Uploads::delete($post['featured_image'] ?? null);
                $data['featured_image'] = $image;
            } elseif ($post && $this->post('remove_image')) {
                Uploads::delete($post['featured_image']);
                $data['featured_image'] = null;
            }
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', 'Featured image: ' . $e->getMessage());
        }

        if ($post) {
            $model->update($post['id'], $data);
            $id = (int) $post['id'];
        } else {
            $data['created_by'] = $this->userId();
            $id                 = (int) $model->insert($data, true);
        }

        // Existing resources: titles and order.
        foreach ((array) $this->post('resource_title') as $rid => $rtitle) {
            $db->table('blog_resources')->where(['id' => (int) $rid, 'post_id' => $id])
                ->update(['title' => mb_substr(trim((string) $rtitle), 0, 200) ?: 'Resource', 'position' => (int) ($this->post('resource_position')[$rid] ?? 0)]);
        }

        // New resources: stored privately, served only to readers with access.
        $next   = (int) $db->table('blog_resources')->selectMax('position')->where('post_id', $id)->get()->getRow('position') + 1;
        $errors = [];
        foreach ($this->request->getFileMultiple('resources') ?? [] as $file) {
            try {
                if ($path = Uploads::file($file, 'blog', self::MAX_RESOURCE_KB, true)) {
                    $db->table('blog_resources')->insert([
                        'post_id'    => $id,
                        'title'      => mb_substr(pathinfo($file->getClientName(), PATHINFO_FILENAME), 0, 200) ?: 'Resource',
                        'file_path'  => $path,
                        'kind'       => self::kind($file->getClientExtension()),
                        'mime'       => $file->getClientMimeType(),
                        'size_bytes' => (int) filesize(Uploads::path($path)),
                        'position'   => $next++,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\RuntimeException $e) {
                $errors[] = $file->getClientName() . ': ' . $e->getMessage();
            }
        }

        $redirect = redirect()->to(site_url('studio/blog/posts/' . $id))->with('message', $post ? 'Post saved.' : 'Post created.');

        return $errors ? $redirect->with('error', 'Some files were not added — ' . implode(' ', $errors)) : $redirect;
    }

    public static function kind(string $ext): string
    {
        $ext = strtolower($ext);

        return match (true) {
            $ext === 'pdf'                                     => 'pdf',
            in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) => 'image',
            $ext === 'mp4'                                     => 'video',
            in_array($ext, ['mp3', 'm4a'], true)               => 'audio',
            default                                            => 'file',
        };
    }

    private function deletePostFiles(array $post): void
    {
        foreach ((new BlogPostModel())->resources((int) $post['id']) as $res) {
            Uploads::delete($res['file_path']);
        }
        Uploads::delete($post['featured_image']);
    }

    private function archiveOr404(int $id): array
    {
        $this->requireManager();
        $archive = (new BlogArchiveModel())->find($id);
        if (! $archive) {
            $this->notFound();
        }

        return $archive;
    }

    private function postOr404(int $id): array
    {
        $this->requireManager();
        $post = (new BlogPostModel())->find($id);
        if (! $post) {
            $this->notFound();
        }

        return $post;
    }
}
