<?php

namespace App\Modules\Lms\Services;

use App\Libraries\Mailer;
use App\Libraries\Notify;
use App\Modules\Bible\Models\BibleReadingLogModel;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\JournalEntryModel;

/**
 * Personal "come back and learn" emails, run once a day by the cron job.
 *
 * Each email is built from the learner's own data: their name, progress in
 * every course they are taking, the exact next lesson (with a link straight
 * to it), how long they have been away, their Bible streak, journal stats,
 * prayers still waiting, and the verse of the day. Subjects vary with each
 * reminder so they never feel like a template.
 *
 * Rules (Studio → Settings → Emails):
 *   - first reminder after `reminder_after_days` without activity (default 3)
 *   - then every `reminder_repeat_days` (default 7)
 *   - at most `reminder_max` reminders per absence (default 4); it resets
 *     as soon as they study again
 *   - people who have not started any course get up to two "start here" nudges
 *   - anyone can switch reminders off in Account → Emails
 */
class LearningReminders
{
    /**
     * @return array{learners:int, starters:int}
     */
    public static function run(?int $now = null): array
    {
        $now    = $now ?? time();
        $after  = max(1, (int) site_setting('reminder_after_days', '3'));
        $repeat = max(1, (int) site_setting('reminder_repeat_days', '7'));
        $max    = max(0, (int) site_setting('reminder_max', '4'));
        $sent   = ['learners' => 0, 'starters' => 0];
        if ($max === 0) {
            return $sent;
        }
        $db = db_connect();

        // Learners with unfinished courses.
        $rows = $db->table('enrollments e')
            ->select('e.user_id, MAX(COALESCE(e.last_activity_at, e.enrolled_at)) AS last_study', false)
            ->join('users u', 'u.id = e.user_id')
            ->where('e.status', 'active')->where('u.deleted_at IS NULL')
            ->groupStart()->where('u.status IS NULL')->orWhere('u.status !=', 'banned')->groupEnd()
            ->groupBy('e.user_id')->get()->getResultArray();
        foreach ($rows as $r) {
            $userId = (int) $r['user_id'];
            $last   = max(strtotime((string) $r['last_study']), self::lastSeen($userId));
            if ($now - $last < $after * 86400 || ! Notify::wants($userId, 'email_reminders')) {
                continue;
            }
            if (! self::due($userId, 'learning_reminder', $last, $repeat, $max, $now)) {
                continue;
            }
            if (self::sendLearner($userId, $now, $last)) {
                $sent['learners']++;
            }
        }

        // People who signed up but never started a course.
        $starters = $db->table('users u')->select('u.id, u.created_at')
            ->where('u.deleted_at IS NULL')->where('u.created_at <=', date('Y-m-d H:i:s', $now - 2 * 86400))
            ->groupStart()->where('u.status IS NULL')->orWhere('u.status !=', 'banned')->groupEnd()
            ->where('NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.user_id = u.id)', null, false)
            ->limit(500)->get()->getResultArray();
        foreach ($starters as $s) {
            $userId = (int) $s['id'];
            if (! Notify::wants($userId, 'email_reminders') || ! self::due($userId, 'start_reminder', strtotime($s['created_at']), $repeat, 2, $now)) {
                continue;
            }
            if (self::sendStarter($userId)) {
                $sent['starters']++;
            }
        }

        return $sent;
    }

    /**
     * Builds and queues one learner's reminder. Public so Studio can preview it.
     */
    public static function sendLearner(int $userId, ?int $now = null, ?int $lastStudy = null): bool
    {
        $now = $now ?? time();
        $u   = Notify::user($userId);
        if (! $u) {
            return false;
        }
        $courses = self::courseProgress($userId);
        if ($courses === []) {
            return false;
        }
        $lastStudy ??= self::lastSeen($userId);
        $days     = max(1, (int) floor(($now - $lastStudy) / 86400));
        $count    = self::sentSince($userId, 'learning_reminder', $lastStudy);
        $journal  = (new JournalEntryModel())->stats($userId);
        $waiting  = max(0, $journal['prayers'] - $journal['answered']);
        $streak   = (new BibleReadingLogModel())->currentStreak($userId);
        $first    = $courses[0];
        $first1   = strtok($u['name'], ' ');
        $verse    = verse_of_the_day();

        $subjects = [
            $first1 . ', your next lesson is waiting: ' . ($first['next'] ?? $first['title']),
            'We miss you, ' . $first1 . ' — you are ' . $first['progress'] . '% through ' . $first['title'],
            $waiting > 0 ? $first1 . ', ' . $waiting . ' ' . ($waiting === 1 ? 'prayer is' : 'prayers are') . ' waiting in your journal' : $first1 . ', a few quiet minutes with God today?',
            $first1 . ', pick up right where you left off',
        ];
        $subject = $subjects[min($count, count($subjects) - 1)];

        $intro = $days === 1 ? 'It has been a day since your last lesson.' : 'It has been <strong>' . $days . ' days</strong> since your last lesson.';
        $paragraphs = [
            $intro . ' Even ten minutes today keeps your rhythm of prayer and learning going. Here is exactly where you left off:',
        ];

        $items = [];
        foreach (array_slice($courses, 0, 3) as $c) {
            $meta = $c['done'] . ' of ' . $c['total'] . ' lessons complete';
            if ($c['next']) {
                $meta .= '<br>Up next: <strong>' . esc($c['next']) . '</strong>' . ($c['next_minutes'] ? ' · about ' . $c['next_minutes'] . ' min' : '');
            }
            $items[] = ['title' => $c['title'], 'meta' => $meta, 'amount' => $c['progress'] . '%', 'progress' => $c['progress'], 'link' => [$c['progress'] > 0 ? 'Continue' : 'Start the course', $c['url']]];
        }

        $rows = [];
        $rows['Bible reading streak'] = $streak > 0 ? $streak . ' day' . ($streak === 1 ? '' : 's') . ' — keep it going!' : 'Start a new streak today';
        if ($journal['total'] > 0) {
            $rows['Journal entries'] = (string) $journal['total'];
        }
        if ($journal['prayers'] > 0) {
            $rows['Prayers answered'] = $journal['answered'] . ' of ' . $journal['prayers'] . ($waiting ? ' (' . $waiting . ' still waiting)' : '');
        }

        Mailer::queue('learning_reminder', $u['email'], $u['name'], $subject, [
            'heading'    => 'Your journey is waiting, ' . $first1,
            'preheader'  => ($first['next'] ? 'Next up: ' . $first['next'] . '. ' : '') . 'You are ' . $first['progress'] . '% through ' . $first['title'] . '.',
            'paragraphs' => $paragraphs,
            'items'      => $items,
            'rows'       => $rows,
            'verse'      => $verse ? ['text' => $verse['text'], 'reference' => $verse['book_name'] . ' ' . $verse['chapter'] . ':' . $verse['verse']] : null,
            'button'     => [$first['progress'] > 0 ? 'Continue learning' : 'Start learning', $first['url']],
            'secondary'  => $waiting > 0 ? ['Review my prayers', site_url('journal?status=open')] : ['Open my journal', site_url('journal')],
            'footer'     => 'You are receiving this learning reminder from ' . site_name() . '. Turn reminders off any time in your email preferences.',
        ], $userId);

        return true;
    }

