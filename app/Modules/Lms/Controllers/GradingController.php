<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\AssignmentModel;
use App\Modules\Lms\Models\AssignmentSubmissionModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\GradebookModel;

class GradingController extends BaseController
{
    public function gradebook(int $courseId): string
    {
        $course = $this->requireOwnedCourse($courseId);
        $rows   = (new GradebookModel())->db->table('gradebook_entries')
            ->select('gradebook_entries.*, users.username')
            ->join('users', 'users.id = gradebook_entries.user_id')
            ->where('gradebook_entries.course_id', $courseId)
            ->get()->getResultArray();

        return view('Lms/instructor/gradebook', ['title' => 'Gradebook: ' . $course['title'], 'course' => $course, 'rows' => $rows]);
    }

    public function submissions(int $assignmentId): string
    {
        $assignment = (new AssignmentModel())->find($assignmentId);
        $course     = $this->requireOwnedCourse($assignment['course_id']);

        $submissions = (new AssignmentSubmissionModel())->db->table('assignment_submissions')
            ->select('assignment_submissions.*, users.username')
            ->join('users', 'users.id = assignment_submissions.user_id')
            ->where('assignment_id', $assignmentId)
            ->get()->getResultArray();

        return view('Lms/instructor/submissions', ['title' => 'Submissions: ' . $assignment['title'], 'course' => $course, 'assignment' => $assignment, 'submissions' => $submissions]);
    }

    public function grade(int $submissionId)
    {
        $submissionModel = new AssignmentSubmissionModel();
        $submission      = $submissionModel->find($submissionId);
        $assignment      = (new AssignmentModel())->find($submission['assignment_id']);
        $course          = $this->requireOwnedCourse($assignment['course_id']);

        $submissionModel->grade($submissionId, auth()->id(), (int) $this->request->getPost('points'), (string) $this->request->getPost('feedback'));
        (new GradebookModel())->recalculate($course['id'], (int) $submission['user_id']);

        return redirect()->to(site_url('instructor/assignments/' . $assignment['id'] . '/submissions'))->with('message', 'Graded.');
    }

    protected function requireOwnedCourse(int $courseId): array
    {
        $course = (new CourseModel())->find($courseId);
        if (! $course || ! (new CourseModel())->isInstructor($courseId, auth()->id())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $course;
    }
}
