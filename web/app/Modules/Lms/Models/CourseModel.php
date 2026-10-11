<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class CourseModel extends Model
{
    protected $table            = 'courses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'category_id', 'title', 'slug', 'summary', 'description', 'thumbnail_path', 'price_cents', 'currency',
        'access_mode', 'status', 'created_by', 'subtitle', 'level', 'language', 'tags', 'intro_video_url',
        'what_you_learn', 'requirements', 'audience', 'author_name', 'author_bio', 'settings', 'is_featured',
        'published_at',
    ];
    protected $useTimestamps   = true;
    protected $validationRules = [
        'title' => 'required|min_length[3]|max_length[200]',
        'slug'  => 'required|alpha_dash|max_length[220]|is_unique[courses.slug,id,{id}]',
    ];

    public const LEVELS = ['all' => 'All levels', 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];

    /**
     * Every per-course option with its default. Stored as JSON in courses.settings.
     */
    public const DEFAULT_SETTINGS = [
        'sequential'            => false,
        'drip'                  => 'none',
        'completion'            => 'lessons',
        'certificate_enabled'   => true,
        'certificate_title'     => 'Certificate of Completion',
        'certificate_signatory' => '',
        'certificate_role'      => 'Course Author',
        'certificate_message'   => 'has faithfully completed every lesson of',
        'discussions'           => true,
        'reviews'               => true,
        'journal'               => true,
        'show_progress'         => true,
        'autoplay_next'         => false,
        'enrollment_limit'      => null,
        'access_days'           => null,
        'brand_color'           => '#4b1e82',
        'accent_color'          => '#c9a227',
        'welcome_message'       => '',
        'completion_message'    => '',
        'default_prompts'       => [],
        'preview_public'        => true,
    ];

    public function published()
    {
        return $this->where('courses.status', 'published');
    }

    public static function settings(array $course): array
    {
        $saved = json_decode((string) ($course['settings'] ?? ''), true);

        return array_merge(self::DEFAULT_SETTINGS, is_array($saved) ? $saved : []);
    }

    public function saveSettings(int $courseId, array $changes): void
    {
        $course  = $this->find($courseId);
        $current = self::settings($course ?? []);
        $merged  = array_merge($current, array_intersect_key($changes, self::DEFAULT_SETTINGS));
        $this->update($courseId, ['settings' => json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    /**
     * Published catalog with aggregate stats (lesson count, minutes,
     * enrollments, rating) for cards and filters.
     */
    public function catalog(array $filters = []): array
    {
        $b = $this->db->table('courses c')
            ->select('c.*, cc.name AS category_name, cc.slug AS category_slug')
            ->select('(SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id AND l.status = \'published\') AS lesson_count', false)
            ->select('(SELECT COALESCE(SUM(l.duration_mins),0) FROM lessons l WHERE l.course_id = c.id AND l.status = \'published\') AS total_minutes', false)
            ->select('(SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count', false)
            ->select('(SELECT AVG(r.rating) FROM course_reviews r WHERE r.course_id = c.id) AS avg_rating', false)
            ->select('(SELECT COUNT(*) FROM course_reviews r WHERE r.course_id = c.id) AS review_count', false)
            ->join('course_categories cc', 'cc.id = c.category_id', 'left')
            ->where('c.deleted_at IS NULL')
            ->where('c.status', 'published');

        if (! empty($filters['q'])) {
            $b->groupStart()->like('c.title', $filters['q'])->orLike('c.summary', $filters['q'])->orLike('c.tags', $filters['q'])->groupEnd();
        }
        if (! empty($filters['category'])) {
            $b->where('cc.slug', $filters['category']);
        }
        if (! empty($filters['level']) && isset(self::LEVELS[$filters['level']])) {
            $b->where('c.level', $filters['level']);
        }
        if (($filters['price'] ?? '') === 'free') {
            $b->where('c.price_cents', 0);
        } elseif (($filters['price'] ?? '') === 'paid') {
            $b->where('c.price_cents >', 0);
        }
        if (! empty($filters['featured'])) {
            $b->where('c.is_featured', 1);
        }

        switch ($filters['sort'] ?? 'popular') {
            case 'newest':
                $b->orderBy('c.created_at', 'DESC');
                break;
            case 'rating':
                $b->orderBy('avg_rating', 'DESC');
                break;
            case 'title':
                $b->orderBy('c.title', 'ASC');
                break;
            default:
                $b->orderBy('c.is_featured', 'DESC')->orderBy('student_count', 'DESC')->orderBy('c.created_at', 'DESC');
        }
        if (! empty($filters['limit'])) {
            $b->limit((int) $filters['limit']);
        }

        return $b->get()->getResultArray();
    }

    public function withStats(array $course): array
    {
        $id  = (int) $course['id'];
        $row = $this->db->query(
            'SELECT
                (SELECT COUNT(*) FROM lessons WHERE course_id = ? AND status = \'published\') AS lesson_count,
                (SELECT COALESCE(SUM(duration_mins),0) FROM lessons WHERE course_id = ? AND status = \'published\') AS total_minutes,
                (SELECT COUNT(*) FROM enrollments WHERE course_id = ?) AS student_count,
                (SELECT AVG(rating) FROM course_reviews WHERE course_id = ?) AS avg_rating,
                (SELECT COUNT(*) FROM course_reviews WHERE course_id = ?) AS review_count,
                (SELECT COUNT(*) FROM quizzes WHERE course_id = ?) AS quiz_count',
            [$id, $id, $id, $id, $id, $id]
        )->getRowArray();

        return array_merge($course, $row ?: []);
    }

    public function categories(): array
    {
        return $this->db->table('course_categories')->orderBy('name', 'ASC')->get()->getResultArray();
    }

    public function instructors(int $courseId): array
    {
        return $this->db->table('course_instructors')
            ->select('users.id, users.username, course_instructors.role')
            ->join('users', 'users.id = course_instructors.user_id')
            ->where('course_instructors.course_id', $courseId)
            ->get()->getResultArray();
    }

    public function isInstructor(int $courseId, int $userId): bool
    {
        $user = auth()->getProvider()->findById($userId);
        if ($user && $user->inGroup('superadmin', 'admin', 'manager')) {
            return true;
        }
        $course = $this->withDeleted()->find($courseId);
        if ($course && (int) $course['created_by'] === $userId) {
            return true;
        }

        return (bool) $this->db->table('course_instructors')
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->countAllResults();
    }

    /**
     * Courses a user can manage in the studio.
     */
    public function manageableBy(int $userId, bool $isAdmin): array
    {
        $b = $this->db->table('courses c')
            ->select('c.*')
            ->select('(SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lesson_count', false)
            ->select('(SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count', false)
            ->select('(SELECT AVG(r.rating) FROM course_reviews r WHERE r.course_id = c.id) AS avg_rating', false)
            ->where('c.deleted_at IS NULL');
        if (! $isAdmin) {
            $b->groupStart()
                ->where('c.created_by', $userId)
                ->orWhere('c.id IN (SELECT course_id FROM course_instructors WHERE user_id = ' . (int) $userId . ')', null, false)
                ->groupEnd();
        }

        return $b->orderBy('c.updated_at', 'DESC')->get()->getResultArray();
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = url_title($title, '-', true) ?: 'course';
        $slug = $base;
        $i    = 2;
        while (true) {
            $b = $this->withDeleted()->where('slug', $slug);
            if ($ignoreId) {
                $b->where('id !=', $ignoreId);
            }
            if (! $b->first()) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
