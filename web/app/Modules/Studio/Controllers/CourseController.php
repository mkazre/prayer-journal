<?php

namespace App\Modules\Studio\Controllers;

use App\Libraries\HtmlSanitizer;
use App\Libraries\Uploads;
use App\Modules\Lms\Models\CourseAnnouncementModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Services\Curriculum;

class CourseController extends StudioController
{
    public function index(): string
    {
        $this->requireInstructor();
        $courses = (new CourseModel())->manageableBy($this->userId(), $this->isManager());
        $status  = (string) $this->request->getGet('status');
        if (in_array($status, ['published', 'draft'], true)) {
            $courses = array_values(array_filter($courses, static fn ($c) => $c['status'] === $status));
        }

        return $this->render('courses/index', ['title' => 'Courses', 'active' => 'courses', 'courses' => $courses, 'status' => $status]);
    }

    public function create()
    {
        $this->requireInstructor();
        $model = new CourseModel();
        if ($this->request->getMethod() === 'POST') {
            $title = trim((string) $this->post('title'));
            if (mb_strlen($title) < 3) {
                return redirect()->back()->withInput()->with('error', 'Give your course a title (at least 3 characters).');
            }
            $id = $model->insert([
                'title'       => $title,
                'slug'        => $model->uniqueSlug($title),
                'summary'     => trim((string) $this->post('summary')) ?: null,
                'status'      => 'draft',
                'created_by'  => $this->userId(),
                'price_cents' => 0,
                'currency'    => store_currency(),
                'access_mode' => 'free',
                'level'       => 'all',
                'author_name' => display_name(),
                'settings'    => json_encode(CourseModel::DEFAULT_SETTINGS),
            ], true);
            db_connect()->table('course_instructors')->insert(['course_id' => $id, 'user_id' => $this->userId(), 'role' => 'owner', 'created_at' => date('Y-m-d H:i:s')]);
            db_connect()->table('course_sections')->insert(['course_id' => $id, 'title' => 'Getting started', 'position' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);

            return redirect()->to(site_url('studio/courses/' . $id . '/curriculum'))->with('message', 'Course created. Add your first lesson.');
        }

        return $this->render('courses/new', ['title' => 'New course', 'active' => 'courses']);
    }

    public function edit(int $id)
    {
        $course = $this->course($id);
        $model  = new CourseModel();

        if ($this->request->getMethod() === 'POST') {
            $title = trim((string) $this->post('title'));
            $slug  = url_title((string) ($this->post('slug') ?: $title), '-', true);
            if (mb_strlen($title) < 3 || $slug === '') {
                return redirect()->back()->withInput()->with('error', 'A title and URL are required.');
            }
            if ($model->withDeleted()->where('slug', $slug)->where('id !=', $id)->first()) {
                return redirect()->back()->withInput()->with('error', 'Another course already uses that URL.');
            }
            $data = [
                'title'           => $title,
                'slug'            => $slug,
                'subtitle'        => trim((string) $this->post('subtitle')) ?: null,
                'summary'         => trim((string) $this->post('summary')) ?: null,
                'description'     => HtmlSanitizer::clean((string) $this->post('description')),
                'category_id'     => $this->categoryId(),
                'level'           => isset(CourseModel::LEVELS[$this->post('level')]) ? $this->post('level') : 'all',
                'language'        => trim((string) $this->post('language')) ?: 'English',
                'tags'            => trim((string) $this->post('tags')) ?: null,
                'intro_video_url' => trim((string) $this->post('intro_video_url')) ?: null,
                'what_you_learn'  => trim((string) $this->post('what_you_learn')) ?: null,
                'requirements'    => trim((string) $this->post('requirements')) ?: null,
                'audience'        => trim((string) $this->post('audience')) ?: null,
                'author_name'     => trim((string) $this->post('author_name')) ?: null,
                'author_bio'      => trim((string) $this->post('author_bio')) ?: null,
                'price_cents'     => max(0, $this->money('price')),
                'access_mode'     => in_array($this->post('access_mode'), ['free', 'paid', 'subscription'], true) ? $this->post('access_mode') : 'free',
            ];
            if ($data['access_mode'] === 'free') {
                $data['price_cents'] = 0;
            }
            if ($this->isManager()) {
                $data['is_featured'] = $this->bool('is_featured');
            }
            try {
                if ($cover = Uploads::image($this->request->getFile('cover'), 'courses')) {
                    Uploads::delete($course['thumbnail_path']);
                    $data['thumbnail_path'] = $cover;
                }
            } catch (\RuntimeException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
            if ($this->post('remove_cover')) {
                Uploads::delete($course['thumbnail_path']);
                $data['thumbnail_path'] = null;
            }
            $model->skipValidation(true)->update($id, $data);
            $model->saveSettings($id, ['in_membership' => $data['access_mode'] === 'paid' && $this->bool('in_membership')]);

            return redirect()->to(site_url('studio/courses/' . $id))->with('message', 'Course details saved.');
        }

        return $this->render('courses/edit', [
            'title'      => $course['title'],
            'active'     => 'courses',
            'tab'        => 'details',
            'course'     => $course,
            'categories' => $model->categories(),
            'stats'      => $model->withStats($course),
        ]);
    }

    public function settings(int $id)
    {
        $course = $this->course($id);
        $model  = new CourseModel();
        if ($this->request->getMethod() === 'POST') {
            $p       = (array) $this->request->getPost();
            $prompts = [];
            foreach ((array) ($p['prompts'] ?? []) as $row) {
                if (trim((string) ($row['label'] ?? '')) !== '') {
                    $prompts[] = ['key' => url_title(mb_substr($row['label'], 0, 40), '-', true) ?: ('p' . count($prompts)), 'label' => trim($row['label']), 'hint' => trim((string) ($row['hint'] ?? '')), 'kind' => $row['kind'] ?? 'note'];
                }
            }
            $hex = static fn ($v, $d) => preg_match('/^#[0-9a-f]{6}$/i', (string) $v) ? strtolower($v) : $d;
            $model->saveSettings($id, [
                'sequential'            => ! empty($p['sequential']),
                'drip'                  => in_array($p['drip'] ?? '', ['none', 'enrollment'], true) ? $p['drip'] : 'none',
                'completion'            => in_array($p['completion'] ?? '', ['lessons', 'lessons_quizzes'], true) ? $p['completion'] : 'lessons',
                'certificate_enabled'   => ! empty($p['certificate_enabled']),
                'certificate_title'     => trim((string) ($p['certificate_title'] ?? '')) ?: 'Certificate of Completion',
                'certificate_signatory' => trim((string) ($p['certificate_signatory'] ?? '')),
                'certificate_role'      => trim((string) ($p['certificate_role'] ?? '')),
                'certificate_message'   => trim((string) ($p['certificate_message'] ?? '')) ?: CourseModel::DEFAULT_SETTINGS['certificate_message'],
                'discussions'           => ! empty($p['discussions']),
                'reviews'               => ! empty($p['reviews']),
                'journal'               => ! empty($p['journal']),
                'show_progress'         => ! empty($p['show_progress']),
                'preview_public'        => ! empty($p['preview_public']),
                'copy_protection'       => ! empty($p['copy_protection']),
                'enrollment_limit'      => ($p['enrollment_limit'] ?? '') !== '' ? max(1, (int) $p['enrollment_limit']) : null,
                'access_days'           => ($p['access_days'] ?? '') !== '' ? max(1, (int) $p['access_days']) : null,
                'brand_color'           => $hex($p['brand_color'] ?? '', '#232d47'),
                'accent_color'          => $hex($p['accent_color'] ?? '', '#c9a227'),
                'welcome_message'       => trim(mb_substr((string) ($p['welcome_message'] ?? ''), 0, 1000)),
                'completion_message'    => trim(mb_substr((string) ($p['completion_message'] ?? ''), 0, 1000)),
                'default_prompts'       => $prompts,
            ]);

            return redirect()->to(site_url('studio/courses/' . $id . '/settings'))->with('message', 'Settings saved.');
        }

        return $this->render('courses/settings', ['title' => 'Settings · ' . $course['title'], 'active' => 'courses', 'tab' => 'settings', 'course' => $course, 'settings' => CourseModel::settings($course)]);
    }

    public function students(int $id): string
    {
        $course = $this->course($id);
        $q      = trim((string) $this->request->getGet('q'));
        $b      = db_connect()->table('enrollments e')
            ->select('e.*, u.username, ai.secret AS email')
            ->select('(SELECT COUNT(*) FROM journal_entries j WHERE j.user_id = e.user_id AND j.course_id = e.course_id) AS journal_count', false)
            ->join('users u', 'u.id = e.user_id')
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left')
            ->where('e.course_id', $id);
        if ($q !== '') {
            $b->groupStart()->like('u.username', $q)->orLike('ai.secret', $q)->groupEnd();
        }
        $rows = $b->orderBy('e.enrolled_at', 'DESC')->get()->getResultArray();

        return $this->render('courses/students', ['title' => 'Students · ' . $course['title'], 'active' => 'courses', 'tab' => 'students', 'course' => $course, 'rows' => $rows, 'q' => $q]);
    }

    public function enrollStudent(int $id)
    {
        $this->course($id);
        $email = trim((string) $this->post('email'));
        $user  = auth()->getProvider()->findByCredentials(['email' => $email]);
        if (! $user) {
            return $this->failed('No account uses ' . $email . '. Ask them to sign up first.');
        }
        (new EnrollmentModel())->enroll($id, (int) $user->id, 'manual');

        return $this->done(display_name($user) . ' enrolled.', [], site_url('studio/courses/' . $id . '/students'));
    }

    public function revoke(int $enrollmentId)
    {
        $enrollment = (new EnrollmentModel())->find($enrollmentId);
        if (! $enrollment) {
            $this->notFound();
        }
        $this->course((int) $enrollment['course_id']);
        $revoke = $enrollment['status'] !== 'revoked';
        (new EnrollmentModel())->update($enrollmentId, ['status' => $revoke ? 'revoked' : 'active']);

        return $this->done($revoke ? 'Access removed.' : 'Access restored.');
    }

    public function resetProgress(int $enrollmentId)
    {
        $enrollment = (new EnrollmentModel())->find($enrollmentId);
        if (! $enrollment) {
            $this->notFound();
        }
        $this->course((int) $enrollment['course_id']);
        db_connect()->table('lesson_completions')->where('enrollment_id', $enrollmentId)->delete();
        Curriculum::recalculate($enrollmentId);

        return $this->done('Progress reset.');
    }

    public function analytics(int $id): string
    {
        $course = $this->course($id);
        $db     = db_connect();
        $enroll = $db->table('enrollments')->where('course_id', $id)->get()->getResultArray();
        $total  = count($enroll);
        $bands  = ['Not started' => 0, '1–25%' => 0, '26–50%' => 0, '51–75%' => 0, '76–99%' => 0, 'Completed' => 0];
        foreach ($enroll as $e) {
            $p = (int) $e['progress_percent'];
            $key = $e['status'] === 'completed' ? 'Completed' : ($p === 0 ? 'Not started' : ($p <= 25 ? '1–25%' : ($p <= 50 ? '26–50%' : ($p <= 75 ? '51–75%' : '76–99%'))));
            $bands[$key]++;
        }
        $lessons = $db->table('lessons l')->select('l.id, l.title, l.position')
            ->select('(SELECT COUNT(*) FROM lesson_completions lc WHERE lc.lesson_id = l.id) AS completions', false)
            ->select('(SELECT COUNT(*) FROM journal_entries j WHERE j.lesson_id = l.id) AS journal', false)
            ->where('l.course_id', $id)->where('l.status', 'published')->orderBy('l.position')->get()->getResultArray();
        $quizzes = $db->table('quizzes q')->select('q.id, q.title, q.pass_percent')
            ->select('(SELECT COUNT(*) FROM quiz_attempts a WHERE a.quiz_id = q.id) AS attempts', false)
            ->select('(SELECT AVG(a.score_percent) FROM quiz_attempts a WHERE a.quiz_id = q.id) AS avg_score', false)
            ->select('(SELECT COUNT(DISTINCT a.user_id) FROM quiz_attempts a WHERE a.quiz_id = q.id AND a.passed = 1) AS passed_users', false)
            ->where('q.course_id', $id)->orderBy('q.position')->get()->getResultArray();
        $series = [];
        $rows   = array_column($db->table('enrollments')->select('DATE(enrolled_at) AS d, COUNT(*) AS n')->where('course_id', $id)->where('enrolled_at >=', date('Y-m-d', strtotime('-29 days')))->groupBy('d')->get()->getResultArray(), 'n', 'd');
        for ($i = 29; $i >= 0; $i--) {
            $d          = date('Y-m-d', strtotime("-{$i} days"));
            $series[$d] = (int) ($rows[$d] ?? 0);
        }

        return $this->render('courses/analytics', [
            'title' => 'Analytics · ' . $course['title'], 'active' => 'courses', 'tab' => 'analytics', 'course' => $course,
            'total' => $total, 'bands' => $bands, 'lessons' => $lessons, 'quizzes' => $quizzes, 'series' => $series,
            'completionRate' => $total ? round($bands['Completed'] / $total * 100) : 0,
            'avgProgress'    => $total ? round(array_sum(array_column($enroll, 'progress_percent')) / $total) : 0,
            'journalTotal'   => $db->table('journal_entries')->where('course_id', $id)->countAllResults(),
        ]);
    }

    public function announcements(int $id)
    {
        $course = $this->course($id);
        $model  = new CourseAnnouncementModel();
        if ($this->request->getMethod() === 'POST') {
            if ($delete = (int) $this->post('delete')) {
                $model->where(['id' => $delete, 'course_id' => $id])->delete();

                return redirect()->back()->with('message', 'Announcement deleted.');
            }
            $title = trim((string) $this->post('title'));
            $body  = trim((string) $this->post('body'));
            if ($title === '' || $body === '') {
                return redirect()->back()->withInput()->with('error', 'Add a title and a message.');
            }
            $model->insert(['course_id' => $id, 'user_id' => $this->userId(), 'title' => $title, 'body' => $body, 'created_at' => date('Y-m-d H:i:s')]);
            // Let enrolled learners know.
            \App\Libraries\Notify::announcement($course, $title, $body);

            return redirect()->back()->with('message', 'Announcement posted and emailed to all learners.');
        }

        return $this->render('courses/announcements', ['title' => 'Announcements · ' . $course['title'], 'active' => 'courses', 'tab' => 'announcements', 'course' => $course, 'items' => $model->forCourse($id, 50)]);
    }

    public function reviews(int $id): string
    {
        $course = $this->course($id);

        return $this->render('courses/reviews', ['title' => 'Reviews · ' . $course['title'], 'active' => 'courses', 'tab' => 'reviews', 'course' => $course,
            'reviews' => (new \App\Modules\Lms\Models\CourseReviewModel())->forCourse($id)]);
    }

    public function deleteReview(int $reviewId)
    {
        $review = db_connect()->table('course_reviews')->where('id', $reviewId)->get()->getRowArray();
        if (! $review) {
            $this->notFound();
        }
        $this->course((int) $review['course_id']);
        db_connect()->table('course_reviews')->where('id', $reviewId)->delete();

        return $this->done('Review removed.');
    }

    public function togglePublish(int $id)
    {
        $course  = $this->course($id);
        $publish = $course['status'] !== 'published';
        if ($publish && ! db_connect()->table('lessons')->where(['course_id' => $id, 'status' => 'published'])->countAllResults()) {
            return $this->failed('Publish at least one lesson before publishing the course.');
        }
        (new CourseModel())->skipValidation(true)->update($id, ['status' => $publish ? 'published' : 'draft', 'published_at' => $publish ? ($course['published_at'] ?: date('Y-m-d H:i:s')) : $course['published_at']]);

        return $this->done($publish ? 'Course published — it is now live.' : 'Course moved back to draft.');
    }

    /**
     * Deep copy: modules, lessons, quizzes (with questions) and assignments.
     */
    public function duplicate(int $id)
    {
        $course = $this->course($id);
        $db     = db_connect();
        $model  = new CourseModel();
        $now    = date('Y-m-d H:i:s');
        $db->transStart();
        $copy = $course;
        unset($copy['id'], $copy['deleted_at']);
        $copy['title']        = $course['title'] . ' (copy)';
        $copy['slug']         = $model->uniqueSlug($copy['title']);
        $copy['status']       = 'draft';
        $copy['created_by']   = $this->userId();
        $copy['is_featured']  = 0;
        $copy['published_at'] = null;
        $copy['created_at']   = $copy['updated_at'] = $now;
        $db->table('courses')->insert($copy);
        $newId = (int) $db->insertID();
        $db->table('course_instructors')->insert(['course_id' => $newId, 'user_id' => $this->userId(), 'role' => 'owner', 'created_at' => $now]);

        $sectionMap = [];
        foreach ($db->table('course_sections')->where('course_id', $id)->get()->getResultArray() as $s) {
            $old = $s['id'];
            unset($s['id']);
            $s['course_id'] = $newId;
            $db->table('course_sections')->insert($s);
            $sectionMap[$old] = (int) $db->insertID();
        }
        foreach ($db->table('lessons')->where('course_id', $id)->get()->getResultArray() as $l) {
            unset($l['id']);
            $l['course_id']  = $newId;
            $l['section_id'] = $sectionMap[$l['section_id']] ?? null;
            $db->table('lessons')->insert($l);
        }
        foreach ($db->table('quizzes')->where('course_id', $id)->get()->getResultArray() as $q) {
            $oldQuiz = $q['id'];
            unset($q['id']);
            $q['course_id']  = $newId;
            $q['section_id'] = $sectionMap[$q['section_id']] ?? null;
            $q['lesson_id']  = null;
            $db->table('quizzes')->insert($q);
            $newQuiz = (int) $db->insertID();
            foreach ($db->table('quiz_questions')->where('quiz_id', $oldQuiz)->get()->getResultArray() as $qq) {
                $oldQ = $qq['id'];
                unset($qq['id']);
                $qq['quiz_id'] = $newQuiz;
                $db->table('quiz_questions')->insert($qq);
                $newQ = (int) $db->insertID();
                foreach ($db->table('quiz_options')->where('question_id', $oldQ)->get()->getResultArray() as $o) {
                    unset($o['id']);
                    $o['question_id'] = $newQ;
                    $db->table('quiz_options')->insert($o);
                }
            }
        }
        foreach ($db->table('assignments')->where('course_id', $id)->get()->getResultArray() as $a) {
            unset($a['id']);
            $a['course_id']  = $newId;
            $a['section_id'] = $sectionMap[$a['section_id']] ?? null;
            $a['lesson_id']  = null;
            $db->table('assignments')->insert($a);
        }
        $db->transComplete();

        return redirect()->to(site_url('studio/courses/' . $newId))->with('message', 'Course duplicated as a draft.');
    }

    public function delete(int $id)
    {
        $course = $this->course($id);
        if (strtolower(trim((string) $this->post('confirm'))) !== 'delete') {
            return redirect()->back()->with('error', 'Type DELETE to confirm.');
        }
        (new CourseModel())->delete($id);

        return redirect()->to(site_url('studio/courses'))->with('message', '"' . $course['title'] . '" was deleted.');
    }

    /**
     * All learners across the courses this user manages.
     */
    public function allStudents(): string
    {
        $this->requireInstructor();
        $ids = array_map('intval', array_column((new CourseModel())->manageableBy($this->userId(), $this->isManager()), 'id')) ?: [0];
        $q   = trim((string) $this->request->getGet('q'));
        $b   = db_connect()->table('enrollments e')
            ->select('u.id AS user_id, u.username, ai.secret AS email, COUNT(e.id) AS courses, AVG(e.progress_percent) AS avg_progress, MAX(COALESCE(e.last_activity_at, e.enrolled_at)) AS last_seen', false)
            ->select("SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed", false)
            ->join('users u', 'u.id = e.user_id')
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left')
            ->whereIn('e.course_id', $ids);
        if ($q !== '') {
            $b->groupStart()->like('u.username', $q)->orLike('ai.secret', $q)->groupEnd();
        }
        $rows = $b->groupBy('u.id, u.username, ai.secret')->orderBy('last_seen', 'DESC')->get()->getResultArray();

        return $this->render('courses/all_students', ['title' => 'Students', 'active' => 'students', 'rows' => $rows, 'q' => $q]);
    }

    private function categoryId(): ?int
    {
        $new = trim((string) $this->post('new_category'));
        if ($new !== '') {
            $db   = db_connect();
            $slug = url_title($new, '-', true);
            $row  = $db->table('course_categories')->where('slug', $slug)->get()->getRowArray();
            if ($row) {
                return (int) $row['id'];
            }
            $db->table('course_categories')->insert(['name' => $new, 'slug' => $slug, 'created_at' => date('Y-m-d H:i:s')]);

            return (int) $db->insertID();
        }

        return (int) $this->post('category_id') ?: null;
    }
}
