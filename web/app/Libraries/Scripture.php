<?php

namespace App\Libraries;

/**
 * Parses Bible references ("Romans 8:26-27", "1 Cor. 14:2", "Ps 119:15")
 * and turns references inside rich text into reader links.
 *
 * Books are identified by their canonical position (1 = Genesis ... 66 =
 * Revelation), which matches bible_books.position.
 */
class Scripture
{
    /**
     * Canonical names in order. Index + 1 = position.
     */
    public const BOOKS = [
        'Genesis', 'Exodus', 'Leviticus', 'Numbers', 'Deuteronomy', 'Joshua', 'Judges', 'Ruth',
        '1 Samuel', '2 Samuel', '1 Kings', '2 Kings', '1 Chronicles', '2 Chronicles', 'Ezra', 'Nehemiah',
        'Esther', 'Job', 'Psalms', 'Proverbs', 'Ecclesiastes', 'Song of Solomon', 'Isaiah', 'Jeremiah',
        'Lamentations', 'Ezekiel', 'Daniel', 'Hosea', 'Joel', 'Amos', 'Obadiah', 'Jonah', 'Micah', 'Nahum',
        'Habakkuk', 'Zephaniah', 'Haggai', 'Zechariah', 'Malachi', 'Matthew', 'Mark', 'Luke', 'John', 'Acts',
        'Romans', '1 Corinthians', '2 Corinthians', 'Galatians', 'Ephesians', 'Philippians', 'Colossians',
        '1 Thessalonians', '2 Thessalonians', '1 Timothy', '2 Timothy', 'Titus', 'Philemon', 'Hebrews',
        'James', '1 Peter', '2 Peter', '1 John', '2 John', '3 John', 'Jude', 'Revelation',
    ];

    /**
     * Groupings used by the reader's book picker.
     */
    public const GROUPS = [
        'The Law'            => [1, 5],
        'History'            => [6, 17],
        'Poetry & Wisdom'    => [18, 22],
        'Major Prophets'     => [23, 27],
        'Minor Prophets'     => [28, 39],
        'The Gospels'        => [40, 43],
        'Early Church'       => [44, 44],
        "Paul's Letters"     => [45, 57],
        'General Letters'    => [58, 65],
        'Prophecy'           => [66, 66],
    ];

    /**
     * Extra spellings/abbreviations => position.
     */
    private const ALIASES = [
        'gen' => 1, 'gn' => 1, 'ex' => 2, 'exod' => 2, 'lev' => 3, 'lv' => 3, 'num' => 4, 'nm' => 4,
        'deut' => 5, 'dt' => 5, 'josh' => 6, 'judg' => 7, 'jdg' => 7, 'rth' => 8,
        '1 sam' => 9, '2 sam' => 10, '1 kgs' => 11, '2 kgs' => 12, '1 kin' => 11, '2 kin' => 12,
        '1 chron' => 13, '2 chron' => 14, '1 chr' => 13, '2 chr' => 14, 'neh' => 16, 'esth' => 17, 'est' => 17,
        'psalm' => 19, 'ps' => 19, 'psa' => 19, 'pss' => 19, 'prov' => 20, 'prv' => 20, 'pr' => 20,
        'eccl' => 21, 'eccles' => 21, 'ecc' => 21, 'song' => 22, 'song of songs' => 22, 'sos' => 22,
        'isa' => 23, 'jer' => 24, 'lam' => 25, 'ezek' => 26, 'eze' => 26, 'dan' => 27, 'hos' => 28,
        'obad' => 31, 'jon' => 32, 'mic' => 33, 'nah' => 34, 'hab' => 35, 'zeph' => 36, 'hag' => 37,
        'zech' => 38, 'zec' => 38, 'mal' => 39, 'matt' => 40, 'mat' => 40, 'mt' => 40, 'mk' => 41, 'mrk' => 41,
        'lk' => 42, 'luk' => 42, 'jn' => 43, 'jhn' => 43, 'act' => 44, 'rom' => 45, 'rm' => 45,
        '1 cor' => 46, '2 cor' => 47, '1 co' => 46, '2 co' => 47, 'gal' => 48, 'eph' => 49, 'phil' => 50, 'php' => 50,
        'col' => 51, '1 thess' => 52, '2 thess' => 53, '1 thes' => 52, '2 thes' => 53, '1 th' => 52, '2 th' => 53,
        '1 tim' => 54, '2 tim' => 55, '1 ti' => 54, '2 ti' => 55, 'tit' => 56, 'philem' => 57, 'phlm' => 57,
        'heb' => 58, 'jas' => 59, 'jm' => 59, '1 pet' => 60, '2 pet' => 61, '1 pt' => 60, '2 pt' => 61,
        '1 jn' => 62, '2 jn' => 63, '3 jn' => 64, '1 jo' => 62, '2 jo' => 63, '3 jo' => 64, 'jud' => 65, 'rev' => 66,
    ];

    private static ?array $lookup = null;
    private static ?string $pattern = null;

    /**
     * Removes the bundled KJV's editorial markup: "{word}" marks words the
     * translators supplied (kept, unbracketed) and "{x: Heb. y}" holds
     * marginal notes (dropped).
     */
    public static function cleanKjv(string $text): string
    {
        $text = preg_replace('/\s*«[^»]*»/u', '', $text);
        do {
            $before = $text;
            $text   = preg_replace('/\s*\{[^{}]*:[^{}]*\}/u', '', $text);
            $text   = preg_replace('/\{([^{}:]*)\}/u', '$1', $text);
        } while ($text !== $before);
        // Unbalanced leftovers from the source file (a note's orphaned tail).
        $text = preg_replace('/\s+[^\s{}]+\}/u', '', $text);
        $text = str_replace(['{', '}'], '', $text);

        return trim(preg_replace('/\s{2,}/u', ' ', $text));
    }

