<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Scripture;
use App\Libraries\Uploads;
use App\Modules\Lms\Models\AssignmentModel;
use App\Modules\Lms\Models\AssignmentSubmissionModel;
use App\Modules\Lms\Models\CourseAnnouncementModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\GradebookModel;
use App\Modules\Lms\Models\JournalEntryModel;
use App\Modules\Lms\Models\LessonModel;
use App\Modules\Lms\Models\LessonNoteModel;
use App\Modules\Lms\Models\QuizAttemptModel;
use App\Modules\Lms\Models\QuizModel;
use App\Modules\Lms\Services\Curriculum;
use App\Modules\Shop\Models\LessonUnlockModel;
use App\Modules\Shop\Models\UserSubscriptionModel;

/**
 * The learner's course player.
 */
class StudyController extends BaseController
{
    public function myLearning(): string
    {
        $enrollments = (new EnrollmentModel())->forUser($this->userId());
        $certs       = db_connect()->table('certificates')->select('course_id, certificate_code')->where('user_id', $this->userId())->get()->getResultArray();

        return view('Lms/study/my_learning', [
            'title'        => 'My learning',
            'inProgress'   => array_values(array_filter($enrollments, static fn ($e) => $e['status'] !== 'completed')),
            'completed'    => array_values(array_filter($enrollments, static fn ($e) => $e['status'] === 'completed')),
            'certificates' => array_column($certs, 'certificate_code', 'course_id'),
        ]);
    }

    public function show(string $slug)
    {
        [$course, $enrollment, $preview] = $this->access($slug);
        $sections = Curriculum::annotate(Curriculum::load((int) $course['id']), $course, $enrollment ?? $this->previewEnrollment($course), $this->userId());
        $flat     = Curriculum::flatten($sections);
        $next     = null;
        foreach ($flat as $item) {
            if (! $item['done'] && ! $item['locked']) {
                $next = $item;
                break;
            }
        }
        $cert = db_connect()->table('certificates')->where(['course_id' => $course['id'], 'user_id' => $this->userId()])->get()->getRowArray();

        return view('Lms/study/course', [
            'title'         => $course['title'],
            'course'        => $course,
            'settings'      => CourseModel::settings($course),
            'enrollment'    => $enrollment,
            'preview'       => $preview,
            'sections'      => $sections,
            'next'          => $next,
            'certificate'   => $cert,
            'announcements' => (new CourseAnnouncementModel())->forCourse((int) $course['id'], 5),
            'noteCount'     => count((new LessonNoteModel())->forCourse($this->userId(), (int) $course['id'])),
            'journalCount'  => db_connect()->table('journal_entries')->where(['user_id' => $this->userId(), 'course_id' => $course['id']])->countAllResults(),
            'current'       => null,
        ]);
    }

    public function lesson(string $slug, int $lessonId)
    {
        [$course, $enrollment, $preview] = $this->access($slug);
        $lesson = (new LessonModel())->find($lessonId);
        if (! $lesson || (int) $lesson['course_id'] !== (int) $course['id'] || ($lesson['status'] !== 'published' && ! $preview)) {
            $this->notFound();
        }
        if ((int) $lesson['price_cents'] > 0 && ! $preview && ! (new LessonUnlockModel())->hasUnlocked($this->userId(), $lessonId)) {
            return redirect()->to(site_url('checkout/lesson/' . $lessonId));
        }

        $sections = Curriculum::annotate(Curriculum::load((int) $course['id'], ! $preview), $course, $enrollment ?? $this->previewEnrollment($course), $this->userId());
        [$current, $prev, $next] = $this->position($sections, 'lesson', $lessonId);
        if ($current && $current['locked'] && ! $preview) {
            return redirect()->to(site_url('study/' . $slug))->with('info', $current['reason'] ?: 'This lesson is not available yet.');
        }

        if ($enrollment) {
            (new EnrollmentModel())->update($enrollment['id'], ['last_lesson_id' => $lessonId, 'last_activity_at' => date('Y-m-d H:i:s')]);
        }
        $settings = CourseModel::settings($course);

        return view('Lms/study/lesson', [
            'title'      => $lesson['title'],
            'course'     => $course,
            'settings'   => $settings,
            'lesson'     => $lesson,
            'enrollment' => $enrollment,
            'preview'    => $preview,
            'sections'   => $sections,
            'current'    => $current,
            'prev'       => $prev,
            'next'       => $next,
            'verses'     => self::keyVerses($lesson['bible_reference']),
            'prompts'    => $settings['journal'] ? LessonModel::prompts($lesson, $settings) : [],
            'answers'    => (new JournalEntryModel())->forLessonPrompts($this->userId(), $lessonId),
            'note'       => (new LessonNoteModel())->forLesson($this->userId(), $lessonId),
            'threads'    => $settings['discussions'] ? $this->threads($lessonId) : [],
            'resources'  => LessonModel::settings($lesson)['resources'],
        ]);
    }

