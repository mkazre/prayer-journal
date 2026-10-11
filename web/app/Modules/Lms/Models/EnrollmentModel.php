<?php

namespace App\Modules\Lms\Models;

use App\Modules\Lms\Services\Curriculum;
use CodeIgniter\Model;

class EnrollmentModel extends Model
{
    protected $table            = 'enrollments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'course_id', 'user_id', 'status', 'progress_percent', 'last_lesson_id', 'enrolled_at', 'completed_at',
        'source', 'expires_at', 'last_activity_at',
    ];

    public function enroll(int $courseId, int $userId, string $source = 'free', bool $notify = true): array
    {
        $existing = $this->where(['course_id' => $courseId, 'user_id' => $userId])->first();
        if ($existing) {
            if ($existing['status'] === 'revoked') {
                $this->update($existing['id'], ['status' => 'active', 'source' => $source]);

                return $this->find($existing['id']);
            }
            // Full access (purchase, membership, free) replaces module-only or
            // lapsed-membership access; a module purchase never downgrades.
            $partial = in_array($existing['source'], ['module', 'subscription'], true);
            if ($partial && $source !== 'module' && $source !== $existing['source']) {
                $this->update($existing['id'], ['source' => $source]);

                return $this->find($existing['id']);
            }

            return $existing;
        }

        $course   = (new CourseModel())->withDeleted()->find($courseId);
        $settings = $course ? CourseModel::settings($course) : [];
        $expires  = ! empty($settings['access_days']) ? date('Y-m-d H:i:s', strtotime('+' . (int) $settings['access_days'] . ' days')) : null;

        $id = $this->insert([
            'course_id'        => $courseId,
            'user_id'          => $userId,
            'status'           => 'active',
            'source'           => $source,
            'enrolled_at'      => date('Y-m-d H:i:s'),
            'expires_at'       => $expires,
            'last_activity_at' => date('Y-m-d H:i:s'),
        ], true);

        if ($notify && $course) {
            \App\Libraries\Notify::enrolled($userId, $course);
        }

        return $this->find($id);
    }

    public function findFor(int $courseId, int $userId): ?array
    {
        return $this->where(['course_id' => $courseId, 'user_id' => $userId])->first();
    }

    public static function isActive(?array $enrollment): bool
    {
        if (! $enrollment || $enrollment['status'] === 'revoked') {
            return false;
        }

        return empty($enrollment['expires_at']) || strtotime($enrollment['expires_at']) > time();
    }

    public function forUser(int $userId): array
    {
        return $this->db->table('enrollments')
            ->select('enrollments.*, courses.title, courses.slug, courses.thumbnail_path, courses.subtitle, courses.settings, lessons.title AS last_lesson_title')
            ->join('courses', 'courses.id = enrollments.course_id')
            ->join('lessons', 'lessons.id = enrollments.last_lesson_id', 'left')
            ->where('enrollments.user_id', $userId)
            ->where('enrollments.status !=', 'revoked')
            ->where('courses.deleted_at IS NULL')
            ->orderBy('COALESCE(enrollments.last_activity_at, enrollments.enrolled_at)', 'DESC', false)
            ->get()->getResultArray();
    }

    /**
     * Kept for API compatibility; $totalLessons is recomputed from the course.
     */
    public function markLessonComplete(int $enrollmentId, int $lessonId, int $totalLessons = 0): array
    {
        $exists = $this->db->table('lesson_completions')->where(['enrollment_id' => $enrollmentId, 'lesson_id' => $lessonId])->countAllResults();
        if (! $exists) {
            $this->db->table('lesson_completions')->insert([
                'enrollment_id' => $enrollmentId,
                'lesson_id'     => $lessonId,
                'completed_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return Curriculum::recalculate($enrollmentId);
    }

    public function markLessonIncomplete(int $enrollmentId, int $lessonId): array
    {
        $this->db->table('lesson_completions')->where(['enrollment_id' => $enrollmentId, 'lesson_id' => $lessonId])->delete();

        return Curriculum::recalculate($enrollmentId);
    }

    public function completedLessonIds(int $enrollmentId): array
    {
        return $this->db->table('lesson_completions')
            ->select('lesson_id')
            ->where('enrollment_id', $enrollmentId)
            ->get()->getResultArray();
    }
}
