<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class AssignmentModel extends Model
{
    protected $table            = 'assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['course_id', 'lesson_id', 'title', 'instructions', 'max_points', 'due_at'];
    protected $useTimestamps    = true;
}
