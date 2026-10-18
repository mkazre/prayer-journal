<?php

namespace App\Modules\Blog\Models;

use CodeIgniter\Model;

class BlogArchiveModel extends Model
{
    protected $table          = 'blog_archives';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['title', 'slug', 'description', 'cover_path', 'access', 'status', 'show_in_menu', 'position'];

    public function published(): array
    {
        return $this->where('status', 'published')->orderBy('position')->orderBy('title')->findAll();
    }

    /**
     * Published archives switched on for the site menus.
     */
    public static function menu(): array
    {
        try {
            return (new self())->select('title, slug')->where(['status' => 'published', 'show_in_menu' => 1])->orderBy('position')->orderBy('title')->findAll();
        } catch (\Throwable $e) {
            return []; // Before migrations have run.
        }
    }

    public function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $base = url_title($text, '-', true) ?: 'archive';
        $slug = $base;
        for ($i = 2; $this->where('slug', $slug)->where('id !=', $ignoreId ?? 0)->countAllResults() > 0; $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
