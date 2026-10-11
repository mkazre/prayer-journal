<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\CourseModel;

class CertificateController extends BaseController
{
    /**
     * Public, verifiable certificate page (share the URL as proof of completion).
     */
    public function show(string $code): string
    {
        $cert = db_connect()->table('certificates')
            ->select('certificates.*, courses.title AS course_title, courses.slug, courses.settings, courses.author_name, users.username')
            ->join('courses', 'courses.id = certificates.course_id')
            ->join('users', 'users.id = certificates.user_id')
            ->where('certificate_code', strtoupper($code))
            ->get()->getRowArray();
        if (! $cert) {
            $this->notFound();
        }

        return view('Lms/certificate', [
            'title'    => 'Certificate — ' . $cert['course_title'],
            'cert'     => $cert,
            'settings' => CourseModel::settings($cert),
            'name'     => display_name(['id' => $cert['user_id'], 'username' => $cert['username']]),
            'isOwner'  => auth()->loggedIn() && (int) auth()->id() === (int) $cert['user_id'],
        ]);
    }

    public function mine(): string
    {
        $certs = db_connect()->table('certificates')
            ->select('certificates.*, courses.title AS course_title, courses.slug, courses.settings')
            ->join('courses', 'courses.id = certificates.course_id')
            ->where('certificates.user_id', $this->userId())
            ->orderBy('issued_at', 'DESC')
            ->get()->getResultArray();

        return view('Lms/my_certificates', ['title' => 'My certificates', 'certs' => $certs]);
    }
}
