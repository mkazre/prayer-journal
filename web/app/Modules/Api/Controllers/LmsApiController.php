<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\HtmlSanitizer;
use App\Modules\Lms\Controllers\StudyController;
use App\Modules\Lms\Models\AssignmentModel;
use App\Modules\Lms\Models\AssignmentSubmissionModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\GradebookModel;
use App\Modules\Lms\Models\JournalEntryModel;
use App\Modules\Lms\Models\LessonModel;
use App\Modules\Lms\Models\QuizAttemptModel;
use App\Modules\Lms\Models\QuizModel;
use App\Modules\Lms\Services\Curriculum;
use App\Modules\Shop\Models\LessonUnlockModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Courses and the learner player for the mobile app. Access rules (drip,
 * sequential, paid lessons) are the same as on the website.
 */
class LmsApiController extends BaseApiController
{
    public function courses()
    {
        $rows = (new CourseModel())->catalog(['q' => (string) $this->request->getGet('q'), 'category' => (string) $this->request->getGet('category'), 'sort' => (string) ($this->request->getGet('sort') ?: 'popular')]);

        return $this->ok(array_map([$this, 'presentCourse'], $rows));
    }

    public function course(string $slug)
    {
        $model  = new CourseModel();
        $course = $model->where('slug', $slug)->where('status', 'published')->first();
        if (! $course) {
            return $this->fail('Course not found.', 404);
        }
        $user       = $this->optionalUser();
        $enrollment = $user ? (new EnrollmentModel())->findFor((int) $course['id'], (int) $user->id) : null;
        $sections   = Curriculum::annotate(Curriculum::load((int) $course['id']), $course, $enrollment, $user ? (int) $user->id : 0);

        return $this->ok($this->presentCourse($model->withStats($course)) + [
            'description'    => HtmlSanitizer::clean((string) $course['description']),
            'what_you_learn' => array_values(array_filter(array_map('trim', explode("\n", (string) $course['what_you_learn'])))),
            'requirements'   => array_values(array_filter(array_map('trim', explode("\n", (string) $course['requirements'])))),
            'author_name'    => $course['author_name'], 'author_bio' => $course['author_bio'],
            'enrolled'       => $enrollment !== null, 'progress' => $enrollment ? (int) $enrollment['progress_percent'] : 0,
            'sections'       => $this->presentSections($sections),
        ]);
    }

    public function enroll(string $slug)
    {
        $user   = $this->currentUser();
        $course = (new CourseModel())->where('slug', $slug)->where('status', 'published')->first();
        if (! $course) {
            return $this->fail('Course not found.', 404);
        }
        $existing = (new EnrollmentModel())->findFor((int) $course['id'], (int) $user->id);
        if (EnrollmentModel::isActive($existing) && $existing['source'] !== 'subscription') {
            return $this->ok(['enrolled' => true, 'progress' => (int) $existing['progress_percent']]);
        }
        if (\App\Modules\Lms\Services\CourseAccess::resolve($course, (int) $user->id)) {
            return $this->ok(['enrolled' => true, 'progress' => 0], 201);
        }
        if ((int) $course['price_cents'] > 0 || ($course['access_mode'] ?? 'free') !== 'free') {
            return $this->fail('This course needs a purchase or membership. Complete checkout on the website.', 402);
        }
        (new EnrollmentModel())->enroll((int) $course['id'], (int) $user->id, 'mobile');

        return $this->ok(['enrolled' => true, 'progress' => 0], 201);
    }

    public function myCourses()
    {
        return $this->ok(array_map(static fn ($e) => [
            'course_id' => (int) $e['course_id'], 'slug' => $e['slug'], 'title' => $e['title'], 'subtitle' => $e['subtitle'],
            'thumbnail_url' => $e['thumbnail_path'] ? base_url($e['thumbnail_path']) : null, 'progress' => (int) $e['progress_percent'],
            'status' => $e['status'], 'last_lesson_id' => $e['last_lesson_id'] ? (int) $e['last_lesson_id'] : null, 'last_lesson_title' => $e['last_lesson_title'],
        ], (new EnrollmentModel())->forUser((int) $this->currentUser()->id)));
    }

