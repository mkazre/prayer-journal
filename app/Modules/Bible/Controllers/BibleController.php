<?php

namespace App\Modules\Bible\Controllers;

use App\Controllers\BaseController;
use App\Modules\Bible\Models\BibleBookModel;
use App\Modules\Bible\Models\BibleBookmarkModel;
use App\Modules\Bible\Models\BibleHighlightModel;
use App\Modules\Bible\Models\BibleNoteModel;
use App\Modules\Bible\Models\BibleReadingLogModel;
use App\Modules\Bible\Models\BibleVerseModel;

class BibleController extends BaseController
{
    public function index(): string
    {
        $books  = (new BibleBookModel())->all();
        $streak = auth()->loggedIn() ? (new BibleReadingLogModel())->currentStreak(auth()->id()) : 0;

        return view('Bible/index', ['title' => 'The Bible', 'books' => $books, 'streak' => $streak]);
    }

    public function chapter(string $abbrev, int $chapter): string
    {
        $bookModel = new BibleBookModel();
        $book      = $bookModel->where('abbrev', $abbrev)->first();
        if (! $book) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $verses  = (new BibleVerseModel())->chapter($book['id'], $chapter);
        $verseIds = array_column($verses, 'id');

        if (auth()->loggedIn()) {
            (new BibleReadingLogModel())->logToday(auth()->id());
        }

        $highlights = auth()->loggedIn() ? (new BibleHighlightModel())->forChapter(auth()->id(), $verseIds) : [];
        $bookmarks  = [];
        if (auth()->loggedIn() && $verseIds !== []) {
            $rows = (new BibleBookmarkModel())->where('user_id', auth()->id())->whereIn('verse_id', $verseIds)->findAll();
            $bookmarks = array_column($rows, 'verse_id');
        }

        return view('Bible/chapter', [
            'title'      => $book['name'] . ' ' . $chapter,
            'book'       => $book,
            'chapter'    => $chapter,
            'verses'     => $verses,
            'highlights' => $highlights,
            'bookmarks'  => $bookmarks,
            'books'      => (new BibleBookModel())->all(),
        ]);
    }

    public function search(): string
    {
        $term    = (string) $this->request->getGet('q');
        $results = $term !== '' ? (new BibleVerseModel())->search($term) : [];

        return view('Bible/search', ['title' => 'Search the Bible', 'term' => $term, 'results' => $results]);
    }

    public function toggleBookmark(int $verseId)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }
        (new BibleBookmarkModel())->toggle(auth()->id(), $verseId);

        return redirect()->back();
    }

    public function setHighlight(int $verseId)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }
        $color = (string) $this->request->getPost('color');
        if ($color === '') {
            (new BibleHighlightModel())->remove(auth()->id(), $verseId);
        } else {
            (new BibleHighlightModel())->set(auth()->id(), $verseId, $color);
        }

        return redirect()->back();
    }

    public function addNote(int $verseId)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }
        (new BibleNoteModel())->insert(['user_id' => auth()->id(), 'verse_id' => $verseId, 'body' => (string) $this->request->getPost('body')]);

        return redirect()->back();
    }

    public function myBookmarks(): string
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(site_url('login'));
        }

        return view('Bible/bookmarks', ['title' => 'My Bookmarks', 'bookmarks' => (new BibleBookmarkModel())->forUser(auth()->id())]);
    }
}
