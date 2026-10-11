<?php

namespace App\Commands;

use App\Libraries\HtmlSanitizer;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\QuizModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Builds the "My Prayer Journal" course from the structured book content in
 * app/Database/Data/prayer-journal-course.json (generated from the PDF by
 * scripts/build_prayer_journal_course.py).
 *
 * The result is an ordinary course: every module, lesson, prompt, quiz and
 * setting can be edited afterwards in Studio.
 *
 *   php spark import:prayer-journal [--author=email|id] [--draft] [--force]
 */
class ImportPrayerJournal extends BaseCommand
{
    protected $group       = 'Prayer Journal';
    protected $name        = 'import:prayer-journal';
    protected $description = 'Creates the "My Prayer Journal" course (6 modules, 121 devotions, quizzes) from the bundled book content.';
    protected $usage       = 'import:prayer-journal [options]';
    protected $options     = [
        '--author' => 'Email or user id of the course owner (defaults to the first admin).',
        '--draft'  => 'Leave the course unpublished.',
        '--force'  => 'Replace the course if it already exists (deletes its lessons, progress and journal links).',
        '--path'   => 'Alternative JSON content file.',
    ];

    public const SLUG = 'my-prayer-journal';

    public function run(array $params)
    {
        $db   = Database::connect();
        $path = CLI::getOption('path') ?: APPPATH . 'Database/Data/prayer-journal-course.json';
        if (! is_file($path)) {
            CLI::error("Content file not found: {$path}");

            return EXIT_ERROR;
        }
        $book = json_decode((string) file_get_contents($path), true);
        if (! is_array($book) || empty($book['secrets'])) {
            CLI::error('Content file is not valid course JSON.');

            return EXIT_ERROR;
        }

        $authorId = $this->resolveAuthor($db, CLI::getOption('author'));
        if (! $authorId) {
            CLI::error('No user found to own the course. Create an admin first, e.g.:');
            CLI::write('  php spark shield:user create -n admin -e admin@example.com');
            CLI::write('  php spark shield:user addgroup -n admin -g superadmin');

            return EXIT_ERROR;
        }

        $courses  = new CourseModel();
        $existing = $courses->withDeleted()->where('slug', self::SLUG)->first();
        if ($existing && ! CLI::getOption('force')) {
            CLI::write('The Prayer Journal course already exists (id ' . $existing['id'] . '). Pass --force to rebuild it.', 'yellow');

            return EXIT_SUCCESS;
        }

        $db->transStart();
        if ($existing) {
            $this->deleteCourse($db, (int) $existing['id']);
        }

        $categoryId = $this->category($db, 'Prayer & Devotion');
        $now        = date('Y-m-d H:i:s');
        $publish    = ! CLI::getOption('draft');

        $settings = array_merge(CourseModel::DEFAULT_SETTINGS, [
            'journal'               => true,
            'certificate_signatory' => 'Nomvula Rakolote',
            'certificate_role'      => 'Author, My Prayer Journal',
            'certificate_title'     => 'Certificate of Completion',
            'certificate_message'   => 'has faithfully completed the six prayer secrets and guided devotions of',
            'welcome_message'       => 'Welcome! Start with "How to Use This Prayer Journal", then take one devotion a day. Every devotion ends with space for your prayer points, meditation and gratitude. What you write is private and is saved to your Prayer Journal, where you can mark prayers as answered.',
            'completion_message'    => 'You have walked through all six prayer secrets. Keep journaling — your Prayer Journal stays with you, and every answered prayer is a testimony.',
            'default_prompts'       => [
                ['key' => 'prayer', 'label' => 'My Prayer Points and Supporting Scriptures', 'hint' => 'Write each petition with a scripture that supports it. You can tick it off as answered in your journal.', 'kind' => 'prayer'],
                ['key' => 'meditation', 'label' => 'My Meditation', 'hint' => '“…you shall meditate on it day and night…” (Joshua 1:8)', 'kind' => 'meditation'],
                ['key' => 'gratitude', 'label' => 'My Gratitude', 'hint' => '“…in everything, by prayer and petition, with thanksgiving, continue to make your wants known to God” (Philippians 4:6)', 'kind' => 'gratitude'],
            ],
        ]);

        $courseId = $courses->insert([
            'category_id'     => $categoryId,
            'title'           => "My Prayer Journal: The Believer's Prayer Companion",
            'slug'            => self::SLUG,
            'subtitle'        => 'Six prayer secrets and 121 guided devotions that make prayer a lifestyle',
            'summary'         => 'A guided, journal-based course that takes you from religious routine to real fellowship with God — one devotion, one prayer point and one note of gratitude at a time.',
            'description'     => HtmlSanitizer::clean($this->description()),
            'price_cents'     => 0,
            'currency'        => store_currency(),
            'access_mode'     => 'free',
            'status'          => $publish ? 'published' : 'draft',
            'published_at'    => $publish ? $now : null,
            'created_by'      => $authorId,
            'level'           => 'all',
            'language'        => 'English',
            'tags'            => 'prayer, devotional, journaling, meditation, fasting, covenant, holy spirit',
            'what_you_learn'  => implode("\n", [
                'Pray from relationship instead of religious routine',
                'Hear God\'s voice through His word and the Holy Spirit',
                'Keep a prayer journal and track answered prayers',
                'Practise biblical meditation on scripture',
                'Pray about your Father\'s business — for others and the kingdom',
                'Approach God with confidence through the blood covenant',
                'Understand the true place of fasting and spiritual warfare',
                'Build a daily rhythm of prayer, meditation and gratitude',
            ]),
            'requirements'    => implode("\n", [
                'A Bible — the full King James Version is built into this site',
                '10 to 15 minutes a day for a devotion and your journal',
                'An open heart and a willingness to write honestly',
            ]),
            'audience'        => implode("\n", [
                'Believers who want prayer to become a lifestyle, not a chore',
                'New Christians learning how to pray',
                'Small groups and prayer teams working through the journal together',
            ]),
            'author_name'     => 'Nomvula Rakolote',
            'author_bio'      => 'Nomvula Rakolote is the author of My Prayer Journal: The Believer\'s Prayer Companion (first published 2021, revised 2024). Through Timeless Wisdom she teaches believers to make prayer a lifestyle rooted in the word of God.',
            'settings'        => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_featured'     => 1,
        ], true);
        if (! $courseId) {
            $db->transRollback();
            CLI::error('Could not create the course: ' . implode(' ', $courses->errors()));

            return EXIT_ERROR;
        }
        $db->table('course_instructors')->insert(['course_id' => $courseId, 'user_id' => $authorId, 'role' => 'owner', 'created_at' => $now]);

        $position = 0;
        $reflect  = [['key' => 'reflection', 'label' => 'My Reflection', 'hint' => 'What is the Holy Spirit highlighting to you from this teaching?', 'kind' => 'note']];

        // Module 0 — welcome.
        $sectionId = $this->section($db, $courseId, 'Welcome: Before You Begin', 'How this journal works, wisdom from Proverbs 8, and what really happens in the prayer closet.', 1);
        $this->lesson($db, $courseId, $sectionId, ++$position, 'How to Use This Prayer Journal', 'text', $book['how_to'], null, 5, true, null, [], 'Seven habits that will get the most out of every devotion.');
        $this->lesson($db, $courseId, $sectionId, ++$position, 'Preamble: Whatever You Do, Seek Wisdom', 'text', $book['preamble'], 'Proverbs 8:17', 6, true, null, [], 'Wisdom speaks: her origin, intentions, advice and promises (Proverbs 8).');
        $this->lesson($db, $courseId, $sectionId, ++$position, 'Introduction to Prayer', 'text', $book['introduction'], 'Proverbs 24:3-4', 8, true, null,
            [['key' => 'closet', 'label' => 'What will you come out with from your prayer closet?', 'hint' => 'Joseph came out with a kingdom; Hannah with a prophet; Esther with deliverance…', 'kind' => 'prayer']],
            'Prayer is fellowship, not a chore — and no one leaves God\'s presence empty-handed.');

        $day       = 0;
        $quizzes   = new QuizModel();
        $lessonCnt = 3;
        foreach ($book['secrets'] as $secret) {
            $n         = (int) $secret['num'];
            $sectionId = $this->section($db, $courseId, 'Secret ' . $n . ' · ' . $secret['title'], $secret['summary'] ?? null, $n + 1);
            $words     = str_word_count(strip_tags($secret['teaching']));
            $this->lesson($db, $courseId, $sectionId, ++$position, 'Prayer Secret No. ' . $n . ': ' . $secret['title'], 'text', $secret['teaching'], null, max(5, (int) ceil($words / 180) + 3), $n === 1, null, $reflect, $secret['summary'] ?? null);
            $lessonCnt++;

            foreach ($secret['devotions'] as $devotion) {
                $day++;
                $this->lesson(
                    $db, $courseId, $sectionId, ++$position, $devotion['title'], 'devotion', $devotion['html'], $devotion['reference'],
                    10, false, $day, [], excerpt($devotion['html'], 150)
                );
                $lessonCnt++;
            }

            if (! empty($secret['quiz'])) {
                $quizId = $quizzes->insert([
                    'course_id'         => $courseId,
                    'section_id'        => $sectionId,
                    'title'             => 'Secret ' . $n . ' Review: ' . $secret['title'],
                    'instructions'      => 'A short review of the teaching and devotions in this block. You can retake it as often as you like; your best score counts.',
                    'pass_percent'      => 70,
                    'time_limit_mins'   => null,
                    'max_attempts'      => 0,
                    'position'          => ++$position,
                    'shuffle_questions' => 0,
                    'show_answers'      => 1,
                    'status'            => 'published',
                ], true);
                $quizzes->replaceQuestions((int) $quizId, $this->quizQuestions($secret['quiz']));
            }
        }

        // Closing module.
        $sectionId = $this->section($db, $courseId, 'Closing: Prayer of Salvation & Next Steps', 'An invitation to receive Jesus, and a final reflection on your journey.', 8);
        $this->lesson($db, $courseId, $sectionId, ++$position, 'Prayer of Salvation', 'text', $book['salvation'], 'John 3:16', 5, true, null,
            [['key' => 'testimony', 'label' => 'My Testimony', 'hint' => 'Write what God has done in you through this journey.', 'kind' => 'gratitude']],
            'God loves you — no matter who you are or what your past was like.');
        $lessonCnt++;
        $db->table('assignments')->insert([
            'course_id'    => $courseId,
            'section_id'   => $sectionId,
            'title'        => 'Final Reflection: What Did You Come Out With?',
            'instructions' => "Look back over your Prayer Journal. In a few paragraphs, share:\n\n1. One religious habit you have let go of.\n2. A prayer you have seen answered (or are still standing on), with its scripture.\n3. How you will keep a daily rhythm of prayer, meditation and gratitude.",
            'max_points'   => 10,
            'position'     => ++$position,
            'allow_file'   => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        $db->transComplete();
        if (! $db->transStatus()) {
            CLI::error('Import failed and was rolled back.');

            return EXIT_ERROR;
        }

        CLI::write(CLI::color('Created "My Prayer Journal" course', 'green') . " (id {$courseId}): 8 modules, {$lessonCnt} lessons, {$day} devotions, " . count($book['secrets']) . ' quizzes, 1 assignment.');
        CLI::write('Edit it in Studio: ' . site_url('studio/courses/' . $courseId));

        return EXIT_SUCCESS;
    }

    private function resolveAuthor($db, $option): ?int
    {
        if ($option) {
            if (ctype_digit((string) $option)) {
                return $db->table('users')->where('id', (int) $option)->countAllResults() ? (int) $option : null;
            }
            $row = $db->table('auth_identities')->where('type', 'email_password')->where('secret', $option)->get()->getRowArray();

            return $row ? (int) $row['user_id'] : null;
        }
        $row = $db->table('auth_groups_users')->whereIn('group', ['superadmin', 'admin'])->orderBy('user_id')->get()->getRowArray();
        if ($row) {
            return (int) $row['user_id'];
        }
        $row = $db->table('users')->orderBy('id')->get()->getRowArray();

        return $row ? (int) $row['id'] : null;
    }

    private function category($db, string $name): int
    {
        $slug = url_title($name, '-', true);
        $row  = $db->table('course_categories')->where('slug', $slug)->get()->getRowArray();
        if ($row) {
            return (int) $row['id'];
        }
        $db->table('course_categories')->insert(['name' => $name, 'slug' => $slug, 'created_at' => date('Y-m-d H:i:s')]);

        return (int) $db->insertID();
    }

    private function section($db, int $courseId, string $title, ?string $summary, int $position): int
    {
        $now = date('Y-m-d H:i:s');
        $db->table('course_sections')->insert(['course_id' => $courseId, 'title' => $title, 'summary' => $summary, 'position' => $position, 'created_at' => $now, 'updated_at' => $now]);

        return (int) $db->insertID();
    }

    private function lesson($db, int $courseId, int $sectionId, int $position, string $title, string $type, string $html, ?string $reference, int $minutes, bool $preview, ?int $dripDay, array $prompts, ?string $summary): int
    {
        $now = date('Y-m-d H:i:s');
        $db->table('lessons')->insert([
            'course_id'       => $courseId,
            'section_id'      => $sectionId,
            'title'           => $title,
            'type'            => $type,
            'summary'         => $summary ? mb_substr($summary, 0, 500) : null,
            'content'         => HtmlSanitizer::clean($html),
            'bible_reference' => $reference,
            'position'        => $position,
            'price_cents'     => 0,
            'status'          => 'published',
            'duration_mins'   => $minutes,
            'is_preview'      => $preview ? 1 : 0,
            'drip_days'       => $dripDay !== null ? $dripDay - 1 : null,
            'settings'        => json_encode(['prompts' => $prompts, 'resources' => [], 'key_verse' => $reference ?? ''], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return (int) $db->insertID();
    }

    private function quizQuestions(array $questions): array
    {
        $out = [];
        foreach ($questions as $q) {
            $out[] = [
                'question'     => $q['question'],
                'type'         => $q['type'],
                'points'       => 1,
                'options'      => $q['options'] ?? [],
                'correct'      => $q['correct'] ?? [],
                'correct_text' => $q['correct_text'] ?? null,
                'explanation'  => $q['explanation'] ?? null,
            ];
        }

        return $out;
    }

    private function deleteCourse($db, int $courseId): void
    {
        $lessonIds = array_column($db->table('lessons')->select('id')->where('course_id', $courseId)->get()->getResultArray(), 'id');
        $quizIds   = array_column($db->table('quizzes')->select('id')->where('course_id', $courseId)->get()->getResultArray(), 'id');
        $assignIds = array_column($db->table('assignments')->select('id')->where('course_id', $courseId)->get()->getResultArray(), 'id');
        $enrollIds = array_column($db->table('enrollments')->select('id')->where('course_id', $courseId)->get()->getResultArray(), 'id');

        if ($quizIds) {
            $questionIds = array_column($db->table('quiz_questions')->select('id')->whereIn('quiz_id', $quizIds)->get()->getResultArray(), 'id');
            $attemptIds  = array_column($db->table('quiz_attempts')->select('id')->whereIn('quiz_id', $quizIds)->get()->getResultArray(), 'id');
            if ($attemptIds) {
                $db->table('quiz_answers')->whereIn('attempt_id', $attemptIds)->delete();
            }
            $db->table('quiz_attempts')->whereIn('quiz_id', $quizIds)->delete();
            if ($questionIds) {
                $db->table('quiz_options')->whereIn('question_id', $questionIds)->delete();
            }
            $db->table('quiz_questions')->whereIn('quiz_id', $quizIds)->delete();
            $db->table('quizzes')->whereIn('id', $quizIds)->delete();
        }
        if ($assignIds) {
            $db->table('assignment_submissions')->whereIn('assignment_id', $assignIds)->delete();
            $db->table('assignments')->whereIn('id', $assignIds)->delete();
        }
        if ($enrollIds) {
            $db->table('lesson_completions')->whereIn('enrollment_id', $enrollIds)->delete();
        }
        if ($lessonIds) {
            $db->table('lesson_notes')->whereIn('lesson_id', $lessonIds)->delete();
            $db->table('course_qa_threads')->whereIn('lesson_id', $lessonIds)->delete();
            $db->table('lesson_unlocks')->whereIn('lesson_id', $lessonIds)->delete();
            $db->table('journal_entries')->whereIn('lesson_id', $lessonIds)->update(['lesson_id' => null]);
        }
        $db->table('journal_entries')->where('course_id', $courseId)->update(['course_id' => null]);
        foreach (['enrollments', 'lessons', 'course_sections', 'course_reviews', 'certificates', 'course_instructors', 'course_announcements', 'gradebook_entries'] as $table) {
            $db->table($table)->where('course_id', $courseId)->delete();
        }
        $db->table('courses')->where('id', $courseId)->delete();
    }

    private function description(): string
    {
        return <<<'HTML'
<p><strong>My Prayer Journal</strong> is more than a book to read — it is a companion for your secret place. This course brings Nomvula Rakolote's journal online so you can work through it at your own pace, with the whole Bible one tap away and a private journal that remembers every prayer.</p>
<h3>How it works</h3>
<ul>
<li><strong>Six prayer secrets.</strong> Each block opens with a teaching that reshapes how you see prayer — from doing away with religion to understanding the blood covenant.</li>
<li><strong>121 daily devotions.</strong> Short readings anchored in a key scripture, shown in full from the King James Version.</li>
<li><strong>Three journaling prompts every day.</strong> Record your prayer points with supporting scriptures, your meditation and your gratitude. Entries save automatically to your private Prayer Journal.</li>
<li><strong>Track answered prayers.</strong> Tick off prayer points as God answers and add a note of testimony.</li>
<li><strong>Review quizzes and a certificate.</strong> A short review after each secret, and a certificate of completion at the end.</li>
</ul>
<div class="callout">The journal is free from calendar dates — go at the pace the Holy Spirit leads. If you prefer a daily rhythm, your instructor can switch on one-devotion-a-day release.</div>
HTML;
    }
}
