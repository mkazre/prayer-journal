<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\AssignmentModel;
use App\Modules\Lms\Models\AssignmentSubmissionModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\GradebookModel;
use App\Modules\Lms\Models\LessonModel;
use App\Modules\Lms\Models\QuizAttemptModel;
use App\Modules\Lms\Models\QuizModel;

class StudyController extends BaseController
{
    public function show(string $slug)
    {
        $course     = $this->requireEnrolledCourse($slug);
        $lessons    = (new LessonModel())->forCourse($course['id']);
        $enrollment = $this->enrollment($course['id']);
        $completed  = array_column((new EnrollmentModel())->completedLessonIds($enrollment['id']), 'lesson_id');
        $quizzes    = (new QuizModel())->where('course_id', $course['id'])->findAll();
        $assignments = (new AssignmentModel())->where('course_id', $course['id'])->findAll();

        return view('Lms/study/course', [
            'title'       => $course['title'],
            'course'      => $course,
            'lessons'     => $lessons,
            'completed'   => $completed,
            'enrollment'  => $enrollment,
            'quizzes'     => $quizzes,
            'assignments' => $assignments,
        ]);
    }

    public function lesson(string $slug, int $lessonId)
    {
        $course     = $this->requireEnrolledCourse($slug);
        $lesson     = (new LessonModel())->find($lessonId);
        if (! $lesson || (int) $lesson['course_id'] !== $course['id']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ((int) $lesson['price_cents'] > 0 && ! (new \App\Modules\Shop\Models\LessonUnlockModel())->hasUnlocked(auth()->id(), $lessonId)) {
            return redirect()->to(site_url('checkout/lesson/' . $lessonId));
        }

        // Continue-learning: remember the last lesson this student opened.
        (new EnrollmentModel())->update($this->enrollment($course['id'])['id'], ['last_lesson_id' => $lessonId]);

        return view('Lms/study/lesson', ['title' => $lesson['title'], 'course' => $course, 'lesson' => $lesson]);
    }

    public function completeLesson(string $slug, int $lessonId)
    {
        $course     = $this->requireEnrolledCourse($slug);
        $enrollment = $this->enrollment($course['id']);
        $total      = count((new LessonModel())->forCourse($course['id']));

        (new EnrollmentModel())->markLessonComplete($enrollment['id'], $lessonId, $total);

        return redirect()->to(site_url('study/' . $slug))->with('message', 'Lesson marked complete.');
    }

    public function quiz(string $slug, int $quizId)
    {
        $course = $this->requireEnrolledCourse($slug);
        $quiz   = (new QuizModel())->withQuestions($quizId);
        if (! $quiz || (int) $quiz['course_id'] !== $course['id']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $attemptModel = new QuizAttemptModel();
        $used         = $attemptModel->attemptsUsed($quizId, auth()->id());
        if ($quiz['max_attempts'] > 0 && $used >= $quiz['max_attempts']) {
            return view('Lms/study/quiz_locked', ['title' => $quiz['title'], 'course' => $course, 'quiz' => $quiz]);
        }

        if ($this->request->getMethod() === 'POST') {
            $answers = array_map(static fn ($v) => $v === '' ? null : (int) $v, $this->request->getPost('answer') ?? []);
            $result  = $attemptModel->submit($quizId, auth()->id(), $answers, (int) $quiz['pass_percent']);

            (new GradebookModel())->recalculate($course['id'], auth()->id());

            return view('Lms/study/quiz_result', ['title' => $quiz['title'], 'course' => $course, 'quiz' => $quiz, 'result' => $result]);
        }

        return view('Lms/study/quiz', ['title' => $quiz['title'], 'course' => $course, 'quiz' => $quiz]);
    }

    public function assignment(string $slug, int $assignmentId)
    {
        $course     = $this->requireEnrolledCourse($slug);
        $assignment = (new AssignmentModel())->find($assignmentId);
        if (! $assignment || (int) $assignment['course_id'] !== $course['id']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $submissionModel = new AssignmentSubmissionModel();
        $submission      = $submissionModel->where(['assignment_id' => $assignmentId, 'user_id' => auth()->id()])->first();

        if ($this->request->getMethod() === 'POST') {
            $submissionModel->submit($assignmentId, auth()->id(), (string) $this->request->getPost('content'));

            return redirect()->to(site_url('study/' . $slug . '/assignment/' . $assignmentId))->with('message', 'Submission received.');
        }

        return view('Lms/study/assignment', ['title' => $assignment['title'], 'course' => $course, 'assignment' => $assignment, 'submission' => $submission]);
    }

    protected function requireEnrolledCourse(string $slug): array
    {
        $course     = (new CourseModel())->where('slug', $slug)->first();
        if (! $course) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $enrollmentModel = new EnrollmentModel();
        $enrolled        = (bool) $enrollmentModel->where(['course_id' => $course['id'], 'user_id' => auth()->id()])->first();

        if (! $enrolled && $course['access_mode'] === 'subscription'
            && (new \App\Modules\Shop\Models\UserSubscriptionModel())->hasActive(auth()->id())) {
            $enrollmentModel->enroll($course['id'], auth()->id());
            $enrolled = true;
        }

        if (! $enrolled) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $course;
    }

    protected function enrollment(int $courseId): array
    {
        return (new EnrollmentModel())->where(['course_id' => $courseId, 'user_id' => auth()->id()])->first();
    }
}