    public function completeLesson(string $slug, int $lessonId)
    {
        [$course, $enrollment] = $this->access($slug, true);
        $lesson                = (new LessonModel())->find($lessonId);
        if (! $lesson || (int) $lesson['course_id'] !== (int) $course['id']) {
            $this->notFound();
        }
        $model  = new EnrollmentModel();
        $isDone = (bool) db_connect()->table('lesson_completions')->where(['enrollment_id' => $enrollment['id'], 'lesson_id' => $lessonId])->countAllResults();
        $undo   = $isDone && $this->request->getPost('undo');
        $result = $undo ? $model->markLessonIncomplete((int) $enrollment['id'], $lessonId) : $model->markLessonComplete((int) $enrollment['id'], $lessonId);

        if ($this->wantsJson()) {
            return $this->response->setJSON(['ok' => true, 'done' => ! $undo, 'progress' => $result, 'csrf' => csrf_hash()]);
        }
        if ($result['complete'] && ! $undo) {
            return redirect()->to(site_url('study/' . $slug))->with('message', 'Course complete — well done!');
        }
        $to = (string) $this->request->getPost('next');

        return redirect()->to($to !== '' && str_starts_with($to, site_url()) ? $to : site_url('study/' . $slug . '/lesson/' . $lessonId))
            ->with('message', $undo ? 'Marked as not complete.' : 'Lesson complete.');
    }

    public function saveNote(string $slug, int $lessonId)
    {
        [$course] = $this->access($slug);
        $this->assertLesson($course, $lessonId);
        (new LessonNoteModel())->saveFor($this->userId(), $lessonId, mb_substr((string) ($this->input()['body'] ?? ''), 0, 20000));

        return $this->done('Note saved.');
    }

    /**
     * Autosaves a journaling prompt answer into the learner's journal.
     */
    public function saveJournal(string $slug, int $lessonId)
    {
        [$course] = $this->access($slug);
        $lesson   = $this->assertLesson($course, $lessonId);
        $in       = $this->input();
        $prompts  = LessonModel::prompts($lesson, CourseModel::settings($course));
        $prompt   = null;
        foreach ($prompts as $p) {
            if ($p['key'] === ($in['prompt'] ?? null)) {
                $prompt = $p;
            }
        }
        if (! $prompt) {
            return $this->failed('Unknown prompt.');
        }
        $body    = trim(mb_substr((string) ($in['body'] ?? ''), 0, 20000));
        $model   = new JournalEntryModel();
        $current = $model->where(['user_id' => $this->userId(), 'lesson_id' => $lessonId, 'title' => $prompt['label']])->first();
        if ($body === '') {
            if ($current) {
                $model->delete($current['id']);
            }

            return $this->done('Cleared.');
        }
        $data = [
            'user_id'    => $this->userId(),
            'course_id'  => $course['id'],
            'lesson_id'  => $lessonId,
            'kind'       => $prompt['kind'],
            'title'      => $prompt['label'],
            'body'       => $body,
            'scripture'  => $lesson['bible_reference'],
            'entry_date' => $current['entry_date'] ?? date('Y-m-d'),
        ];
        if ($current) {
            $model->update($current['id'], $data);
            $id = (int) $current['id'];
        } else {
            $id = (int) $model->insert($data, true);
        }

        return $this->done('Saved to your journal.', ['id' => $id]);
    }

