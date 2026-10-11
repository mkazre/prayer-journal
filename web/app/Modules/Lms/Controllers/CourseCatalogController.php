<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\CourseReviewModel;
use App\Modules\Lms\Models\EnrollmentModel;

class CourseCatalogController extends BaseController
{
    public function index(): string
    {
        $model    = (new CourseModel())->published();
        $search   = $this->request->getGet('q');
        $category = $this->request->getGet('category');
        if ($search) {
            $model->like('title', $search);
        }
        if ($category) {
            $model->where('category_id', $category);
        }
        $courses = $model->orderBy('created_at', 'desc')->findAll();

        $reviewModel = new CourseReviewModel();
        foreach ($courses as &$c) {
            $c['avg_rating'] = $reviewModel->averageRating($c['id']);
        }

        return view('Lms/catalog/index', ['title' => 'Courses', 'courses' => $courses]);
    }

    public function show(string $slug)
    {
        $courseModel = new CourseModel();
        $course      = $courseModel->where('slug', $slug)->first();
        if (! $course) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $enrolled = false;
        if (auth()->loggedIn()) {
            $enrolled = (bool) (new EnrollmentModel())->where(['course_id' => $course['id'], 'user_id' => auth()->id()])->first();
        }

        $reviewModel = new CourseReviewModel();

        return view('Lms/catalog/show', [
            'title'      => $course['title'],
            'course'     => $course,
            'enrolled'   => $enrolled,
            'reviews'    => $reviewModel->forCourse($course['id']),
            'avgRating'  => $reviewModel->averageRating($course['id']),
        ]);
    }

    public function review(string $slug)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }
        $course = (new CourseModel())->where('slug', $slug)->first();
        if (! $course) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        (new CourseReviewModel())->upsert($course['id'], auth()->id(), (int) $this->request->getPost('rating'), (string) $this->request->getPost('body'));

        return redirect()->to(site_url('courses/' . $slug))->with('message', 'Thanks for your review!');
    }

    public function enroll(string $slug)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }

        $course = (new CourseModel())->where('slug', $slug)->first();
        if (! $course) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($course['price_cents'] > 0) {
            // Paid courses go through the shop checkout so purchase and enrollment share one payment path.
            return redirect()->to(site_url('checkout/course/' . $course['id']));
        }

        (new EnrollmentModel())->enroll($course['id'], auth()->id());

        return redirect()->to(site_url('study/' . $course['slug']))->with('message', 'Enrolled! Happy studying.');
    }
}
