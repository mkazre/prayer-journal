<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class CourseAnnouncementModel extends Model
{
    protected $table         = 'course_announcements';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['course_id', 'user_id', 'title', 'body', 'created_at'];

    public function forCourse(int $courseId, int $limit = 20): array
    {
        return $this->where('course_id', $courseId)->orderBy('created_at', 'DESC')->findAll($limit);
    }
}
