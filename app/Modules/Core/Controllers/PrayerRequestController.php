<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\PrayerRequestModel;

class PrayerRequestController extends BaseController
{
    public function index(): string
    {
        return view('Core/prayer_requests', ['title' => 'Prayer Requests', 'requests' => (new PrayerRequestModel())->feed()]);
    }

    public function create()
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }
        (new PrayerRequestModel())->insert([
            'user_id'      => auth()->id(),
            'body'         => (string) $this->request->getPost('body'),
            'is_anonymous' => $this->request->getPost('is_anonymous') ? 1 : 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('prayer-requests'))->with('message', 'Your request has been shared.');
    }

    public function pray(int $id)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }
        (new PrayerRequestModel())->prayFor($id, auth()->id());

        return redirect()->to(site_url('prayer-requests'));
    }
}
