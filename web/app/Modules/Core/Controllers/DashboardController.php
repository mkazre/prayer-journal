<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\EnrollmentModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $user        = auth()->user();
        $enrollments = (new EnrollmentModel())->db->table('enrollments')
            ->select('enrollments.*, courses.title as course_title, courses.slug, lessons.title as lesson_title')
            ->join('courses', 'courses.id = enrollments.course_id')
            ->join('lessons', 'lessons.id = enrollments.last_lesson_id', 'left')
            ->where('enrollments.user_id', $user->id)
            ->where('enrollments.status', 'active')
            ->orderBy('enrollments.enrolled_at', 'desc')
            ->get()->getResultArray();

        return view('Core/dashboard', [
            'title'       => 'Dashboard',
            'user'        => $user,
            'roles'       => $user->getGroups(),
            'enrollments' => $enrollments,
        ]);
    }
}
