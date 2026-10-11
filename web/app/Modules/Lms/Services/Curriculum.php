<?php

namespace App\Modules\Lms\Services;

use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\LessonModel;
use Config\Database;

/**
 * Builds a course's ordered curriculum (modules containing lessons, quizzes
 * and assignments), resolves access rules (drip schedule, sequential
 * progression, free previews) and tracks progress.
 */
class Curriculum
{
    /**
     * @return list<array{id:int|null,title:string,summary:?string,items:list<array>}>
     */
    public static function load(int $courseId, bool $publishedOnly = true): array
    {
        $db       = Database::connect();
        $sections = $db->table('course_sections')->where('course_id', $courseId)->orderBy('position')->orderBy('id')->get()->getResultArray();

        $lessons = $db->table('lessons')->where('course_id', $courseId);
        if ($publishedOnly) {
            $lessons->where('status', 'published');
        }
        $lessons = $lessons->orderBy('position')->orderBy('id')->get()->getResultArray();

        $quizzes = $db->table('quizzes')->where('course_id', $courseId);
        if ($publishedOnly) {
            $quizzes->where('status', 'published');
        }
        $quizzes = $quizzes->orderBy('position')->orderBy('id')->get()->getResultArray();
        $qCounts = [];
        if ($quizzes !== []) {
            foreach ($db->table('quiz_questions')->select('quiz_id, COUNT(*) AS n')->whereIn('quiz_id', array_column($quizzes, 'id'))->groupBy('quiz_id')->get()->getResultArray() as $r) {
                $qCounts[(int) $r['quiz_id']] = (int) $r['n'];
            }
        }

        $assignments = $db->table('assignments')->where('course_id', $courseId)->orderBy('position')->orderBy('id')->get()->getResultArray();

        $bySection = [];
        foreach ($lessons as $l) {
            $bySection[(int) $l['section_id']][] = [
                'kind' => 'lesson', 'id' => (int) $l['id'], 'title' => $l['title'], 'type' => $l['type'] ?: 'text',
                'position' => (int) $l['position'], 'duration' => (int) $l['duration_mins'], 'is_preview' => (bool) $l['is_preview'],
                'drip_days' => $l['drip_days'] !== null ? (int) $l['drip_days'] : null, 'available_at' => $l['available_at'],
                'status' => $l['status'], 'price_cents' => (int) $l['price_cents'], 'summary' => $l['summary'],
                'bible_reference' => $l['bible_reference'],
            ];
        }
        foreach ($quizzes as $q) {
            $bySection[(int) $q['section_id']][] = [
                'kind' => 'quiz', 'id' => (int) $q['id'], 'title' => $q['title'], 'type' => 'quiz', 'position' => (int) $q['position'],
                'duration' => (int) $q['time_limit_mins'], 'is_preview' => false, 'drip_days' => null, 'available_at' => null,
                'status' => $q['status'] ?? 'published', 'questions' => $qCounts[(int) $q['id']] ?? 0, 'pass_percent' => (int) $q['pass_percent'],
            ];
        }
        foreach ($assignments as $a) {
            $bySection[(int) $a['section_id']][] = [
                'kind' => 'assignment', 'id' => (int) $a['id'], 'title' => $a['title'], 'type' => 'assignment', 'position' => (int) $a['position'],
                'duration' => 0, 'is_preview' => false, 'drip_days' => null, 'available_at' => null, 'status' => 'published',
                'due_at' => $a['due_at'],
            ];
        }

        $out = [];
        foreach ($sections as $s) {
            $items = $bySection[(int) $s['id']] ?? [];
            unset($bySection[(int) $s['id']]);
            $out[] = ['id' => (int) $s['id'], 'title' => $s['title'], 'summary' => $s['summary'], 'items' => self::sortItems($items)];
        }
        // Anything not in a module (legacy content, or a deleted module).
        $loose = [];
        foreach ($bySection as $items) {
            $loose = array_merge($loose, $items);
        }
        if ($loose !== []) {
            array_unshift($out, ['id' => null, 'title' => $sections === [] ? 'Course content' : 'General', 'summary' => null, 'items' => self::sortItems($loose)]);
        }

        return $out;
    }

    private static function sortItems(array $items): array
    {
        usort($items, static fn ($a, $b) => [$a['position'], $a['kind'] !== 'lesson', $a['id']] <=> [$b['position'], $b['kind'] !== 'lesson', $b['id']]);

        return $items;
    }

    /**
     * Flattened, ordered list of items across modules.
     */
    public static function flatten(array $sections): array
    {
        $flat = [];
        foreach ($sections as $s) {
            foreach ($s['items'] as $item) {
                $item['section_title'] = $s['title'];
                $flat[]                = $item;
            }
        }

        return $flat;
    }

    public static function key(array $item): string
    {
        return $item['kind'] . ':' . $item['id'];
    }

