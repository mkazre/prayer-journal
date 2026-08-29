<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class CourseReviewModel extends Model
{
    protected $table         = 'course_reviews';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['course_id', 'user_id', 'rating', 'body', 'created_at'];

    public function forCourse(int $courseId): array
    {
        return $this->db->table('course_reviews')
            ->select('course_reviews.*, users.username')
            ->join('users', 'users.id = course_reviews.user_id')
            ->where('course_id', $courseId)
            ->orderBy('created_at', 'desc')
            ->get()->getResultArray();
    }

    public function averageRating(int $courseId): ?float
    {
        $row = $this->where('course_id', $courseId)->selectAvg('rating')->first();

        return $row && $row['rating'] !== null ? round((float) $row['rating'], 1) : null;
    }

    public function upsert(int $courseId, int $userId, int $rating, string $body): void
    {
        $existing = $this->where(['course_id' => $courseId, 'user_id' => $userId])->first();
        if ($existing) {
            $this->update($existing['id'], ['rating' => $rating, 'body' => $body]);

            return;
        }
        $this->insert(['course_id' => $courseId, 'user_id' => $userId, 'rating' => $rating, 'body' => $body, 'created_at' => date('Y-m-d H:i:s')]);
    }
}
