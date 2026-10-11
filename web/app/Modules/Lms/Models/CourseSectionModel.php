<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class CourseSectionModel extends Model
{
    protected $table         = 'course_sections';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['course_id', 'title', 'summary', 'position', 'price_cents'];
    protected $useTimestamps = true;

    public function nextPosition(int $courseId): int
    {
        $row = $this->selectMax('position')->where('course_id', $courseId)->first();

        return (int) ($row['position'] ?? 0) + 1;
    }
}
