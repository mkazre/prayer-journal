<?php

namespace App\Modules\Blog\Models;

use CodeIgniter\Model;

class BlogPostModel extends Model
{
    protected $table         = 'blog_posts';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['archive_id', 'title', 'slug', 'excerpt', 'body', 'featured_image', 'access', 'status', 'published_at', 'created_by'];

    /**
     * Published posts in an archive, newest first.
     */
    public function forArchive(int $archiveId): array
    {
        return $this->where('archive_id', $archiveId)->where('status', 'published')
            ->where('published_at <=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'DESC')->orderBy('id', 'DESC')->findAll();
    }

    public function resources(int $postId): array
    {
        return $this->db->table('blog_resources')->where('post_id', $postId)->orderBy('position')->orderBy('id')->get()->getResultArray();
    }

    /**
     * @return array<int,int> post_id => resource count
     */
    public function resourceCounts(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }
        $rows = $this->db->table('blog_resources')->select('post_id, COUNT(*) AS n')->whereIn('post_id', $postIds)->groupBy('post_id')->get()->getResultArray();

        return array_map('intval', array_column($rows, 'n', 'post_id'));
    }

    public function uniqueSlug(int $archiveId, string $text, ?int $ignoreId = null): string
    {
        $base = url_title($text, '-', true) ?: 'post';
        $slug = $base;
        for ($i = 2; $this->where(['archive_id' => $archiveId, 'slug' => $slug])->where('id !=', $ignoreId ?? 0)->countAllResults() > 0; $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
