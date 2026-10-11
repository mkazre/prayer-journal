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
        return (new \App\Modules\Core\Models\AppSettingModel())->get('announcement');
    }
}

if (! function_exists('verse_of_the_day')) {
    function verse_of_the_day(): ?array
    {
        $model = new \App\Modules\Bible\Models\BibleVerseModel();
        $count = $model->countAllResults(false);
        if ($count === 0) {
            return null;
        }
        // Deterministic per-day pick so everyone sees the same verse today.
        $seed   = (int) date('Ymd');
        $offset = $seed % $count;

        $row = $model->select('bible_verses.*, bible_books.name as book_name, bible_books.abbrev')
            ->join('bible_books', 'bible_books.id = bible_verses.book_id')
            ->orderBy('bible_verses.id', 'asc')
            ->limit(1, $offset)
            ->get()->getRowArray();

        return $row;
    }
}
