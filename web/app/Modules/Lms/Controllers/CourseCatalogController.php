<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\CourseReviewModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\LessonModel;
use App\Modules\Lms\Services\Curriculum;

class CourseCatalogController extends BaseController
{
    public function index(): string
    {
        $model   = new CourseModel();
        $filters = [
            'q'        => trim((string) $this->request->getGet('q')),
            'category' => (string) $this->request->getGet('category'),
            'level'    => (string) $this->request->getGet('level'),
            'price'    => (string) $this->request->getGet('price'),
            'sort'     => (string) ($this->request->getGet('sort') ?: 'popular'),
        ];
        $mine = [];
        if (auth()->loggedIn()) {
            foreach ((new EnrollmentModel())->forUser($this->userId()) as $e) {
                $mine[(int) $e['course_id']] = (int) $e['progress_percent'];
            }
        }

        return view('Lms/catalog/index', [
            'title'      => 'Courses',
            'courses'    => $model->catalog($filters),
            'filters'    => $filters,
            'categories' => $model->categories(),
            'mine'       => $mine,
        ]);
    }

    public function show(string $slug)
    {
        $course = $this->findCourse($slug);
        $model  = new CourseModel();
        $course = $model->withStats($course);

        $enrollment = auth()->loggedIn() ? \App\Modules\Lms\Services\CourseAccess::resolve($course, $this->userId()) : null;
        $sections   = Curriculum::load((int) $course['id']);
        $owned      = $enrollment ? \App\Modules\Lms\Services\CourseAccess::ownedSections($enrollment, (int) $course['id'], $this->userId()) : null;
        $plans      = (new \App\Modules\Shop\Models\SubscriptionPlanModel())->where('is_active', 1)->orderBy('price_cents')->findAll();
        $reviews    = new CourseReviewModel();
        $all        = $reviews->forCourse((int) $course['id']);
        $breakdown  = array_fill(1, 5, 0);
        foreach ($all as $r) {
            $breakdown[max(1, min(5, (int) $r['rating']))]++;
        }
        $myReview = null;
        if (auth()->loggedIn()) {
            foreach ($all as $r) {
                if ((int) $r['user_id'] === $this->userId()) {
                    $myReview = $r;
                }
            }
        }
        $related = array_values(array_filter(
            $model->catalog(['category' => $course['category_slug'] ?? null, 'limit' => 4]),
            static fn ($c) => (int) $c['id'] !== (int) $course['id']
        ));

        return view('Lms/catalog/show', [
            'title'           => $course['title'],
            'metaDescription' => $course['summary'],
            'course'          => $course,
            'settings'        => CourseModel::settings($course),
            'sections'        => $sections,
            'enrollment'      => $enrollment,
            'enrolled'        => EnrollmentModel::isActive($enrollment) && $owned === null,
            'ownedSections'   => $owned,
            'inMembership'    => \App\Modules\Lms\Services\CourseAccess::inMembership($course),
            'cheapestPlan'    => $plans[0] ?? null,
            'lapsed'          => auth()->loggedIn() && ! $enrollment && \App\Modules\Lms\Services\CourseAccess::lapsed($course, $this->userId()),
            'reviews'         => $all,
            'breakdown'       => $breakdown,
            'avgRating'       => $course['avg_rating'] !== null ? round((float) $course['avg_rating'], 1) : null,
            'myReview'        => $myReview,
            'related'         => array_slice($related, 0, 3),
            'canEdit'         => auth()->loggedIn() && $model->isInstructor((int) $course['id'], $this->userId()),
            'full'            => $this->isFull($course),
        ]);
    }

