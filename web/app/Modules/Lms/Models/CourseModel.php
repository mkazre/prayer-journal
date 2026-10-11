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
        'category_id', 'title', 'slug', 'summary', 'description',
        'thumbnail_path', 'price_cents', 'currency', 'status', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $validationRules = [
        'title' => 'required|min_length[3]|max_length[200]',
        'slug'  => 'required|alpha_dash|max_length[220]|is_unique[courses.slug,id,{id}]',
    ];

    public function published()
    {
        return $this->where('status', 'published');
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
        $course = $this->find($courseId);
        if ($course && (int) $course['created_by'] === $userId) {
            return true;
        }

        return (bool) $this->db->table('course_instructors')
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->countAllResults();
    }
}