    public function curriculum(string $slug)
    {
        $ctx = $this->context($slug);
        if ($ctx instanceof ResponseInterface) {
            return $ctx;
        }
        [$course, $enrollment, $sections] = $ctx;

        return $this->ok([
            'course'   => $this->presentCourse($course),
            'progress' => (int) $enrollment['progress_percent'],
            'last_lesson_id' => $enrollment['last_lesson_id'] ? (int) $enrollment['last_lesson_id'] : null,
            'sections' => $this->presentSections($sections),
        ]);
    }

    public function lesson(string $slug, int $lessonId)
    {
        $ctx = $this->context($slug);
        if ($ctx instanceof ResponseInterface) {
            return $ctx;
        }
        [$course, $enrollment, $sections] = $ctx;
        $userId = (int) $this->currentUser()->id;
        $lesson = (new LessonModel())->find($lessonId);
        if (! $lesson || (int) $lesson['course_id'] !== (int) $course['id'] || $lesson['status'] !== 'published') {
            return $this->fail('Lesson not found.', 404);
        }
        if ((int) $lesson['price_cents'] > 0 && ! (new LessonUnlockModel())->hasUnlocked($userId, $lessonId)) {
            return $this->fail('This lesson is sold separately. Unlock it on the website.', 402);
        }
        [$item, $prev, $next] = $this->position($sections, 'lesson', $lessonId);
        if ($item && $item['locked']) {
            return $this->fail($item['reason'] ?: 'This lesson is not available yet.', 423);
        }
        (new EnrollmentModel())->update($enrollment['id'], ['last_lesson_id' => $lessonId, 'last_activity_at' => date('Y-m-d H:i:s')]);

        $settings = CourseModel::settings($course);
        $answers  = (new JournalEntryModel())->forLessonPrompts($userId, $lessonId);
        $prompts  = array_map(static fn ($p) => $p + ['answer' => $answers[$p['label']]['body'] ?? ''], $settings['journal'] ? LessonModel::prompts($lesson, $settings) : []);

        return $this->ok([
            'id' => (int) $lesson['id'], 'title' => $lesson['title'], 'type' => $lesson['type'] ?: 'text', 'summary' => $lesson['summary'],
            'content_html' => HtmlSanitizer::clean((string) $lesson['content']), 'video_url' => video_embed_url((string) $lesson['video_url']),
            'duration_mins' => (int) $lesson['duration_mins'], 'bible_reference' => $lesson['bible_reference'],
            'scripture' => StudyController::keyVerses($lesson['bible_reference']),
            'done' => (bool) ($item['done'] ?? false), 'section_title' => $item['section_title'] ?? null,
            'prev' => $prev ? ['kind' => $prev['kind'], 'id' => $prev['id'], 'title' => $prev['title']] : null,
            'next' => $next ? ['kind' => $next['kind'], 'id' => $next['id'], 'title' => $next['title'], 'locked' => (bool) $next['locked']] : null,
            'prompts' => $prompts,
        ]);
    }

    public function completeLesson(string $slug, int $lessonId)
    {
        $ctx = $this->context($slug);
        if ($ctx instanceof ResponseInterface) {
            return $ctx;
        }
        [$course, $enrollment] = $ctx;
        $lesson = (new LessonModel())->find($lessonId);
        if (! $lesson || (int) $lesson['course_id'] !== (int) $course['id']) {
            return $this->fail('Lesson not found.', 404);
        }
        $undo   = ! empty($this->body()['undo']);
        $model  = new EnrollmentModel();
        $result = $undo ? $model->markLessonIncomplete((int) $enrollment['id'], $lessonId) : $model->markLessonComplete((int) $enrollment['id'], $lessonId);

        return $this->ok(['done' => ! $undo, 'progress' => $result['percent'], 'complete' => $result['complete']]);
    }

