<?php

namespace App\Modules\Api\Controllers;

use App\Modules\Lms\Models\AssignmentModel;
use App\Modules\Lms\Models\AssignmentSubmissionModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\GradebookModel;
use App\Modules\Lms\Models\LessonModel;
use App\Modules\Lms\Models\QuizAttemptModel;
use App\Modules\Lms\Models\QuizModel;
use App\Modules\Shop\Models\LessonUnlockModel;

class LmsApiController extends BaseApiController
{
    public function courses()
    {
        return $this->ok((new CourseModel())->published()->orderBy('created_at', 'desc')->findAll());
    }

    public function course(string $slug)
    {
        $course = (new CourseModel())->where('slug', $slug)->first();
        if (! $course) {
            return $this->fail('Course not found.', 404);
        }
        $course['lessons'] = (new LessonModel())->forCourse($course['id']);

        return $this->ok($course);
    }

    public function enroll(string $slug)
    {
        $user   = $this->currentUser();
        $course = (new CourseModel())->where('slug', $slug)->first();
        if (! $course) {
            return $this->fail('Course not found.', 404);
        }
        if ($course['price_cents'] > 0) {
            return $this->fail('This course requires payment - use the checkout endpoints.', 402);
        }
        $enrollment = (new EnrollmentModel())->enroll($course['id'], $user->id);

        return $this->ok($enrollment, 201);
    }

    public function myCourses()
    {
        return $this->ok((new EnrollmentModel())->forUser($this->currentUser()->id));
    }

    public function lesson(string $slug, int $lessonId)
    {
        $user   = $this->currentUser();
        $course = $this->requireEnrolled($slug, $user->id);
        if ($course instanceof \CodeIgniter\HTTP\Response) {
            return $course;
        }

        $lesson = (new LessonModel())->find($lessonId);
        if (! $lesson || (int) $lesson['course_id'] !== $course['id']) {
            return $this->fail('Lesson not found.', 404);
        }
        if ((int) $lesson['price_cents'] > 0 && ! (new LessonUnlockModel())->hasUnlocked($user->id, $lessonId)) {
            return $this->fail('This lesson requires payment - use the checkout endpoints.', 402);
        }

        $enrollment = (new EnrollmentModel())->where(['course_id' => $course['id'], 'user_id' => $user->id])->first();
        (new EnrollmentModel())->update($enrollment['id'], ['last_lesson_id' => $lessonId]);

        return $this->ok($lesson);
    }

    public function completeLesson(string $slug, int $lessonId)
    {
        $user       = $this->currentUser();
        $course     = $this->requireEnrolled($slug, $user->id);
        if ($course instanceof \CodeIgniter\HTTP\Response) {
            return $course;
        }
        $enrollment = (new EnrollmentModel())->where(['course_id' => $course['id'], 'user_id' => $user->id])->first();
        $total      = count((new LessonModel())->forCourse($course['id']));
        (new EnrollmentModel())->markLessonComplete($enrollment['id'], $lessonId, $total);

        return $this->ok(['message' => 'Lesson marked complete.']);
    }

    public function quiz(string $slug, int $quizId)
    {
        $user   = $this->currentUser();
        $course = $this->requireEnrolled($slug, $user->id);
        if ($course instanceof \CodeIgniter\HTTP\Response) {
            return $course;
        }
        $quiz = (new QuizModel())->withQuestions($quizId);
        if (! $quiz || (int) $quiz['course_id'] !== $course['id']) {
            return $this->fail('Quiz not found.', 404);
        }
        // Strip correct-answer flags before sending to the client.
        foreach ($quiz['questions'] as &$q) {
            foreach ($q['options'] as &$o) {
                unset($o['is_correct']);
            }
        }

        return $this->ok($quiz);
    }

    public function submitQuiz(string $slug, int $quizId)
    {
        $user   = $this->currentUser();
        $course = $this->requireEnrolled($slug, $user->id);
        if ($course instanceof \CodeIgniter\HTTP\Response) {
            return $course;
        }
        $quiz = (new QuizModel())->find($quizId);
        if (! $quiz || (int) $quiz['course_id'] !== $course['id']) {
            return $this->fail('Quiz not found.', 404);
        }

        $answers = $this->request->getJSON(true)['answers'] ?? [];
        $result  = (new QuizAttemptModel())->submit($quizId, $user->id, $answers, (int) $quiz['pass_percent']);
        (new GradebookModel())->recalculate($course['id'], $user->id);

        return $this->ok($result);
    }

    public function assignments(string $slug)
    {
        $user   = $this->currentUser();
        $course = $this->requireEnrolled($slug, $user->id);
        if ($course instanceof \CodeIgniter\HTTP\Response) {
            return $course;
        }

        return $this->ok((new AssignmentModel())->where('course_id', $course['id'])->findAll());
    }

    public function submitAssignment(string $slug, int $assignmentId)
    {
        $user   = $this->currentUser();
        $course = $this->requireEnrolled($slug, $user->id);
        if ($course instanceof \CodeIgniter\HTTP\Response) {
            return $course;
        }
        $content    = $this->request->getJSON(true)['content'] ?? '';
        $submission = (new AssignmentSubmissionModel())->submit($assignmentId, $user->id, $content);

        return $this->ok($submission, 201);
    }

    private function requireEnrolled(string $slug, int $userId)
    {
        $course = (new CourseModel())->where('slug', $slug)->first();
        if (! $course) {
            return $this->fail('Course not found.', 404);
        }
        if (! (new EnrollmentModel())->where(['course_id' => $course['id'], 'user_id' => $userId])->first()) {
            return $this->fail('Not enrolled in this course.', 403);
        }

        return $course;
    }
}
