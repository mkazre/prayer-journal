<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;

class CertificateController extends BaseController
{
    public function show(string $code): string
    {
        $cert = $this->db()->table('certificates')
            ->select('certificates.*, courses.title as course_title, users.username')
            ->join('courses', 'courses.id = certificates.course_id')
            ->join('users', 'users.id = certificates.user_id')
            ->where('certificate_code', $code)
            ->get()->getRowArray();

        if (! $cert) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('Lms/certificate', ['title' => 'Certificate', 'cert' => $cert]);
    }

    public function mine(): string
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }
        $certs = $this->db()->table('certificates')
            ->select('certificates.*, courses.title as course_title')
            ->join('courses', 'courses.id = certificates.course_id')
            ->where('certificates.user_id', auth()->id())
            ->get()->getResultArray();

        return view('Lms/my_certificates', ['title' => 'My Certificates', 'certs' => $certs]);
    }

    protected function db()
    {
        return \Config\Database::connect();
    }
}
