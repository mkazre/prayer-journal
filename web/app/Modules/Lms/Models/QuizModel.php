<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class QuizModel extends Model
{
    protected $table            = 'quizzes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['course_id', 'lesson_id', 'title', 'instructions', 'pass_percent', 'time_limit_mins', 'max_attempts'];
    protected $useTimestamps    = true;

    public function withQuestions(int $quizId): ?array
    {
        $quiz = $this->find($quizId);
        if (! $quiz) {
            return null;
        }

        $questions = $this->db->table('quiz_questions')->where('quiz_id', $quizId)->orderBy('position', 'asc')->get()->getResultArray();
        foreach ($questions as &$q) {
            $q['options'] = $this->db->table('quiz_options')->where('question_id', $q['id'])->orderBy('position', 'asc')->get()->getResultArray();
        }
        $quiz['questions'] = $questions;

        return $quiz;
    }
}
