<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\PrayerRequestModel;

/**
 * The community prayer wall: share a request, pray for others, and mark
 * your own requests answered with a short testimony.
 */
class PrayerRequestController extends BaseController
{
    public function index(): string
    {
        $filters = [
            'category' => (string) $this->request->getGet('category'),
            'status'   => (string) $this->request->getGet('status'),
            'mine'     => $this->request->getGet('mine') && auth()->loggedIn() ? $this->userId() : null,
            'viewer'   => auth()->loggedIn() ? $this->userId() : null,
        ];
        $model = new PrayerRequestModel();
        $db    = db_connect();

        return view('Core/prayer_requests', [
            'title'    => 'Prayer wall',
            'requests' => $model->feed(60, $filters),
            'filters'  => $filters,
            'stats'    => [
                'requests' => $db->table('prayer_requests')->countAllResults(),
                'prayers'  => (int) ($db->table('prayer_requests')->selectSum('prayer_count')->get()->getRow('prayer_count') ?? 0),
                'answered' => $db->table('prayer_requests')->where('is_answered', 1)->countAllResults(),
            ],
        ]);
    }

    public function create()
    {
        $body = trim((string) $this->request->getPost('body'));
        if (mb_strlen($body) < 5) {
            return $this->failed('Write a few words about what you would like prayer for.');
        }
        $category = (string) $this->request->getPost('category');
        (new PrayerRequestModel())->insert([
            'user_id'      => $this->userId(),
            'body'         => mb_substr($body, 0, 2000),
            'category'     => isset(PrayerRequestModel::CATEGORIES[$category]) ? $category : 'general',
            'is_anonymous' => $this->request->getPost('is_anonymous') ? 1 : 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return $this->done('Your request has been shared. The community is praying with you.', [], site_url('prayer-requests'));
    }

    public function pray(int $id)
    {
        $model   = new PrayerRequestModel();
        $request = $model->find($id) ?? $this->notFound();
        $added   = $model->prayFor($id, $this->userId());
        if ($added && (int) $request['user_id'] !== $this->userId()) {
            \App\Libraries\Notify::prayedFor((int) $request['user_id'], (string) $request['body'], display_name());
        }

        return $this->done($added ? 'Thank you for praying.' : 'You have already prayed for this request.', ['count' => (int) $request['prayer_count'] + ($added ? 1 : 0), 'prayed' => true], site_url('prayer-requests'));
    }

    public function answered(int $id)
    {
        $model   = new PrayerRequestModel();
        $request = $model->find($id) ?? $this->notFound();
        if ((int) $request['user_id'] !== $this->userId()) {
            $this->notFound();
        }
        $answered = ! (int) $request['is_answered'];
        $model->update($id, ['is_answered' => $answered ? 1 : 0, 'answer_note' => $answered ? (trim((string) $this->request->getPost('answer_note')) ?: null) : null]);

        return $this->done($answered ? 'Praise God! Marked as answered.' : 'Marked as still praying.', [], site_url('prayer-requests'));
    }

    public function delete(int $id)
    {
        $model   = new PrayerRequestModel();
        $request = $model->find($id) ?? $this->notFound();
        if ((int) $request['user_id'] !== $this->userId() && ! is_staff('superadmin', 'admin', 'manager')) {
            $this->notFound();
        }
        db_connect()->table('prayer_request_prayers')->where('request_id', $id)->delete();
        $model->delete($id);

        return $this->done('Request removed.', [], site_url('prayer-requests'));
    }
}