    public function saveJournal(string $slug, int $lessonId)
    {
        $ctx = $this->context($slug);
        if ($ctx instanceof ResponseInterface) {
            return $ctx;
        }
        [$course] = $ctx;
        $userId = (int) $this->currentUser()->id;
        $lesson = (new LessonModel())->find($lessonId);
        if (! $lesson || (int) $lesson['course_id'] !== (int) $course['id']) {
            return $this->fail('Lesson not found.', 404);
        }
        $in     = $this->body();
        $prompt = null;
        foreach (LessonModel::prompts($lesson, CourseModel::settings($course)) as $p) {
            if ($p['key'] === ($in['prompt'] ?? null)) {
                $prompt = $p;
            }
        }
        if (! $prompt) {
            return $this->fail('Unknown prompt.', 422);
        }
        $body    = trim(mb_substr((string) ($in['body'] ?? ''), 0, 20000));
        $model   = new JournalEntryModel();
        $current = $model->where(['user_id' => $userId, 'lesson_id' => $lessonId, 'title' => $prompt['label']])->first();
        if ($body === '') {
            if ($current) {
                $model->delete($current['id']);
            }

            return $this->ok(['saved' => true, 'cleared' => true]);
        }
        $data = ['user_id' => $userId, 'course_id' => $course['id'], 'lesson_id' => $lessonId, 'kind' => $prompt['kind'], 'title' => $prompt['label'], 'body' => $body, 'scripture' => $lesson['bible_reference'], 'entry_date' => $current['entry_date'] ?? date('Y-m-d')];
        $current ? $model->update($current['id'], $data) : $model->insert($data);

        return $this->ok(['saved' => true]);
    }

    public function quiz(string $slug, int $quizId)
    {
        $ctx = $this->context($slug);
        if ($ctx instanceof ResponseInterface) {
            return $ctx;
        }
        [$course, , $sections] = $ctx;
        $quiz = (new QuizModel())->withQuestions($quizId);
        if (! $quiz || (int) $quiz['course_id'] !== (int) $course['id']) {
            return $this->fail('Quiz not found.', 404);
        }
        [$item] = $this->position($sections, 'quiz', $quizId);
        if ($item && $item['locked']) {
            return $this->fail($item['reason'] ?: 'This quiz is not available yet.', 423);
        }
        $attempts = new QuizAttemptModel();
        $used     = $attempts->attemptsUsed($quizId, (int) $this->currentUser()->id);

        return $this->ok([
            'id' => (int) $quiz['id'], 'title' => $quiz['title'], 'description' => $quiz['description'] ?? null,
            'pass_percent' => (int) $quiz['pass_percent'], 'time_limit_mins' => (int) $quiz['time_limit_mins'],
            'attempts_left' => (int) $quiz['max_attempts'] > 0 ? max(0, (int) $quiz['max_attempts'] - $used) : null,
            'best' => $attempts->best($quizId, (int) $this->currentUser()->id),
            // Correct answers are never sent to the device.
            'questions' => array_map(static fn ($q) => [
                'id' => (int) $q['id'], 'type' => $q['type'], 'prompt' => $q['prompt'] ?? $q['question'] ?? '', 'points' => (int) $q['points'],
                'options' => $q['type'] === 'short_text' ? [] : array_map(static fn ($o) => ['id' => (int) $o['id'], 'text' => $o['option_text'] ?? $o['text'] ?? ''], $q['options']),
            ], $quiz['questions']),
        ]);
    }

    public function submitQuiz(string $slug, int $quizId)
    {
        $ctx = $this->context($slug);
        if ($ctx instanceof ResponseInterface) {
            return $ctx;
        }
        [$course, $enrollment] = $ctx;
        $userId = (int) $this->currentUser()->id;
        $quiz   = (new QuizModel())->find($quizId);
        if (! $quiz || (int) $quiz['course_id'] !== (int) $course['id']) {
            return $this->fail('Quiz not found.', 404);
        }
        $attempts = new QuizAttemptModel();
        if ((int) $quiz['max_attempts'] > 0 && $attempts->attemptsUsed($quizId, $userId) >= (int) $quiz['max_attempts']) {
            return $this->fail('No attempts left for this quiz.', 409);
        }
        $result = $attempts->submit($quizId, $userId, (array) ($this->body()['answers'] ?? []), (int) $quiz['pass_percent']);
        (new GradebookModel())->recalculate((int) $course['id'], $userId);
        $progress = Curriculum::recalculate((int) $enrollment['id']);
        $notes    = array_column(db_connect()->table('quiz_questions')->select('id, explanation')->where('quiz_id', $quizId)->get()->getResultArray(), 'explanation', 'id');
        foreach ($result['review'] as $qid => &$r) {
            $r['explanation'] = $notes[$qid] ?? null;
        }
        unset($r);

        return $this->ok($result + ['progress' => $progress['percent']]);
    }