    public function discuss(string $slug, int $lessonId)
    {
        [$course] = $this->access($slug);
        $this->assertLesson($course, $lessonId);
        if (! CourseModel::settings($course)['discussions']) {
            return $this->failed('Discussion is turned off for this course.');
        }
        $body = trim(mb_substr((string) $this->request->getPost('body'), 0, 5000));
        if ($body === '') {
            return $this->failed('Write a comment first.');
        }
        $parent = (int) $this->request->getPost('parent_id') ?: null;
        if ($parent && ! db_connect()->table('course_qa_threads')->where(['id' => $parent, 'lesson_id' => $lessonId])->countAllResults()) {
            $parent = null;
        }
        db_connect()->table('course_qa_threads')->insert([
            'lesson_id'  => $lessonId,
            'user_id'    => $this->userId(),
            'parent_id'  => $parent,
            'body'       => $body,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($parent) {
            $author = (int) db_connect()->table('course_qa_threads')->where('id', $parent)->get()->getRow('user_id');
            if ($author && $author !== $this->userId()) {
                $lesson = (new LessonModel())->find($lessonId);
                \App\Libraries\Notify::discussionReply($author, display_name(), (string) ($lesson['title'] ?? 'a lesson'), site_url('study/' . $slug . '/lesson/' . $lessonId) . '#discussion');
            }
        }

        return redirect()->to(site_url('study/' . $slug . '/lesson/' . $lessonId) . '#discussion')->with('message', 'Comment posted.');
    }

    public function quiz(string $slug, int $quizId)
    {
        [$course, $enrollment, $preview] = $this->access($slug);
        $quiz = (new QuizModel())->withQuestions($quizId);
        if (! $quiz || (int) $quiz['course_id'] !== (int) $course['id']) {
            $this->notFound();
        }
        $sections = Curriculum::annotate(Curriculum::load((int) $course['id'], ! $preview), $course, $enrollment ?? $this->previewEnrollment($course), $this->userId());
        [$current, $prev, $next] = $this->position($sections, 'quiz', $quizId);
        if ($current && $current['locked'] && ! $preview) {
            return redirect()->to(site_url('study/' . $slug))->with('info', $current['reason']);
        }

        $attempts = new QuizAttemptModel();
        $used     = $attempts->attemptsUsed($quizId, $this->userId());
        $history  = $attempts->where(['quiz_id' => $quizId, 'user_id' => $this->userId()])->orderBy('id', 'DESC')->findAll(10);
        $base     = ['course' => $course, 'quiz' => $quiz, 'sections' => $sections, 'current' => $current, 'prev' => $prev, 'next' => $next,
            'enrollment' => $enrollment, 'preview' => $preview, 'settings' => CourseModel::settings($course), 'history' => $history, 'title' => $quiz['title']];

        $sessionKey = 'quiz_started_' . $quizId;
        if ($this->request->getMethod() === 'POST') {
            if ((int) $quiz['max_attempts'] > 0 && $used >= (int) $quiz['max_attempts']) {
                return redirect()->to(site_url('study/' . $slug . '/quiz/' . $quizId))->with('error', 'No attempts left.');
            }
            $answers = (array) ($this->request->getPost('answer') ?? []);
            $result  = $attempts->submit($quizId, $this->userId(), $answers, (int) $quiz['pass_percent'], session($sessionKey));
            session()->remove($sessionKey);
            (new GradebookModel())->recalculate((int) $course['id'], $this->userId());
            if ($enrollment) {
                Curriculum::recalculate((int) $enrollment['id']);
            }

            return view('Lms/study/quiz_result', $base + ['result' => $result, 'answers' => $answers, 'attemptsLeft' => (int) $quiz['max_attempts'] > 0 ? max(0, (int) $quiz['max_attempts'] - $used - 1) : null]);
        }

        if ((int) $quiz['max_attempts'] > 0 && $used >= (int) $quiz['max_attempts']) {
            return view('Lms/study/quiz_locked', $base);
        }
        if (! session($sessionKey) || $this->request->getGet('restart')) {
            session()->set($sessionKey, date('Y-m-d H:i:s'));
        }
        if ((int) $quiz['shuffle_questions']) {
            mt_srand(crc32(session_id() . $quizId));
            shuffle($quiz['questions']);
            mt_srand();
            $base['quiz'] = $quiz;
        }
        $elapsed = time() - strtotime((string) session($sessionKey));

        return view('Lms/study/quiz', $base + [
            'used'        => $used,
            'best'        => $attempts->best($quizId, $this->userId()),
            'secondsLeft' => $quiz['time_limit_mins'] ? max(0, (int) $quiz['time_limit_mins'] * 60 - $elapsed) : null,
            'start'       => (bool) $this->request->getGet('start') || $history === [],
        ]);
    }

    public function assignment(string $slug, int $assignmentId)
    {
        [$course, $enrollment, $preview] = $this->access($slug);
        $assignment = (new AssignmentModel())->find($assignmentId);
        if (! $assignment || (int) $assignment['course_id'] !== (int) $course['id']) {
            $this->notFound();
        }
        $submissions = new AssignmentSubmissionModel();
        $submission  = $submissions->where(['assignment_id' => $assignmentId, 'user_id' => $this->userId()])->first();

        if ($this->request->getMethod() === 'POST') {
            if (! $enrollment) {
                return redirect()->back()->with('error', 'Previewing — submissions are for enrolled learners.');
            }
            if ($submission && $submission['status'] === 'graded') {
                return redirect()->back()->with('error', 'This submission has already been graded.');
            }
            $content = trim((string) $this->request->getPost('content'));
            $file    = null;
            if ((int) $assignment['allow_file']) {
                try {
                    $file = Uploads::file($this->request->getFile('attachment'), 'submissions', 20480, true);
                } catch (\RuntimeException $e) {
                    return redirect()->back()->withInput()->with('error', $e->getMessage());
                }
            }
            if ($content === '' && ! $file) {
                return redirect()->back()->with('error', 'Write your response or attach a file.');
            }
            $submissions->submit($assignmentId, $this->userId(), $content, $file ?? ($submission['attachment_path'] ?? null));
            Curriculum::recalculate((int) $enrollment['id']);
            \App\Libraries\Notify::assignmentSubmitted($course, (string) $assignment['title'], $this->userId());

            return redirect()->to(site_url('study/' . $slug . '/assignment/' . $assignmentId))->with('message', 'Submitted. Your instructor will review it.');
        }

        $sections = Curriculum::annotate(Curriculum::load((int) $course['id'], ! $preview), $course, $enrollment ?? $this->previewEnrollment($course), $this->userId());
        [$current, $prev, $next] = $this->position($sections, 'assignment', $assignmentId);

        return view('Lms/study/assignment', [
            'title' => $assignment['title'], 'course' => $course, 'assignment' => $assignment, 'submission' => $submission,
            'sections' => $sections, 'current' => $current, 'prev' => $prev, 'next' => $next, 'enrollment' => $enrollment,
            'preview' => $preview, 'settings' => CourseModel::settings($course),
        ]);
    }

    public function notes(string $slug): string
    {
        [$course] = $this->access($slug);

        return view('Lms/study/notes', [
            'title'   => 'My notes — ' . $course['title'],
            'course'  => $course,
            'notes'   => (new LessonNoteModel())->forCourse($this->userId(), (int) $course['id']),
            'journal' => (new JournalEntryModel())->search($this->userId(), ['course' => (int) $course['id']])->get()->getResultArray(),
        ]);
    }

    /**
     * Full text of a lesson's key scripture, from the built-in KJV.
     *
     * @return array{label:string,url:string,verses:list<array>}|null
     */
    public static function keyVerses(?string $reference): ?array
    {
        if (! $reference || ! ($ref = Scripture::parse($reference))) {
            return null;
        }
        $b = db_connect()->table('bible_verses v')->select('v.verse, v.text')->join('bible_books b', 'b.id = v.book_id')
            ->where('b.position', $ref['book'])->where('v.chapter', $ref['chapter']);
        if ($ref['verse_start']) {
            $b->where('v.verse >=', $ref['verse_start'])->where('v.verse <=', min($ref['verse_end'], $ref['verse_start'] + 24));
        } else {
            $b->limit(25);
        }
        $verses = $b->orderBy('v.verse')->get()->getResultArray();

        return $verses ? ['label' => $ref['label'], 'url' => Scripture::url($ref), 'verses' => $verses] : null;
    }

    /**
     * @return array{0:array,1:?array,2:bool} [course, enrollment, preview-mode]
     */
    private function access(string $slug, bool $requireEnrollment = false): array
    {
        $model  = new CourseModel();
        $course = $model->where('slug', $slug)->first();
        if (! $course) {
            $this->notFound();
        }
        $enrollment = \App\Modules\Lms\Services\CourseAccess::resolve($course, $this->userId());
        if (! $enrollment && \App\Modules\Lms\Services\CourseAccess::lapsed($course, $this->userId()) && ! $model->isInstructor((int) $course['id'], $this->userId())) {
            throw new \CodeIgniter\HTTP\Exceptions\RedirectException(redirect()->to(site_url('pricing'))->with('error', 'Your membership is not active, so this course is paused. Renew to continue where you left off — your progress is saved.'));
        }
        if (EnrollmentModel::isActive($enrollment)) {
            if ($course['status'] !== 'published' && ! $model->isInstructor((int) $course['id'], $this->userId())) {
                $this->notFound();
            }

            return [$course, $enrollment, false];
        }
        // Instructors may walk through their own course without enrolling.
        if (! $requireEnrollment && $model->isInstructor((int) $course['id'], $this->userId())) {
            return [$course, null, true];
        }
        $enrollment = (new EnrollmentModel())->findFor((int) $course['id'], $this->userId());
        if ($enrollment && ! EnrollmentModel::isActive($enrollment)) {
            throw new \CodeIgniter\HTTP\Exceptions\RedirectException(redirect()->to(site_url('courses/' . $slug))->with('error', 'Your access to this course has ended.'));
        }

        throw new \CodeIgniter\HTTP\Exceptions\RedirectException(redirect()->to(site_url('courses/' . $slug))->with('info', 'Enroll to start this course.'));
    }

    private function previewEnrollment(array $course): array
    {
        return ['id' => 0, 'course_id' => $course['id'], 'user_id' => $this->userId(), 'status' => 'active', 'enrolled_at' => date('Y-m-d H:i:s', strtotime('-10 years')), 'progress_percent' => 0];
    }

    private function assertLesson(array $course, int $lessonId): array
    {
        $lesson = (new LessonModel())->find($lessonId);
        if (! $lesson || (int) $lesson['course_id'] !== (int) $course['id']) {
            $this->notFound();
        }

        return $lesson;
    }

    /**
     * @return array{0:?array,1:?array,2:?array} [current, previous, next]
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

    private function threads(int $lessonId): array
    {
        $rows = db_connect()->table('course_qa_threads t')->select('t.*, u.username')->join('users u', 'u.id = t.user_id')
            ->where('t.lesson_id', $lessonId)->orderBy('t.created_at', 'ASC')->get()->getResultArray();
        $top = [];
        $replies = [];
        foreach ($rows as $r) {
            if ($r['parent_id']) {
                $replies[(int) $r['parent_id']][] = $r;
            } else {
                $top[] = $r;
            }
        }
        foreach ($top as &$t) {
            $t['replies'] = $replies[(int) $t['id']] ?? [];
        }

        return array_reverse($top);
    }
}
