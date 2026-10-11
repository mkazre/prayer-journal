<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\Scripture;
use App\Modules\Bible\Models\BibleReadingLogModel;
use App\Modules\Bible\Models\ReadingPlanModel;
use App\Modules\Core\Models\PrayerRequestModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\JournalEntryModel;

/**
 * App-level endpoints for the mobile app: branding/config, the home
 * dashboard, and the community prayer wall.
 */
class AppApiController extends BaseApiController
{
    public function config()
    {
        return $this->ok([
            'name'        => site_name(),
            'tagline'     => (string) site_setting('tagline', 'Pray. Reflect. Grow.'),
            'logo_url'    => logo_url(),
            'brand_color' => (string) (site_setting('brand_color') ?: '#4b1e82'),
            'accent_color' => (string) (site_setting('accent_color') ?: '#c9a227'),
            'currency'    => store_currency(),
            'announcement' => (string) site_setting('announcement', ''),
            'web_url'     => base_url(),
            'verse_of_the_day' => $this->verse(),
            'prayer_categories' => PrayerRequestModel::CATEGORIES,
            'journal_kinds' => array_map(static fn ($k) => $k[0], JournalEntryModel::KINDS),
        ]);
    }

    public function dashboard()
    {
        $user    = $this->currentUser();
        $courses = array_map(static fn ($e) => [
            'course_id' => (int) $e['course_id'], 'slug' => $e['slug'], 'title' => $e['title'], 'subtitle' => $e['subtitle'],
            'thumbnail_url' => $e['thumbnail_path'] ? base_url($e['thumbnail_path']) : null,
            'progress' => (int) $e['progress_percent'], 'status' => $e['status'],
            'last_lesson_id' => $e['last_lesson_id'] ? (int) $e['last_lesson_id'] : null, 'last_lesson_title' => $e['last_lesson_title'],
        ], (new EnrollmentModel())->forUser($user->id));
        $plans = array_map(static fn ($p) => [
            'id' => (int) $p['plan']['id'], 'title' => $p['plan']['title'], 'slug' => $p['plan']['slug'] ?? null, 'percent' => $p['percent'], 'next_day' => $p['next_day'],
            'today' => $p['today'] ? ['day' => (int) $p['today']['day'], 'label' => $p['today']['label'], 'readings' => $p['today']['readings']] : null,
        ], (new ReadingPlanModel())->forUser($user->id));

        return $this->ok([
            'user'    => ['id' => $user->id, 'username' => $user->username, 'name' => display_name(['id' => $user->id, 'username' => $user->username])],
            'verse_of_the_day' => $this->verse(),
            'courses' => $courses,
            'journal' => (new JournalEntryModel())->stats($user->id),
            'streak'  => (new BibleReadingLogModel())->currentStreak($user->id),
            'plans'   => $plans,
            'unread_notifications' => db_connect()->table('notifications')->where('user_id', $user->id)->where('read_at', null)->countAllResults(),
        ]);
    }

    public function prayerRequests()
    {
        $user    = $this->optionalUser();
        $filters = ['category' => (string) $this->request->getGet('category'), 'status' => (string) $this->request->getGet('status'), 'viewer' => $user?->id];
        $offset  = max(0, (int) $this->request->getGet('offset'));
        $rows    = (new PrayerRequestModel())->feed(30, $filters, $offset);

        return $this->ok(array_map(static fn ($r) => [
            'id' => (int) $r['id'], 'body' => $r['body'], 'category' => $r['category'] ?? 'general',
            'author' => $r['is_anonymous'] ? 'Anonymous' : display_name(['id' => $r['user_id'], 'username' => $r['username']]),
            'is_mine' => $user && (int) $r['user_id'] === (int) $user->id,
            'prayer_count' => (int) $r['prayer_count'], 'i_prayed' => ! empty($r['i_prayed']),
            'is_answered' => (bool) $r['is_answered'], 'answer_note' => $r['answer_note'], 'created_at' => $r['created_at'],
        ], $rows));
    }

    public function createPrayerRequest()
    {
        $in   = $this->body();
        $body = trim((string) ($in['body'] ?? ''));
        if (mb_strlen($body) < 5) {
            return $this->fail('Write a few words about what you would like prayer for.', 422);
        }
        $category = (string) ($in['category'] ?? 'general');
        $id       = (new PrayerRequestModel())->insert([
            'user_id' => $this->currentUser()->id, 'body' => mb_substr($body, 0, 2000),
            'category' => isset(PrayerRequestModel::CATEGORIES[$category]) ? $category : 'general',
            'is_anonymous' => ! empty($in['is_anonymous']) ? 1 : 0, 'created_at' => date('Y-m-d H:i:s'),
        ], true);

        return $this->ok(['id' => (int) $id, 'message' => 'Your request has been shared.'], 201);
    }

    public function pray(int $id)
    {
        $model   = new PrayerRequestModel();
        $request = $model->find($id);
        if (! $request) {
            return $this->fail('Request not found.', 404);
        }
        $added = $model->prayFor($id, $this->currentUser()->id);

        return $this->ok(['prayed' => true, 'added' => $added, 'prayer_count' => (int) $request['prayer_count'] + ($added ? 1 : 0)]);
    }

    private function verse(): ?array
    {
        $v = verse_of_the_day();

        return $v ? [
            'reference' => $v['book_name'] . ' ' . $v['chapter'] . ':' . $v['verse'], 'text' => $v['text'],
            'book' => Scripture::slug((int) $v['book_position']), 'chapter' => (int) $v['chapter'], 'verse' => (int) $v['verse'],
        ] : null;
    }
}