    public static function sendStarter(int $userId): bool
    {
        $u = Notify::user($userId);
        if (! $u) {
            return false;
        }
        $course = (new CourseModel())->catalog(['featured' => 1, 'limit' => 1])[0] ?? ((new CourseModel())->catalog(['limit' => 1])[0] ?? null);
        if (! $course) {
            return false;
        }
        $first1 = strtok($u['name'], ' ');
        Mailer::queue('start_reminder', $u['email'], $u['name'], $first1 . ', start your prayer journey today', [
            'heading'    => 'Ready to begin, ' . $first1 . '?',
            'paragraphs' => ['You created your account but have not started a course yet. The best place to begin is <strong>' . esc($course['title']) . '</strong>' . ($course['subtitle'] ? ': ' . esc($course['subtitle']) : '') . '.'],
            'items'      => [['title' => $course['title'], 'meta' => (int) $course['lesson_count'] . ' lessons · guided journal prompts · certificate', 'link' => ['See the course', site_url('courses/' . $course['slug'])]]],
            'button'     => ['Start the course', site_url('courses/' . $course['slug'])],
            'footer'     => 'You are receiving this because you signed up at ' . site_name() . '. Turn reminders off in your email preferences.',
        ], $userId);

        return true;
    }

    /**
     * Unfinished courses with progress and the exact next lesson.
     *
     * @return list<array{title:string, url:string, progress:int, done:int, total:int, next:?string, next_minutes:int}>
     */
    public static function courseProgress(int $userId): array
    {
        $db   = db_connect();
        $rows = $db->table('enrollments e')->select('e.*, c.title, c.slug')->join('courses c', 'c.id = e.course_id')
            ->where('e.user_id', $userId)->where('e.status', 'active')->where('c.deleted_at IS NULL')->where('c.status', 'published')
            ->orderBy('COALESCE(e.last_activity_at, e.enrolled_at)', 'DESC', false)->get()->getResultArray();
        $out = [];
        foreach ($rows as $e) {
            $course = (new CourseModel())->find($e['course_id']);
            if (! $course || ! CourseAccess::resolve($course, $userId)) {
                continue;
            }
            $flat  = Curriculum::flatten(Curriculum::annotate(Curriculum::load((int) $course['id']), $course, $e, $userId));
            $less  = array_values(array_filter($flat, static fn ($i) => $i['kind'] === 'lesson'));
            $done  = count(array_filter($less, static fn ($i) => $i['done']));
            $next  = null;
            foreach ($flat as $i) {
                if (! $i['done'] && ! $i['locked']) {
                    $next = $i;
                    break;
                }
            }
            $url = site_url('study/' . $course['slug']);
            if ($next) {
                $url = site_url('study/' . $course['slug'] . '/' . $next['kind'] . '/' . $next['id']);
            }
            $out[] = [
                'title' => $course['title'], 'url' => $url, 'progress' => (int) $e['progress_percent'], 'done' => $done, 'total' => count($less),
                'next' => $next['title'] ?? null, 'next_minutes' => (int) ($next['duration'] ?? 0),
            ];
        }

        return $out;
    }

    private static function due(int $userId, string $template, int $since, int $repeatDays, int $max, int $now): bool
    {
        $db   = db_connect();
        $sent = $db->table('email_queue')->where(['user_id' => $userId, 'template' => $template])->where('created_at >', date('Y-m-d H:i:s', $since));
        if ($sent->countAllResults(false) >= $max) {
            return false;
        }
        $last = $db->table('email_queue')->selectMax('created_at')->where(['user_id' => $userId, 'template' => $template])->get()->getRow('created_at');

        return ! $last || strtotime($last) <= $now - $repeatDays * 86400 + 3600;
    }

    private static function sentSince(int $userId, string $template, int $since): int
    {
        return db_connect()->table('email_queue')->where(['user_id' => $userId, 'template' => $template])->where('created_at >', date('Y-m-d H:i:s', $since))->countAllResults();
    }

    private static function lastSeen(int $userId): int
    {
        $row = db_connect()->table('users')->select('last_active')->where('id', $userId)->get()->getRow('last_active');

        return $row ? (int) strtotime($row) : 0;
    }
}
