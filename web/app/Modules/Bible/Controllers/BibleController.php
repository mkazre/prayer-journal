<?php

namespace App\Modules\Bible\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Scripture;
use App\Modules\Bible\Models\BibleBookmarkModel;
use App\Modules\Bible\Models\BibleBookModel;
use App\Modules\Bible\Models\BibleHighlightModel;
use App\Modules\Bible\Models\BibleNoteModel;
use App\Modules\Bible\Models\BibleReadingLogModel;
use App\Modules\Bible\Models\BibleVerseModel;
use App\Modules\Bible\Models\ReadingPlanModel;
use App\Modules\Core\Models\UserProfileModel;

class BibleController extends BaseController
{
    public const COLORS = ['yellow', 'green', 'blue', 'pink', 'purple', 'orange'];

    /**
     * What readers see for each colour. "purple" is the stored key for saved
     * highlights, but it now renders in the brand navy.
     */
    public const COLOR_LABELS = ['yellow' => 'Yellow', 'green' => 'Green', 'blue' => 'Blue', 'pink' => 'Pink', 'purple' => 'Navy', 'orange' => 'Orange'];

    public function index(): string
    {
        $books   = (new BibleBookModel())->all();
        $grouped = [];
        foreach ($books as $b) {
            $grouped[$b['testament']][Scripture::groupFor((int) $b['position'])][] = $b;
        }
        $last = auth()->loggedIn() ? session('bible_last') : null;

        return view('Bible/index', [
            'title'   => 'The Bible',
            'grouped' => $grouped,
            'streak'  => auth()->loggedIn() ? (new BibleReadingLogModel())->currentStreak($this->userId()) : 0,
            'verse'   => verse_of_the_day(),
            'last'    => $last,
            'plans'   => auth()->loggedIn() ? (new ReadingPlanModel())->forUser($this->userId()) : [],
            'counts'  => auth()->loggedIn() ? $this->libraryCounts() : null,
        ]);
    }

    /**
     * Book overview: chapter grid.
     */
    public function book(string $slug)
    {
        $book = $this->findBook($slug);
        if ((int) $book['chapter_count'] === 1) {
            return redirect()->to(site_url('bible/' . Scripture::slug((int) $book['position']) . '/1'));
        }

        return view('Bible/book', ['title' => $book['name'], 'book' => $book, 'books' => (new BibleBookModel())->all()]);
    }

    public function chapter(string $slug, int $chapter)
    {
        $book = $this->findBook($slug);
        if ($chapter < 1 || $chapter > (int) $book['chapter_count']) {
            return redirect()->to(site_url('bible/' . Scripture::slug((int) $book['position']) . '/1'));
        }
        // Canonical URLs use the full book name ("/bible/1-john/4").
        $canonical = Scripture::slug((int) $book['position']);
        if ($slug !== $canonical) {
            return redirect()->to(site_url('bible/' . $canonical . '/' . $chapter));
        }

        $verses   = (new BibleVerseModel())->chapter((int) $book['id'], $chapter);
        $verseIds = array_map('intval', array_column($verses, 'id'));
        $highlights = $bookmarks = $notes = [];
        if (auth()->loggedIn()) {
            (new BibleReadingLogModel())->logToday($this->userId());
            session()->set('bible_last', ['label' => $book['name'] . ' ' . $chapter, 'url' => site_url('bible/' . $canonical . '/' . $chapter)]);
            $highlights = (new BibleHighlightModel())->forChapter($this->userId(), $verseIds);
            if ($verseIds !== []) {
                $bookmarks = array_map('intval', array_column((new BibleBookmarkModel())->where('user_id', $this->userId())->whereIn('verse_id', $verseIds)->findAll(), 'verse_id'));
                foreach ((new BibleNoteModel())->where('user_id', $this->userId())->whereIn('verse_id', $verseIds)->orderBy('created_at')->findAll() as $n) {
                    $notes[(int) $n['verse_id']][] = $n;
                }
            }
        }

        $books    = (new BibleBookModel())->all();
        $position = (int) $book['position'];
        $prev     = $chapter > 1 ? ['book' => $book, 'chapter' => $chapter - 1] : ($position > 1 ? ['book' => $books[$position - 2], 'chapter' => (int) $books[$position - 2]['chapter_count']] : null);
        $next     = $chapter < (int) $book['chapter_count'] ? ['book' => $book, 'chapter' => $chapter + 1] : ($position < 66 ? ['book' => $books[$position], 'chapter' => 1] : null);

        return view('Bible/chapter', [
            'title'           => $book['name'] . ' ' . $chapter,
            'metaDescription' => $verses ? excerpt($verses[0]['text'], 150) : null,
            'book'            => $book,
            'chapter'         => $chapter,
            'verses'          => $verses,
            'highlights'      => $highlights,
            'bookmarks'       => $bookmarks,
            'notes'           => $notes,
            'books'           => $books,
            'prev'            => $prev,
            'next'            => $next,
            'prefs'           => user_prefs(),
            'colors'          => self::COLORS,
        ]);
    }

