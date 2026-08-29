<?php

namespace App\Commands;

use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\LessonModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Smalot\PdfParser\Parser;

/**
 * Imports the "My Prayer Journal" PDF as one draft course with one lesson
 * per "Prayer Secret" chapter (plus an intro and closing lesson). Lessons
 * are left in draft status - the raw PDF text extraction needs a human
 * pass to clean up formatting, hyphenation artifacts, and journaling
 * blocks before publishing.
 */
class ImportPrayerJournal extends BaseCommand
{
    protected $group       = 'Prayer Journal';
    protected $name        = 'import:prayer-journal';
    protected $description = 'Imports the Prayer Journal PDF as a draft course with one lesson per chapter.';

    protected $arguments = [
        'path' => 'Path to the PDF file (defaults to the project-adjacent copy).',
    ];

    public function run(array $params)
    {
        $path = $params[0] ?? 'C:\Users\Mkhaliphi.Mnguni\OneDrive - Smollan\Desktop\Sites\Nomvula\FINAL PRAYER JOURNAL 13 SEPTEMBER 2024.pdf';

        if (! is_file($path)) {
            CLI::error("File not found: {$path}");

            return;
        }

        CLI::write('Parsing PDF text (this can take a few seconds)...');
        $parser = new Parser();
        $text   = $parser->parseFile($path)->getText();

        $chapters = $this->splitIntoChapters($text);

        $courseModel = new CourseModel();
        $existing    = $courseModel->where('slug', 'my-prayer-journal')->first();

        if ($existing) {
            $courseId = $existing['id'];
            CLI::write("Course already exists (id {$courseId}); re-importing lessons.");
            (new LessonModel())->where('course_id', $courseId)->delete();
        } else {
            $adminId  = $this->firstAdminId();
            $courseId = $courseModel->insert([
                'title'       => 'My Prayer Journal',
                'slug'        => 'my-prayer-journal',
                'summary'     => "The Believer's Prayer Companion, by Nomvula Rakolote",
                'description' => 'A devotional course built from the Prayer Journal book: understanding prayer, meditation, the blood covenant, spiritual warfare, and living a life of prayer.',
                'status'      => 'draft',
                'created_by'  => $adminId,
            ], true);
            CLI::write("Created course 'My Prayer Journal' (id {$courseId}), status: draft.");
        }

        $position = 1;
        foreach ($chapters as $chapter) {
            (new LessonModel())->insert([
                'course_id' => $courseId,
                'title'     => $chapter['title'],
                'content'   => $chapter['content'],
                'position'  => $position++,
                'status'    => 'draft',
            ]);
            CLI::write('  + Lesson: ' . $chapter['title'] . ' (' . number_format(strlen($chapter['content'])) . ' chars)');
        }

        CLI::write('');
        CLI::write(CLI::color('Done.', 'green') . ' Review and edit lessons at /instructor/courses/' . $courseId . '/edit before publishing.');
    }

    /**
     * Chapter titles as printed in the table of contents. The PDF's text
     * extraction order jumbles headers/footers around the real in-body
     * heading, so the title text right after "Prayer Secret No. N" at its
     * last occurrence is unreliable - these are taken directly from the ToC.
     *
     * @var array<int,string>
     */
    private const CHAPTER_TITLES = [
        1 => 'Do Away With Religion',
        2 => 'All Your Life Is Prayer',
        3 => 'The Truth About Meditation',
        4 => "About My Father's Business",
        5 => 'Understanding the Blood Covenant',
        6 => 'Fasting And Spiritual Warfare',
    ];

    /**
     * @return list<array{title:string,content:string}>
     */
    private function splitIntoChapters(string $text): array
    {
        // "Prayer Secret No. N" appears once in the table of contents and once
        // again as the real chapter heading; the real heading is the LAST
        // occurrence of each number since the ToC lists them all up front.
        preg_match_all('/Prayer Secret No\.\s*(\d+)/u', $text, $matches, PREG_OFFSET_CAPTURE);

        $lastByNumber = [];
        foreach ($matches[1] as $i => $numMatch) {
            $number = (int) $numMatch[0];
            $offset = $matches[0][$i][1];
            $lastByNumber[$number] = ['offset' => $offset, 'title' => self::CHAPTER_TITLES[$number] ?? ('Chapter ' . $number)];
        }
        ksort($lastByNumber);

        $chapters    = [];
        $numbers     = array_keys($lastByNumber);
        $introEnd    = $numbers === [] ? 2000 : $lastByNumber[$numbers[0]]['offset'];

        // Front matter: How To Use / Introduction / Preamble.
        $introText = trim(substr($text, 0, $introEnd));
        if ($introText !== '') {
            $chapters[] = [
                'title'   => 'Introduction: How To Use This Prayer Journal',
                'content' => $this->clean($introText),
            ];
        }

        foreach ($numbers as $idx => $number) {
            $start = $lastByNumber[$number]['offset'];
            $end   = isset($numbers[$idx + 1]) ? $lastByNumber[$numbers[$idx + 1]]['offset'] : null;

            // Stop each chapter before "Prayer Of Salvation" if it falls inside the last chapter's range.
            $salvationPos = mb_strripos($text, 'Prayer Of Salvation');
            if ($end === null && $salvationPos !== false && $salvationPos > $start) {
                $end = $salvationPos;
            }

            $chunk = $end !== null ? substr($text, $start, $end - $start) : substr($text, $start);

            $chapters[] = [
                'title'   => 'Prayer Secret No. ' . $number . ': ' . $lastByNumber[$number]['title'],
                'content' => $this->clean($chunk),
            ];
        }

        $salvationPos = mb_strripos($text, 'Prayer Of Salvation');
        if ($salvationPos !== false) {
            $chapters[] = [
                'title'   => 'Prayer of Salvation',
                'content' => $this->clean(substr($text, $salvationPos)),
            ];
        }

        return $chapters;
    }

    private function clean(string $text): string
    {
        $text = preg_replace('/\r/', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function firstAdminId(): int
    {
        $row = \Config\Database::connect()->table('auth_groups_users')
            ->whereIn('group', ['admin', 'superadmin'])
            ->get()->getRowArray();

        return $row ? (int) $row['user_id'] : 1;
    }
}
