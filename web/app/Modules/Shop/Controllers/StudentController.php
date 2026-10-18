<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\SubscriptionPlanModel;
use App\Modules\Shop\Services\StudentVerification;

/**
 * Student status: verify with SheerID, then buy a plan at its student price.
 */
class StudentController extends BaseController
{
    public function index(): string
    {
        $userId = $this->userId();

        return view('Shop/student', [
            'title'      => 'Student status',
            'configured' => StudentVerification::isConfigured(),
            'current'    => StudentVerification::current($userId),
            'latest'     => StudentVerification::latest($userId),
            'plans'      => array_values(array_filter((new SubscriptionPlanModel())->where('is_active', 1)->findAll(), static fn ($p) => $p['student_price_cents'] !== null && $p['student_price_cents'] !== '')),
        ]);
    }

    public function verify()
    {
        if (StudentVerification::isVerified($this->userId())) {
            return redirect()->to(site_url('account/student'))->with('message', 'You are already verified.');
        }
        try {
            return redirect()->to(StudentVerification::start($this->userId(), (string) auth()->user()->email));
        } catch (\RuntimeException $e) {
            log_message('error', 'Student verification start failed: ' . $e->getMessage());

            return redirect()->to(site_url('account/student'))->with('error', 'Student verification is not available right now. Please try again later.');
        }
    }

    /**
     * SheerID's form sends students back here; check the result straight away.
     */
    public function back()
    {
        $latest = StudentVerification::latest($this->userId());
        if ($latest && $latest['status'] === 'pending') {
            $latest = StudentVerification::refresh($latest['external_id']);
        }
        if ($latest && $latest['status'] === 'verified') {
            return redirect()->to(site_url('account/student'))->with('message', 'You are verified as a student — your student price is unlocked.');
        }

        return redirect()->to(site_url('account/student'));
    }

    /**
     * SheerID webhook (signed). Always answers quickly; a bad signature is 401.
     */
    public function webhook()
    {
        $ok = StudentVerification::handleWebhook((string) $this->request->getBody(), $this->request->getHeaderLine('X-SheerID-Signature'));

        return $this->response->setStatusCode($ok ? 200 : 401)->setBody($ok ? 'ok' : 'invalid signature');
    }
}