    public function assignments(string $slug)
    {
        $ctx = $this->context($slug);
        if ($ctx instanceof ResponseInterface) {
            return $ctx;
        }

        return $this->ok((new AssignmentModel())->where('course_id', $ctx[0]['id'])->orderBy('position')->findAll());
    }

    public function submitAssignment(string $slug, int $assignmentId)
    {
        $ctx = $this->context($slug);
        if ($ctx instanceof ResponseInterface) {
            return $ctx;
        }
        $assignment = (new AssignmentModel())->find($assignmentId);
        if (! $assignment || (int) $assignment['course_id'] !== (int) $ctx[0]['id']) {
            return $this->fail('Assignment not found.', 404);
        }
        $content = trim((string) ($this->body()['content'] ?? ''));
        if ($content === '') {
            return $this->fail('Write your answer first.', 422);
        }
        $submission = (new AssignmentSubmissionModel())->submit($assignmentId, (int) $this->currentUser()->id, HtmlSanitizer::clean(nl2br(esc($content))));
        Curriculum::recalculate((int) $ctx[1]['id']);
        \App\Libraries\Notify::assignmentSubmitted($ctx[0], (string) $assignment['title'], (int) $this->currentUser()->id);

        return $this->ok($submission, 201);
    }

    /**
     * @return array{0:array,1:array,2:array}|ResponseInterface [course, enrollment, annotated sections]
     */
    private function context(string $slug)
    {
        $course = (new CourseModel())->where('slug', $slug)->first();
        if (! $course) {
            return $this->fail('Course not found.', 404);
        }
        $userId     = (int) $this->currentUser()->id;
        $enrollment = \App\Modules\Lms\Services\CourseAccess::resolve($course, $userId);
        if (! $enrollment) {
            return $this->fail(\App\Modules\Lms\Services\CourseAccess::lapsed($course, $userId) ? 'Your membership is not active. Renew on the website to continue — your progress is saved.' : 'Enroll in this course first.', 403);
        }

        return [$course, $enrollment, Curriculum::annotate(Curriculum::load((int) $course['id']), $course, $enrollment, $userId)];
    }

    /**
     * @return array{0:?array,1:?array,2:?array} [item, previous, next]
     */
    private function position(array $sections, string $kind, int $id): array
    {
        $flat = Curriculum::flatten($sections);
        foreach ($flat as $i => $item) {
            if ($item['kind'] === $kind && $item['id'] === $id) {
                return [$item, $flat[$i - 1] ?? null, $flat[$i + 1] ?? null];
            }
        }

        return [null, null, null];
    }

    private function presentSections(array $sections): array
    {
        return array_map(static fn ($s) => [
            'id' => $s['id'], 'title' => $s['title'], 'summary' => $s['summary'], 'done_count' => $s['done_count'] ?? 0,
            'items' => array_map(static fn ($i) => [
                'kind' => $i['kind'], 'id' => $i['id'], 'title' => $i['title'], 'type' => $i['type'], 'duration' => $i['duration'],
                'is_preview' => $i['is_preview'], 'done' => $i['done'] ?? false, 'locked' => $i['locked'] ?? false, 'reason' => $i['reason'] ?? null,
            ], $s['items']),
        ], $sections);
    }

    private function presentCourse(array $c): array
    {
        return [
            'id' => (int) $c['id'], 'slug' => $c['slug'], 'title' => $c['title'], 'subtitle' => $c['subtitle'] ?? null, 'summary' => $c['summary'] ?? null,
            'thumbnail_url' => ! empty($c['thumbnail_path']) ? base_url($c['thumbnail_path']) : null,
            'level' => $c['level'] ?? null, 'category' => $c['category_name'] ?? null,
            'price_cents' => (int) ($c['price_cents'] ?? 0), 'currency' => $c['currency'] ?? store_currency(), 'access_mode' => $c['access_mode'] ?? 'free',
            'lesson_count' => (int) ($c['lesson_count'] ?? 0), 'total_minutes' => (int) ($c['total_minutes'] ?? 0),
            'student_count' => (int) ($c['student_count'] ?? 0), 'rating' => isset($c['avg_rating']) ? round((float) $c['avg_rating'], 1) : null,
        ];
    }
}
