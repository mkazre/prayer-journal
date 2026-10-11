<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class LessonModel extends Model
{
    protected $table            = 'lessons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'course_id', 'title', 'content', 'video_url', 'attachment_path',
        'bible_reference', 'position', 'available_at', 'status',
    ];
    protected $useTimestamps = true;

    public function forCourse(int $courseId, bool $publishedOnly = true)
    {
        $builder = $this->where('course_id', $courseId)->orderBy('position', 'asc');
        if ($publishedOnly) {
            $builder->where('status', 'published')
                ->groupStart()
                    ->where('available_at IS NULL')
                    ->orWhere('available_at <=', date('Y-m-d H:i:s'))
                ->groupEnd();
        }

        return $builder->findAll();
    }
}
