<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Seeds the full King James Version Bible (public domain) from the bundled
 * app/Database/Data/kjv.json into bible_books and bible_verses.
 *
 * Source text: https://github.com/thiagobodruk/bible (en_kjv.json)
 */
class ImportBible extends BaseCommand
{
    protected $group       = 'Prayer Journal';
    protected $name        = 'bible:import';
    protected $description = 'Imports the full KJV Bible text into bible_books/bible_verses.';

    /**
     * Book order matches the bundled JSON exactly (66 books, standard canon order).
     *
     * @var list<array{0:string,1:string}>
     */
    private const BOOKS = [
        ['Genesis', 'OT'], ['Exodus', 'OT'], ['Leviticus', 'OT'], ['Numbers', 'OT'], ['Deuteronomy', 'OT'],
        ['Joshua', 'OT'], ['Judges', 'OT'], ['Ruth', 'OT'], ['1 Samuel', 'OT'], ['2 Samuel', 'OT'],
        ['1 Kings', 'OT'], ['2 Kings', 'OT'], ['1 Chronicles', 'OT'], ['2 Chronicles', 'OT'], ['Ezra', 'OT'],
        ['Nehemiah', 'OT'], ['Esther', 'OT'], ['Job', 'OT'], ['Psalms', 'OT'], ['Proverbs', 'OT'],
        ['Ecclesiastes', 'OT'], ['Song of Solomon', 'OT'], ['Isaiah', 'OT'], ['Jeremiah', 'OT'], ['Lamentations', 'OT'],
        ['Ezekiel', 'OT'], ['Daniel', 'OT'], ['Hosea', 'OT'], ['Joel', 'OT'], ['Amos', 'OT'],
        ['Obadiah', 'OT'], ['Jonah', 'OT'], ['Micah', 'OT'], ['Nahum', 'OT'], ['Habakkuk', 'OT'],
        ['Zephaniah', 'OT'], ['Haggai', 'OT'], ['Zechariah', 'OT'], ['Malachi', 'OT'],
        ['Matthew', 'NT'], ['Mark', 'NT'], ['Luke', 'NT'], ['John', 'NT'], ['Acts', 'NT'],
        ['Romans', 'NT'], ['1 Corinthians', 'NT'], ['2 Corinthians', 'NT'], ['Galatians', 'NT'], ['Ephesians', 'NT'],
        ['Philippians', 'NT'], ['Colossians', 'NT'], ['1 Thessalonians', 'NT'], ['2 Thessalonians', 'NT'],
        ['1 Timothy', 'NT'], ['2 Timothy', 'NT'], ['Titus', 'NT'], ['Philemon', 'NT'], ['Hebrews', 'NT'],
        ['James', 'NT'], ['1 Peter', 'NT'], ['2 Peter', 'NT'], ['1 John', 'NT'], ['2 John', 'NT'],
        ['3 John', 'NT'], ['Jude', 'NT'], ['Revelation', 'NT'],
    ];

    public function run(array $params)
    {
        $db   = Database::connect();
        $path = APPPATH . 'Database/Data/kjv.json';

        if ((int) $db->table('bible_books')->countAllResults() > 0 && ! CLI::getOption('force')) {
            CLI::write('Bible already imported. Pass --force to re-import.', 'yellow');

            return;
        }

        if (! is_file($path)) {
            CLI::error("Bible data file not found: {$path}");

            return;
        }

        $json = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($path));
        $data = json_decode($json, true);

        if (! is_array($data) || count($data) !== 66) {
            CLI::error('Unexpected Bible data file format.');

            return;
        }

        $db->table('bible_verses')->truncate();
        $db->table('bible_books')->truncate();

        foreach ($data as $i => $book) {
            [$name, $testament] = self::BOOKS[$i];
            $chapters           = $book['chapters'];

            $bookId = $db->table('bible_books')->insert([
                'name'          => $name,
                'abbrev'        => $book['abbrev'],
                'testament'     => $testament,
                'position'      => $i + 1,
                'chapter_count' => count($chapters),
            ]) ? $db->insertID() : null;

            $rows = [];
            foreach ($chapters as $chapterIndex => $verses) {
                foreach ($verses as $verseIndex => $text) {
                    $rows[] = [
                        'book_id' => $bookId,
                        'chapter' => $chapterIndex + 1,
                        'verse'   => $verseIndex + 1,
                        'text'    => $text,
                    ];
                }
            }
            $db->table('bible_verses')->insertBatch($rows);

            CLI::write("  + {$name}: " . count($rows) . ' verses');
        }

        CLI::write('');
        CLI::write(CLI::color('Bible import complete.', 'green'));
    }
}
