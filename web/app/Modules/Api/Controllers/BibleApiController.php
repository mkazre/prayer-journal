<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\Scripture;
use App\Modules\Bible\Models\BibleBookModel;
use App\Modules\Bible\Models\BibleBookmarkModel;
use App\Modules\Bible\Models\BibleHighlightModel;
use App\Modules\Bible\Models\BibleNoteModel;
use App\Modules\Bible\Models\BibleReadingLogModel;
use App\Modules\Bible\Models\BibleVerseModel;

class BibleApiController extends BaseApiController
{
    public function books()
    {
        return $this->ok((new BibleBookModel())->all());
    }

    public function chapter(string $book, int $chapter)
    {
        $books = new BibleBookModel();
        $row   = $books->where('abbrev', $book)->first();
        if (! $row && ($position = Scripture::bookPosition(str_replace('-', ' ', $book)))) {
            $row = $books->where('position', $position)->first();
        }
        if (! $row) {
            return $this->fail('Book not found.', 404);
        }
        $verses = (new BibleVerseModel())->chapter((int) $row['id'], $chapter);
        if ($verses === []) {
            return $this->fail('Chapter not found.', 404);
        }

        $marks = ['highlights' => [], 'bookmarks' => []];
        if ($user = $this->optionalUser()) {
            (new BibleReadingLogModel())->logToday($user->id);
            $ids = array_column($verses, 'id');
            $db  = db_connect();
            $marks['highlights'] = array_column($db->table('bible_highlights')->select('verse_id, color')->where('user_id', $user->id)->whereIn('verse_id', $ids)->get()->getResultArray(), 'color', 'verse_id');
            $marks['bookmarks']  = array_map('intval', array_column($db->table('bible_bookmarks')->select('verse_id')->where('user_id', $user->id)->whereIn('verse_id', $ids)->get()->getResultArray(), 'verse_id'));
        }

        return $this->ok([
            'book' => $row + ['slug' => Scripture::slug((int) $row['position'])], 'chapter' => $chapter,
            'has_prev' => $chapter > 1 || (int) $row['position'] > 1, 'has_next' => $chapter < (int) $row['chapter_count'] || (int) $row['position'] < 66,
            'verses' => $verses,
        ] + $marks);
    }

    public function passage()
    {
        $ref = Scripture::parse((string) $this->request->getGet('ref'));
        if (! $ref) {
            return $this->fail('Reference not recognised.', 404);
        }
        $b = db_connect()->table('bible_verses v')->select('v.id, v.verse, v.text')->join('bible_books b', 'b.id = v.book_id')
            ->where('b.position', $ref['book'])->where('v.chapter', $ref['chapter']);
        if ($ref['verse_start']) {
            $b->where('v.verse >=', $ref['verse_start'])->where('v.verse <=', min($ref['verse_end'], $ref['verse_start'] + 30));
        }

        return $this->ok(['reference' => $ref['label'], 'book' => Scripture::slug($ref['book']), 'chapter' => $ref['chapter'], 'verses' => $b->orderBy('v.verse')->get()->getResultArray()]);
    }

    public function verseOfTheDay()
    {
        $v = verse_of_the_day();
        if (! $v) {
            return $this->fail('The Bible has not been imported yet.', 404);
        }

        return $this->ok(['reference' => $v['book_name'] . ' ' . $v['chapter'] . ':' . $v['verse'], 'text' => $v['text'], 'book' => Scripture::slug((int) $v['book_position']), 'chapter' => (int) $v['chapter'], 'verse' => (int) $v['verse']]);
    }

    public function search()
    {
        $term = trim((string) $this->request->getGet('q'));
        if (mb_strlen($term) < 2) {
            return $this->ok(['total' => 0, 'verses' => []]);
        }
        $model  = new BibleVerseModel();
        $offset = max(0, (int) $this->request->getGet('offset'));

        return $this->ok(['total' => $model->searchCount($term, []), 'verses' => $model->searchPage($term, [], 50, $offset)]);
    }

    public function toggleBookmark(int $verseId)
    {
        $added = (new BibleBookmarkModel())->toggle($this->currentUser()->id, $verseId);

        return $this->ok(['bookmarked' => $added]);
    }

    public function myBookmarks()
    {
        return $this->ok((new BibleBookmarkModel())->forUser($this->currentUser()->id));
    }

    public function setHighlight(int $verseId)
    {
        $color = (string) ($this->body()['color'] ?? '');
        $model = new BibleHighlightModel();
        if ($color === '') {
            $model->remove($this->currentUser()->id, $verseId);

            return $this->ok(['highlighted' => false]);
        }

        return $this->ok($model->setColor($this->currentUser()->id, $verseId, $color));
    }

    public function addNote(int $verseId)
    {
        $body = trim((string) ($this->body()['body'] ?? ''));
        if ($body === '') {
            return $this->fail('Write a note first.', 422);
        }
        $id   = (new BibleNoteModel())->insert(['user_id' => $this->currentUser()->id, 'verse_id' => $verseId, 'body' => $body], true);

        return $this->ok(['id' => $id], 201);
    }

    public function streak()
    {
        return $this->ok(['streak' => (new BibleReadingLogModel())->currentStreak($this->currentUser()->id)]);
    }
}