    /**
     * Annotates every item with completion and lock state for one learner.
     *
     * @param array|null $enrollment enrollments row (null for previews)
     */
    public static function annotate(array $sections, array $course, ?array $enrollment, int $userId): array
    {
        $settings  = CourseModel::settings($course);
        $db        = Database::connect();
        $doneLessons = [];
        $passedQuizzes = [];
        $submitted = [];
        if ($enrollment) {
            $doneLessons = array_map('intval', array_column($db->table('lesson_completions')->select('lesson_id')->where('enrollment_id', $enrollment['id'])->get()->getResultArray(), 'lesson_id'));
            $passedQuizzes = (new \App\Modules\Lms\Models\QuizAttemptModel())->passedQuizIds($userId, (int) $course['id']);
            $submitted = array_map('intval', array_column($db->table('assignment_submissions s')->select('s.assignment_id')->join('assignments a', 'a.id = s.assignment_id')->where('s.user_id', $userId)->where('a.course_id', $course['id'])->get()->getResultArray(), 'assignment_id'));
        }
        $enrolledAt   = $enrollment ? strtotime((string) $enrollment['enrolled_at']) : time();
        $previousDone = true;
        $now          = time();

        foreach ($sections as &$section) {
            $doneCount = 0;
            foreach ($section['items'] as &$item) {
                $done = match ($item['kind']) {
                    'lesson'     => in_array($item['id'], $doneLessons, true),
                    'quiz'       => in_array($item['id'], $passedQuizzes, true),
                    'assignment' => in_array($item['id'], $submitted, true),
                };
                $locked = false;
                $reason = null;
                $opensAt = null;
                if (! $enrollment) {
                    $locked = ! ($item['kind'] === 'lesson' && $item['is_preview']);
                    $reason = $locked ? 'Enroll to unlock' : null;
                } else {
                    if ($item['available_at'] && strtotime($item['available_at']) > $now) {
                        $opensAt = strtotime($item['available_at']);
                    }
                    if ($settings['drip'] === 'enrollment' && $item['drip_days'] !== null && $item['drip_days'] > 0) {
                        $dripAt = $enrolledAt + $item['drip_days'] * 86400;
                        if ($dripAt > $now) {
                            $opensAt = max($opensAt ?? 0, $dripAt);
                        }
                    }
                    if ($opensAt && $opensAt > $now) {
                        $locked = true;
                        $reason = 'Opens ' . date('j M Y', $opensAt);
                    } elseif ($settings['sequential'] && ! $previousDone && ! $done) {
                        $locked = true;
                        $reason = 'Complete the previous step first';
                    }
                }
                $item['done']    = $done;
                $item['locked']  = $locked;
                $item['reason']  = $reason;
                $item['opens_at'] = $opensAt;
                $previousDone    = $done;
                $doneCount += $done ? 1 : 0;
            }
            unset($item);
            $section['done_count'] = $doneCount;
        }

        return $sections;
    }

    /**
     * Recomputes progress for an enrollment and issues a certificate on completion.
     */
    public static function recalculate(int $enrollmentId): array
    {
        $db         = Database::connect();
        $enrollment = $db->table('enrollments')->where('id', $enrollmentId)->get()->getRowArray();
        $course     = (new CourseModel())->withDeleted()->find($enrollment['course_id']);
        $settings   = CourseModel::settings($course);

        $lessonIds = array_map('intval', array_column($db->table('lessons')->select('id')->where('course_id', $course['id'])->where('status', 'published')->get()->getResultArray(), 'id'));
        $done      = $lessonIds === [] ? 0 : $db->table('lesson_completions')->where('enrollment_id', $enrollmentId)->whereIn('lesson_id', $lessonIds)->countAllResults();
        $total     = count($lessonIds);

        $quizzesOk = true;
        if ($settings['completion'] === 'lessons_quizzes') {
            $quizIds = array_map('intval', array_column($db->table('quizzes')->select('id')->where('course_id', $course['id'])->where('status', 'published')->get()->getResultArray(), 'id'));
            $passed  = (new \App\Modules\Lms\Models\QuizAttemptModel())->passedQuizIds((int) $enrollment['user_id'], (int) $course['id']);
            $quizzesOk = array_diff($quizIds, $passed) === [];
            $total += count($quizIds);
            $done  += count(array_intersect($quizIds, $passed));
        }

        $percent  = $total > 0 ? (int) floor(($done / $total) * 100) : 0;
        if ($done > 0 && $percent === 0) {
            $percent = 1;
        }
        $complete = $total > 0 && $done >= $total && $quizzesOk;
        if ($complete) {
            $percent = 100;
        }
        $data = ['progress_percent' => $percent, 'last_activity_at' => date('Y-m-d H:i:s')];
        if ($complete && $enrollment['status'] !== 'completed') {
            $data['status']       = 'completed';
            $data['completed_at'] = date('Y-m-d H:i:s');
        } elseif (! $complete && $enrollment['status'] === 'completed') {
            $data['status'] = 'active';
            $data['completed_at'] = null;
        }
        $db->table('enrollments')->where('id', $enrollmentId)->update($data);

        if ($complete && $settings['certificate_enabled']) {
            $exists = $db->table('certificates')->where(['course_id' => $course['id'], 'user_id' => $enrollment['user_id']])->countAllResults();
            if (! $exists) {
                $db->table('certificates')->insert([
                    'course_id'        => $course['id'],
                    'user_id'          => $enrollment['user_id'],
                    'certificate_code' => strtoupper(bin2hex(random_bytes(6))),
                    'issued_at'        => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return ['percent' => $percent, 'complete' => $complete, 'done' => $done, 'total' => $total];
    }

    public static function totalMinutes(array $sections): int
    {
        $m = 0;
        foreach ($sections as $s) {
            foreach ($s['items'] as $i) {
                $m += (int) $i['duration'];
            }
        }

        return $m;
    }

    public static function countItems(array $sections, ?string $kind = null): int
    {
        $n = 0;
        foreach ($sections as $s) {
            foreach ($s['items'] as $i) {
                $n += ($kind === null || $i['kind'] === $kind) ? 1 : 0;
            }
        }

        return $n;
    }

    public static function duration(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'Self-paced';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return ($h ? $h . 'h ' : '') . ($m ? $m . 'm' : '');
    }
}