    public static function bookName(int $position): string
    {
        return self::BOOKS[$position - 1] ?? '';
    }

    public static function slug(int $position): string
    {
        return strtolower(str_replace(' ', '-', self::bookName($position)));
    }

    public static function groupFor(int $position): string
    {
        foreach (self::GROUPS as $label => [$from, $to]) {
            if ($position >= $from && $position <= $to) {
                return $label;
            }
        }

        return '';
    }

    /**
     * Book name/alias (any case, optional trailing dot, "1Cor" or "I Cor") => position.
     */
    public static function bookPosition(string $name): ?int
    {
        $key = self::normalise($name);

        return self::lookup()[$key] ?? null;
    }

    /**
     * @return array{book:int, name:string, chapter:int, verse_start:?int, verse_end:?int, label:string}|null
     */
    public static function parse(string $reference): ?array
    {
        $reference = trim(preg_replace('/\s+/', ' ', $reference));
        if (! preg_match('/^(.+?)\.?\s*(\d{1,3})(?:\s*:\s*(\d{1,3})(?:\s*[-–]\s*(\d{1,3}))?)?$/u', $reference, $m)) {
            return null;
        }
        $book = self::bookPosition($m[1]);
        if ($book === null) {
            return null;
        }
        $chapter = (int) $m[2];
        $start   = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null;
        $end     = isset($m[4]) && $m[4] !== '' ? (int) $m[4] : $start;
        if ($start !== null && $end < $start) {
            $end = $start;
        }

        $label = self::bookName($book) . ' ' . $chapter . ($start ? ':' . $start . ($end !== $start ? '-' . $end : '') : '');

        return ['book' => $book, 'name' => self::bookName($book), 'chapter' => $chapter, 'verse_start' => $start, 'verse_end' => $end, 'label' => $label];
    }

    public static function url(array $ref): string
    {
        return site_url('bible/' . self::slug($ref['book']) . '/' . $ref['chapter']) . ($ref['verse_start'] ? '#v' . $ref['verse_start'] : '');
    }

    /**
     * Links every "Book C:V" reference found in text nodes (not inside tags
     * or existing links).
     */
    public static function linkify(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $parts   = preg_split('/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $inLink  = 0;
        $pattern = self::pattern();
        foreach ($parts as $i => $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] === '<') {
                if (preg_match('/^<a[\s>]/i', $part)) {
                    $inLink++;
                } elseif (preg_match('/^<\/a>/i', $part)) {
                    $inLink = max(0, $inLink - 1);
                }

                continue;
            }
            if ($inLink > 0) {
                continue;
            }
            $parts[$i] = preg_replace_callback($pattern, static function (array $m): string {
                $ref = self::parse($m[0]);
                if ($ref === null) {
                    return $m[0];
                }

                return '<a class="bible-ref" href="' . esc(self::url($ref), 'attr') . '" data-ref="' . esc($ref['label'], 'attr') . '">' . $m[0] . '</a>';
            }, $part);
        }

        return implode('', $parts);
    }

    /**
     * Finds all references in plain text.
     *
     * @return list<array>
     */
    public static function extract(string $text): array
    {
        preg_match_all(self::pattern(), $text, $m);
        $out = [];
        foreach ($m[0] as $raw) {
            if ($ref = self::parse($raw)) {
                $out[] = $ref;
            }
        }

        return $out;
    }

    private static function normalise(string $name): string
    {
        $n = mb_strtolower(trim($name));
        $n = rtrim($n, '.');
        $n = preg_replace('/^(iii|ii|i)\s+/u', '', $n, 1, $roman) ?? $n;
        if ($roman) {
            $num = ['i' => 1, 'ii' => 2, 'iii' => 3][strtok(mb_strtolower(trim($name)), ' ')] ?? '';
            $n   = $num . ' ' . $n;
        }
        $n = preg_replace('/^([123])\s*/u', '$1 ', $n);

        return preg_replace('/\s+/', ' ', $n);
    }

    private static function lookup(): array
    {
        if (self::$lookup === null) {
            self::$lookup = self::ALIASES;
            foreach (self::BOOKS as $i => $name) {
                self::$lookup[mb_strtolower($name)] = $i + 1;
            }
        }

        return self::$lookup;
    }

    private static function pattern(): string
    {
        if (self::$pattern === null) {
            $names = array_keys(self::lookup());
            // Longest first so "1 John" wins over "John".
            usort($names, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            $alts = array_map(static function (string $n): string {
                $q = preg_quote($n, '/');
                // "1 cor" also matches "1Cor" and "1 Cor."
                return preg_replace('/^([123])\\\\? /', '$1\s?', str_replace('\ ', ' ', $q));
            }, $names);
            $alts = array_map(static fn ($a) => str_replace(' ', '\s+', $a), $alts);
            self::$pattern = '/(?<![\w\/#])(?:' . implode('|', $alts) . ')\.?\s?\d{1,3}:\d{1,3}(?:\s*[-–]\s*\d{1,3})?(?![\d:])/iu';
        }

        return self::$pattern;
    }
}
