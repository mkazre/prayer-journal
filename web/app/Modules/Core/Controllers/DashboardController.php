<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Bible\Models\BibleReadingLogModel;
use App\Modules\Bible\Models\ReadingPlanModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\JournalEntryModel;
use App\Modules\Lms\Services\Curriculum;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $userId      = $this->userId();
        $db          = db_connect();
        $enrollments = (new EnrollmentModel())->forUser($userId);

        // Next step in the most recently active course.
        $next = null;
        foreach ($enrollments as $e) {
            if ($e['status'] === 'completed') {
                continue;
            }
            $course   = $db->table('courses')->where('id', $e['course_id'])->get()->getRowArray();
            $sections = Curriculum::annotate(Curriculum::load((int) $e['course_id']), $course, $e, $userId);
            foreach (Curriculum::flatten($sections) as $item) {
                if (! $item['done'] && ! $item['locked']) {
                    $next = ['course' => $course, 'item' => $item, 'enrollment' => $e];
                    break 2;
                }
            }
        }

        $journal = new JournalEntryModel();

        return view('Core/dashboard', [
            'title'        => 'Dashboard',
            'enrollments'  => $enrollments,
            'next'         => $next,
            'verse'        => verse_of_the_day(),
            'streak'       => (new BibleReadingLogModel())->currentStreak($userId),
            'journalStats' => $journal->stats($userId),
            'openPrayers'  => $journal->search($userId, ['status' => 'open'])->limit(5)->get()->getResultArray(),
            'recent'       => $journal->search($userId)->limit(4)->get()->getResultArray(),
            'plans'        => (new ReadingPlanModel())->forUser($userId),
            'certificates' => $db->table('certificates')->where('user_id', $userId)->countAllResults(),
            'lessonsDone'  => $db->table('lesson_completions lc')->join('enrollments e', 'e.id = lc.enrollment_id')->where('e.user_id', $userId)->countAllResults(),
            'orders'       => $db->table('orders')->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(3)->get()->getResultArray(),
            'week'         => $this->activityWeek($userId),
        ]);
    }

    /**
     * Seven-day activity strip (Bible reading, lessons, journal entries).
     */
    private function activityWeek(int $userId): array
    {
        $db   = db_connect();
        $from = date('Y-m-d', strtotime('-6 days'));
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d        = date('Y-m-d', strtotime("-{$i} days"));
            $days[$d] = ['date' => $d, 'read' => false, 'lessons' => 0, 'journal' => 0];
        }
        foreach ($db->table('bible_reading_log')->where('user_id', $userId)->where('read_date >=', $from)->get()->getResultArray() as $r) {
            if (isset($days[$r['read_date']])) {
                $days[$r['read_date']]['read'] = true;
            }
        }
        foreach ($db->table('lesson_completions lc')->select('DATE(lc.completed_at) AS d, COUNT(*) AS n')->join('enrollments e', 'e.id = lc.enrollment_id')->where('e.user_id', $userId)->where('lc.completed_at >=', $from)->groupBy('d')->get()->getResultArray() as $r) {
            if (isset($days[$r['d']])) {
                $days[$r['d']]['lessons'] = (int) $r['n'];
            }
        }
        foreach ($db->table('journal_entries')->select('DATE(created_at) AS d, COUNT(*) AS n')->where('user_id', $userId)->where('created_at >=', $from)->groupBy('d')->get()->getResultArray() as $r) {
            if (isset($days[$r['d']])) {
                $days[$r['d']]['journal'] = (int) $r['n'];
            }
        }

        return array_values($days);
    }
}
