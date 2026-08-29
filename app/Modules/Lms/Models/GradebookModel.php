<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class GradebookModel extends Model
{
    protected $table            = 'gradebook_entries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['course_id', 'user_id', 'quiz_avg_percent', 'assignment_avg_percent', 'overall_percent', 'updated_at'];

    public function recalculate(int $courseId, int $userId): array
    {
        $quizAvg = $this->db->table('quiz_attempts')
            ->select('AVG(score_percent) as avg_score')
            ->where('user_id', $userId)
            ->where('quiz_id IN (SELECT id FROM quizzes WHERE course_id = ' . (int) $courseId . ')', null, false)
            ->get()->getRowArray();

        $assignmentAvg = $this->db->table('assignment_submissions')
            ->select('AVG(points_awarded) as avg_points, AVG(max_points) as avg_max')
            ->join('assignments', 'assignments.id = assignment_submissions.assignment_id')
            ->where('assignment_submissions.user_id', $userId)
            ->where('assignments.course_id', $courseId)
            ->where('assignment_submissions.status', 'graded')
            ->get()->getRowArray();

        $quizPercent = $quizAvg && $quizAvg['avg_score'] !== null ? (int) round((float) $quizAvg['avg_score']) : null;

        $assignmentPercent = null;
        if ($assignmentAvg && $assignmentAvg['avg_max'] > 0) {
            $assignmentPercent = (int) round(((float) $assignmentAvg['avg_points'] / (float) $assignmentAvg['avg_max']) * 100);
        }

        $components = array_filter([$quizPercent, $assignmentPercent], static fn ($v) => $v !== null);
        $overall    = $components !== [] ? (int) round(array_sum($components) / count($components)) : null;

        $existing = $this->where(['course_id' => $courseId, 'user_id' => $userId])->first();
        $data     = [
            'course_id'              => $courseId,
            'user_id'                => $userId,
            'quiz_avg_percent'       => $quizPercent,
            'assignment_avg_percent' => $assignmentPercent,
            'overall_percent'        => $overall,
            'updated_at'             => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->update($existing['id'], $data);

            return $this->find($existing['id']);
        }

        $id = $this->insert($data, true);

        return $this->find($id);
    }
}