    public function search(): string
    {
        $term      = trim((string) $this->request->getGet('q'));
        $testament = (string) $this->request->getGet('t');
        $bookSlug  = (string) $this->request->getGet('book');
        $page      = max(1, (int) $this->request->getGet('page'));
        $perPage   = 30;
        $results   = [];
        $total     = 0;
        $direct    = null;

        if ($term !== '') {
            // "John 3:16" or "Psalm 23" jumps straight to the passage.
            if ($ref = Scripture::parse($term)) {
                $direct = $ref;
            }
            $model   = new BibleVerseModel();
            $filters = ['testament' => in_array($testament, ['OT', 'NT'], true) ? $testament : null, 'book' => $bookSlug !== '' ? Scripture::bookPosition(str_replace('-', ' ', $bookSlug)) : null];
            $total   = $model->searchCount($term, $filters);
            $results = $model->searchPage($term, $filters, $perPage, ($page - 1) * $perPage);
        }

        return view('Bible/search', [
            'title'     => $term !== '' ? 'Search: ' . $term : 'Search the Bible',
            'term'      => $term,
            'testament' => $testament,
            'bookSlug'  => $bookSlug,
            'results'   => $results,
            'total'     => $total,
            'page'      => $page,
            'pages'     => max(1, (int) ceil($total / $perPage)),
            'direct'    => $direct,
            'books'     => (new BibleBookModel())->all(),
        ]);
    }

    /**
     * JSON passage lookup for verse popovers: ?ref=Romans 8:26-27
     */
    public function passage()
    {
        $ref = Scripture::parse((string) $this->request->getGet('ref'));
        if (! $ref) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Reference not recognised.']);
        }
        $b = db_connect()->table('bible_verses v')->select('v.id, v.verse, v.text')->join('bible_books b', 'b.id = v.book_id')
            ->where('b.position', $ref['book'])->where('v.chapter', $ref['chapter']);
        if ($ref['verse_start']) {
            $b->where('v.verse >=', $ref['verse_start'])->where('v.verse <=', min($ref['verse_end'], $ref['verse_start'] + 30));
        } else {
            $b->limit(30);
        }

