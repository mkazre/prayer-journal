<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class LessonNoteModel extends Model
{
    protected $table         = 'lesson_notes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'lesson_id', 'body'];
    protected $useTimestamps = true;

    public function forLesson(int $userId, int $lessonId): ?array
    {
        return $this->where(['user_id' => $userId, 'lesson_id' => $lessonId])->first();
    }

    public function saveFor(int $userId, int $lessonId, string $body): void
    {
        $existing = $this->forLesson($userId, $lessonId);
        if ($existing) {
            $this->update($existing['id'], ['body' => $body]);

            return;
        }
        $this->insert(['user_id' => $userId, 'lesson_id' => $lessonId, 'body' => $body]);
    }

    public function forCourse(int $userId, int $courseId): array
    {
        return $this->db->table('lesson_notes n')
            ->select('n.*, l.title AS lesson_title, l.id AS lesson_id')
            ->join('lessons l', 'l.id = n.lesson_id')
            ->where('n.user_id', $userId)
            ->where('l.course_id', $courseId)
            ->where("n.body != ''")
            ->orderBy('l.position', 'ASC')
            ->get()->getResultArray();
    }
}