    /**
     * Free preview lessons, readable before enrolling.
     */
    public function preview(string $slug, int $lessonId)
    {
        $course   = $this->findCourse($slug);
        $settings = CourseModel::settings($course);
        $lesson   = (new LessonModel())->where(['id' => $lessonId, 'course_id' => $course['id'], 'status' => 'published', 'is_preview' => 1])->first();
        if (! $lesson || (! $settings['preview_public'] && ! auth()->loggedIn())) {
            return redirect()->to(site_url('courses/' . $slug))->with('info', 'Enroll to open this lesson.');
        }
        if (auth()->loggedIn() && EnrollmentModel::isActive((new EnrollmentModel())->findFor((int) $course['id'], $this->userId()))) {
            return redirect()->to(site_url('study/' . $slug . '/lesson/' . $lessonId));
        }

        StudyController::protect($course);

        return view('Lms/catalog/preview', [
            'title'    => $lesson['title'] . ' — ' . $course['title'],
            'course'   => $course,
            'lesson'   => $lesson,
            'settings' => $settings,
            'verses'   => StudyController::keyVerses($lesson['bible_reference']),
        ]);
    }

    public function enroll(string $slug)
    {
        $course = $this->findCourse($slug);
        $model  = new EnrollmentModel();
        $userId = $this->userId();

        $existing = $model->findFor((int) $course['id'], $userId);
        if (EnrollmentModel::isActive($existing) && ! in_array($existing['source'], ['subscription', 'module'], true)) {
            return redirect()->to(site_url('study/' . $slug));
        }
        if ($this->isFull($course)) {
            return redirect()->to(site_url('courses/' . $slug))->with('error', 'This course is full right now.');
        }
        if (\App\Modules\Lms\Services\CourseAccess::resolve($course, $userId)) {
            return redirect()->to(site_url('study/' . $slug));
        }
        if (($course['access_mode'] ?? 'free') === 'subscription') {
            return redirect()->to(site_url('pricing'))->with('info', 'This course is included with membership.');
        } elseif ((int) $course['price_cents'] > 0) {
            // Paid courses go through the shop checkout so purchase and enrollment share one payment path.
            return redirect()->to(site_url('checkout/course/' . $course['id']));
        } else {
            $model->enroll((int) $course['id'], $userId, 'free');
        }

        return redirect()->to(site_url('study/' . $course['slug']))->with('message', 'You are enrolled. Welcome!');
    }

    public function review(string $slug)
    {
        $course   = $this->findCourse($slug);
        $settings = CourseModel::settings($course);
        if (! $settings['reviews']) {
            return $this->failed('Reviews are turned off for this course.');
        }
        if (! EnrollmentModel::isActive((new EnrollmentModel())->findFor((int) $course['id'], $this->userId()))) {
            return $this->failed('Enroll in the course to leave a review.');
        }
        $rating = (int) $this->request->getPost('rating');
        if ($rating < 1 || $rating > 5) {
            return $this->failed('Choose a rating from 1 to 5 stars.');
        }
        (new CourseReviewModel())->upsert((int) $course['id'], $this->userId(), $rating, trim(mb_substr((string) $this->request->getPost('body'), 0, 3000)));

        return $this->done('Thank you for your review!', [], site_url('courses/' . $slug . '#reviews'));
    }

    private function findCourse(string $slug): array
    {
        $model  = new CourseModel();
        $course = $model->where('slug', $slug)->first();
        if (! $course) {
            $this->notFound();
        }
        if ($course['status'] !== 'published' && ! (auth()->loggedIn() && $model->isInstructor((int) $course['id'], $this->userId()))) {
            $this->notFound();
        }
        $category                = $course['category_id'] ? db_connect()->table('course_categories')->where('id', $course['category_id'])->get()->getRowArray() : null;
        $course['category_slug'] = $category['slug'] ?? null;
        $course['category_name'] = $category['name'] ?? null;

        return $course;
    }

    private function isFull(array $course): bool
    {
        $limit = CourseModel::settings($course)['enrollment_limit'];
        if (! $limit) {
            return false;
        }

        return db_connect()->table('enrollments')->where('course_id', $course['id'])->where('status !=', 'revoked')->countAllResults() >= (int) $limit;
    }
}
