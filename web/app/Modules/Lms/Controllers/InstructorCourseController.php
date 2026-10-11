<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\AssignmentModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\LessonModel;
use App\Modules\Lms\Models\QuizModel;

class InstructorCourseController extends BaseController
{
    protected CourseModel $courses;
    protected LessonModel $lessons;
    protected QuizModel $quizzes;
    protected AssignmentModel $assignments;

    public function __construct()
    {
        $this->courses     = new CourseModel();
        $this->lessons     = new LessonModel();
        $this->quizzes     = new QuizModel();
        $this->assignments = new AssignmentModel();
    }

    public function index(): string
    {
        $userId  = auth()->id();
        $courses = $this->courses->where('created_by', $userId)->orderBy('created_at', 'desc')->findAll();

        return view('Lms/instructor/courses_index', ['title' => 'My Courses', 'courses' => $courses]);
    }

    public function create()
    {
        if ($this->request->getMethod() === 'POST') {
            $data = [
                'title'       => $this->request->getPost('title'),
                'slug'        => url_title($this->request->getPost('title'), '-', true),
                'summary'     => $this->request->getPost('summary'),
                'description' => $this->request->getPost('description'),
                'price_cents' => (int) round((float) $this->request->getPost('price') * 100),
                'status'      => 'draft',
                'created_by'  => auth()->id(),
            ];

            if (! $this->courses->save($data)) {
                return view('Lms/instructor/course_form', ['title' => 'New Course', 'errors' => $this->courses->errors(), 'course' => $data]);
            }

            return redirect()->to(site_url('instructor/courses'))->with('message', 'Course created.');
        }

        return view('Lms/instructor/course_form', ['title' => 'New Course', 'course' => null]);
    }

    public function edit(int $id)
    {
        $course = $this->requireOwnedCourse($id);

        if ($this->request->getMethod() === 'POST') {
            $data = [
                'title'       => $this->request->getPost('title'),
                'summary'     => $this->request->getPost('summary'),
                'description' => $this->request->getPost('description'),
                'price_cents' => (int) round((float) $this->request->getPost('price') * 100),
                'status'      => $this->request->getPost('status') ?: $course['status'],
            ];
            $this->courses->update($id, $data);

            return redirect()->to(site_url('instructor/courses/' . $id . '/edit'))->with('message', 'Course updated.');
        }

        $course['lessons'] = $this->lessons->forCourse($id, false);
        $course['quizzes'] = $this->quizzes->where('course_id', $id)->findAll();

        return view('Lms/instructor/course_form', ['title' => 'Edit Course', 'course' => $course]);
    }

    public function addLesson(int $courseId)
    {
        $this->requireOwnedCourse($courseId);

        if ($this->request->getMethod() === 'POST') {
            $this->lessons->insert([
                'course_id'       => $courseId,
                'title'           => $this->request->getPost('title'),
                'content'         => $this->request->getPost('content'),
                'video_url'       => $this->request->getPost('video_url'),
                'bible_reference' => $this->request->getPost('bible_reference'),
                'position'        => (int) $this->request->getPost('position'),
                'status'          => 'published',
            ]);

            return redirect()->to(site_url('instructor/courses/' . $courseId . '/edit'))->with('message', 'Lesson added.');
        }

        return view('Lms/instructor/lesson_form', ['title' => 'New Lesson', 'courseId' => $courseId, 'lesson' => null]);
    }

    public function addQuiz(int $courseId)
    {
        $this->requireOwnedCourse($courseId);

        if ($this->request->getMethod() === 'POST') {
            $quizId = $this->quizzes->insert([
                'course_id'    => $courseId,
                'title'        => $this->request->getPost('title'),
                'instructions' => $this->request->getPost('instructions'),
                'pass_percent' => (int) $this->request->getPost('pass_percent') ?: 70,
            ], true);

            $questions = $this->request->getPost('questions') ?? [];
            foreach ($questions as $q) {
                if (empty($q['question'])) {
                    continue;
                }
                $questionId = $this->quizzes->db->table('quiz_questions')->insert([
                    'quiz_id'  => $quizId,
                    'question'=> $q['question'],
                    'points'  => (int) ($q['points'] ?? 1),
                ]) ? $this->quizzes->db->insertID() : null;

                foreach (($q['options'] ?? []) as $i => $optionText) {
                    if ($optionText === '') {
                        continue;
                    }
                    $this->quizzes->db->table('quiz_options')->insert([
                        'question_id' => $questionId,
                        'option_text' => $optionText,
                        'is_correct'  => (isset($q['correct']) && (int) $q['correct'] === (int) $i) ? 1 : 0,
                        'position'    => $i,
                    ]);
                }
            }

            return redirect()->to(site_url('instructor/courses/' . $courseId . '/edit'))->with('message', 'Quiz added.');
        }

        return view('Lms/instructor/quiz_form', ['title' => 'New Quiz', 'courseId' => $courseId]);
    }

    protected function requireOwnedCourse(int $courseId): array
    {
        $course = $this->courses->find($courseId);
        if (! $course || ! $this->courses->isInstructor($courseId, auth()->id())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $course;
    }
}
