<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class AssignmentModel extends Model
{
    protected $table            = 'assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['course_id', 'section_id', 'lesson_id', 'title', 'instructions', 'max_points', 'due_at', 'position', 'allow_file'];
    protected $useTimestamps    = true;
}
