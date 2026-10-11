<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('site_announcement')) {
    function site_announcement(): ?string
    {
        return site_setting('announcement');
    }
}

if (! function_exists('verse_of_the_day')) {
    /**
     * A well-known verse that changes daily (same for everyone on a given day).
     */
    function verse_of_the_day(?string $date = null): ?array
    {
        $refs = [
            'Jeremiah 29:11', 'Philippians 4:6', 'Philippians 4:13', 'Proverbs 3:5', 'Isaiah 40:31', 'Joshua 1:9', 'Psalms 23:1',
            'Romans 8:28', 'Matthew 6:33', 'John 3:16', '2 Corinthians 5:17', 'Psalms 46:1', 'Isaiah 41:10', 'Hebrews 11:6',
            'James 1:5', 'Psalms 119:105', 'Romans 12:2', 'Galatians 2:20', '1 John 1:9', 'Matthew 11:28', 'Psalms 37:4',
            'Proverbs 4:23', 'Lamentations 3:22', 'Psalms 91:1', 'Ephesians 2:8', 'Romans 15:13', '1 Peter 5:7', 'Mark 11:24',
            'John 14:27', 'Hebrews 4:16', 'Psalms 34:8', 'Isaiah 26:3', '2 Timothy 1:7', 'Romans 8:31', 'Psalms 27:1',
            'Matthew 7:7', 'John 15:5', 'Colossians 3:23', 'Psalms 16:11', 'Philippians 4:19', 'Joshua 1:8', 'Psalms 1:2',
            '1 Thessalonians 5:17', 'Ephesians 6:18', 'James 5:16', 'Psalms 145:18', 'Jeremiah 33:3', 'Matthew 18:20',
            'Romans 8:26', '1 John 5:14', 'Psalms 5:3', 'Psalms 62:8', 'Isaiah 65:24', 'John 16:24', 'Psalms 139:14',
            'Proverbs 16:3', 'Micah 6:8', 'Zephaniah 3:17', 'Psalms 103:2', 'Numbers 6:24', 'Deuteronomy 31:6', '2 Chronicles 7:14',
            'Psalms 121:1', 'Psalms 51:10', 'Isaiah 43:19', 'Habakkuk 2:3', 'Nehemiah 8:10', 'John 10:10', 'John 8:32',
            'Romans 5:8', 'Romans 10:17', '1 Corinthians 13:4', '2 Corinthians 12:9', 'Galatians 5:22', 'Ephesians 3:20',
            'Colossians 3:15', 'Hebrews 13:8', 'James 4:8', '1 Peter 2:9', '1 John 4:18', 'Revelation 3:20', 'Psalms 19:14',
            'Psalms 30:5', 'Psalms 55:22', 'Psalms 84:11', 'Psalms 100:4', 'Proverbs 18:10', 'Isaiah 9:6', 'Isaiah 53:5',
            'Matthew 5:16', 'Matthew 28:20', 'Luke 1:37', 'John 1:5', 'John 11:25', 'Acts 1:8', 'Romans 12:12', 'Philippians 1:6',
        ];
        $ref = \App\Libraries\Scripture::parse($refs[((int) date('z', strtotime($date ?? 'today')) + (int) date('Y', strtotime($date ?? 'today'))) % count($refs)]);
        if ($ref === null) {
            return null;
        }
        try {
            $row = db_connect()->table('bible_verses v')
                ->select('v.*, b.name AS book_name, b.abbrev, b.position AS book_position')
                ->join('bible_books b', 'b.id = v.book_id')
                ->where('b.position', $ref['book'])->where('v.chapter', $ref['chapter'])->where('v.verse', $ref['verse_start'])
                ->get()->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }

        return $row ?: null;
    }
}
