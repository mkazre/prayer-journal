<?php

namespace App\Modules\Studio\Controllers;

use App\Modules\Lms\Models\AssignmentSubmissionModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\GradebookModel;

class GradingController extends StudioController
{
    public function index(): string
    {
        $this->requireInstructor();
        $ids    = array_map('intval', array_column((new CourseModel())->manageableBy($this->userId(), $this->isAdmin()), 'id')) ?: [0];
        $status = (string) ($this->request->getGet('status') ?: 'submitted');
        $rows   = db_connect()->table('assignment_submissions s')
            ->select('s.*, a.title AS assignment_title, a.max_points, a.course_id, c.title AS course_title, u.username')
            ->join('assignments a', 'a.id = s.assignment_id')->join('courses c', 'c.id = a.course_id')->join('users u', 'u.id = s.user_id')
            ->whereIn('a.course_id', $ids)->where('s.status', $status === 'graded' ? 'graded' : 'submitted')
            ->orderBy('s.submitted_at', $status === 'graded' ? 'DESC' : 'ASC')->get()->getResultArray();

        return $this->render('grading/index', ['title' => 'Grading', 'active' => 'grading', 'rows' => $rows, 'status' => $status]);
    }

    public function grade(int $submissionId)
    {
        $db  = db_connect();
        $sub = $db->table('assignment_submissions s')->select('s.*, a.course_id, a.max_points, a.title')->join('assignments a', 'a.id = s.assignment_id')->where('s.id', $submissionId)->get()->getRowArray();
        if (! $sub) {
            $this->notFound();
        }
        $this->course((int) $sub['course_id']);
        $points = max(0, min((int) $sub['max_points'], (int) $this->post('points')));
        (new AssignmentSubmissionModel())->grade($submissionId, $this->userId(), $points, trim((string) $this->post('feedback')));
        (new GradebookModel())->recalculate((int) $sub['course_id'], (int) $sub['user_id']);
        $db->table('notifications')->insert(['user_id' => $sub['user_id'], 'type' => 'graded', 'body' => 'Your assignment "' . $sub['title'] . '" was graded: ' . $points . '/' . $sub['max_points'], 'created_at' => date('Y-m-d H:i:s')]);

        return $this->done('Graded ' . $points . '/' . $sub['max_points'] . '.');
    }

    public function gradebook(int $courseId): string
    {
        $course = $this->course($courseId);
        $rows   = db_connect()->table('enrollments e')
            ->select('e.user_id, e.progress_percent, e.status, u.username, g.quiz_avg_percent, g.assignment_avg_percent, g.overall_percent')
            ->join('users u', 'u.id = e.user_id')
            ->join('gradebook_entries g', 'g.user_id = e.user_id AND g.course_id = e.course_id', 'left')
            ->where('e.course_id', $courseId)->orderBy('u.username')->get()->getResultArray();

        return $this->render('grading/gradebook', ['title' => 'Gradebook · ' . $course['title'], 'active' => 'courses', 'tab' => 'gradebook', 'course' => $course, 'rows' => $rows]);
    }
}