        return $this->response->setHeader('Cache-Control', 'public, max-age=86400')->setJSON([
            'reference' => $ref['label'],
            'url'       => Scripture::url($ref),
            'verses'    => $b->orderBy('v.verse')->get()->getResultArray(),
        ]);
    }

    /**
     * Bookmarks, highlights and notes in one place.
     */
    public function library(): string
    {
        $tab    = (string) ($this->request->getGet('tab') ?: 'highlights');
        $color  = (string) $this->request->getGet('color');
        $db     = db_connect();
        $select = 'v.id AS verse_id, v.chapter, v.verse, v.text, b.name AS book_name, b.position';
        $items  = [];
        if ($tab === 'bookmarks') {
            $items = $db->table('bible_bookmarks x')->select($select . ', x.created_at, x.id')->join('bible_verses v', 'v.id = x.verse_id')->join('bible_books b', 'b.id = v.book_id')
                ->where('x.user_id', $this->userId())->orderBy('x.created_at', 'DESC')->get()->getResultArray();
        } elseif ($tab === 'notes') {
            $items = $db->table('bible_notes x')->select($select . ', x.created_at, x.id, x.body')->join('bible_verses v', 'v.id = x.verse_id')->join('bible_books b', 'b.id = v.book_id')
                ->where('x.user_id', $this->userId())->orderBy('x.created_at', 'DESC')->get()->getResultArray();
        } else {
            $tab = 'highlights';
            $b   = $db->table('bible_highlights x')->select($select . ', x.created_at, x.id, x.color')->join('bible_verses v', 'v.id = x.verse_id')->join('bible_books b', 'b.id = v.book_id')
                ->where('x.user_id', $this->userId());
            if (in_array($color, self::COLORS, true)) {
                $b->where('x.color', $color);
            }
            $items = $b->orderBy('b.position')->orderBy('v.chapter')->orderBy('v.verse')->get()->getResultArray();
        }

        return view('Bible/library', ['title' => 'My Bible library', 'tab' => $tab, 'items' => $items, 'counts' => $this->libraryCounts(), 'color' => $color, 'colors' => self::COLORS]);
    }

    public function toggleBookmark(int $verseId)
    {
        $this->assertVerse($verseId);
        $added = (new BibleBookmarkModel())->toggle($this->userId(), $verseId);

        return $this->done($added ? 'Bookmarked.' : 'Bookmark removed.', ['bookmarked' => $added]);
    }

    /**
     * Highlights one or more verses (verse_ids[] in the body) with a colour, or clears them.
     */
    public function setHighlight(int $verseId)
    {
        $in    = $this->input();
        $ids   = array_values(array_unique(array_map('intval', (array) ($in['verse_ids'] ?? [$verseId]))));
        $color = (string) ($in['color'] ?? '');
        $model = new BibleHighlightModel();
        foreach (array_slice($ids, 0, 200) as $id) {
            $this->assertVerse($id);
            if ($color === '' || ! in_array($color, self::COLORS, true)) {
                $model->remove($this->userId(), $id);
            } else {
                $model->setColor($this->userId(), $id, $color);
            }
        }

        return $this->done($color === '' ? 'Highlight removed.' : 'Highlighted.', ['color' => $color, 'verse_ids' => $ids]);
    }

    public function addNote(int $verseId)
    {
        $this->assertVerse($verseId);
        $body = trim(mb_substr((string) ($this->input()['body'] ?? ''), 0, 5000));
        if ($body === '') {
            return $this->failed('Write a note first.');
        }
        $id = (new BibleNoteModel())->insert(['user_id' => $this->userId(), 'verse_id' => $verseId, 'body' => $body], true);

        return $this->done('Note saved.', ['id' => $id, 'body' => $body]);
    }

    public function deleteNote(int $noteId)
    {
        $model = new BibleNoteModel();
        $note  = $model->where(['id' => $noteId, 'user_id' => $this->userId()])->first();
        if (! $note) {
            $this->notFound();
        }
        $model->delete($noteId);

        return $this->done('Note deleted.');
    }

    /**
     * Reader settings (layout + verse numbers) saved to the user's preferences.
     */
    public function settings()
    {
        (new UserProfileModel())->savePreferences($this->userId(), $this->input());

        return $this->done('Reader settings saved.');
    }

    private function findBook(string $slug): array
    {
        $model = new BibleBookModel();
        $book  = $model->where('abbrev', $slug)->first();
        if (! $book && ($pos = Scripture::bookPosition(str_replace('-', ' ', $slug)))) {
            $book = $model->where('position', $pos)->first();
        }
        if (! $book) {
            $this->notFound();
        }

        return $book;
    }

    private function assertVerse(int $verseId): void
    {
        if (! db_connect()->table('bible_verses')->where('id', $verseId)->countAllResults()) {
            $this->notFound();
        }
    }

    private function libraryCounts(): array
    {
        $db = db_connect();

        return [
            'highlights' => $db->table('bible_highlights')->where('user_id', $this->userId())->countAllResults(),
            'bookmarks'  => $db->table('bible_bookmarks')->where('user_id', $this->userId())->countAllResults(),
            'notes'      => $db->table('bible_notes')->where('user_id', $this->userId())->countAllResults(),
        ];
    }
}
