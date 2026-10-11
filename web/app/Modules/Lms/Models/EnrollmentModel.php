<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class EnrollmentModel extends Model
{
    protected $table            = 'enrollments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['course_id', 'user_id', 'status', 'progress_percent', 'last_lesson_id', 'enrolled_at', 'completed_at'];

    public function enroll(int $courseId, int $userId): array
    {
        $existing = $this->where(['course_id' => $courseId, 'user_id' => $userId])->first();
        if ($existing) {
            return $existing;
        }

        $id = $this->insert([
            'course_id'   => $courseId,
            'user_id'     => $userId,
            'status'      => 'active',
            'enrolled_at' => date('Y-m-d H:i:s'),
        ], true);

        return $this->find($id);
    }

    public function forUser(int $userId): array
    {
        return $this->db->table('enrollments')
            ->select('enrollments.*, courses.title, courses.slug, courses.thumbnail_path')
            ->join('courses', 'courses.id = enrollments.course_id')
            ->where('enrollments.user_id', $userId)
            ->get()->getResultArray();
    }

    public function markLessonComplete(int $enrollmentId, int $lessonId, int $totalLessons): void
    {
        $this->db->table('lesson_completions')->ignore(true)->insert([
            'enrollment_id' => $enrollmentId,
            'lesson_id'     => $lessonId,
            'completed_at'  => date('Y-m-d H:i:s'),
        ]);

        $completed = $this->db->table('lesson_completions')->where('enrollment_id', $enrollmentId)->countAllResults();
        $percent   = $totalLessons > 0 ? (int) round(($completed / $totalLessons) * 100) : 0;

        $data = ['progress_percent' => $percent];
        if ($percent >= 100) {
            $data['completed_at'] = date('Y-m-d H:i:s');
            $data['status']       = 'completed';
        }
        $this->update($enrollmentId, $data);

        if ($percent >= 100) {
            $this->issueCertificateIfMissing($enrollmentId);
        }
    }

    protected function issueCertificateIfMissing(int $enrollmentId): void
    {
        $enrollment = $this->find($enrollmentId);
        $exists     = $this->db->table('certificates')->where(['course_id' => $enrollment['course_id'], 'user_id' => $enrollment['user_id']])->countAllResults();
        if ($exists) {
            return;
        }
        $this->db->table('certificates')->insert([
            'course_id'        => $enrollment['course_id'],
            'user_id'          => $enrollment['user_id'],
            'certificate_code' => strtoupper(bin2hex(random_bytes(8))),
            'issued_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    public function completedLessonIds(int $enrollmentId): array
    {
        return $this->db->table('lesson_completions')
            ->select('lesson_id')
            ->where('enrollment_id', $enrollmentId)
            ->get()->getResultArray();
    }
}
