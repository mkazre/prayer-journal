<?php

namespace App\Modules\Api\Controllers;

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

    public function chapter(string $abbrev, int $chapter)
    {
        $book = (new BibleBookModel())->where('abbrev', $abbrev)->first();
        if (! $book) {
            return $this->fail('Book not found.', 404);
        }
        $verses = (new BibleVerseModel())->chapter($book['id'], $chapter);

        $user = $this->currentUser();
        if ($user) {
            (new BibleReadingLogModel())->logToday($user->id);
        }

        return $this->ok(['book' => $book, 'chapter' => $chapter, 'verses' => $verses]);
    }

    public function search()
    {
        $term = (string) $this->request->getGet('q');

        return $this->ok($term !== '' ? (new BibleVerseModel())->search($term) : []);
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
        $color = (string) ($this->request->getJSON(true)['color'] ?? '');
        $model = new BibleHighlightModel();
        if ($color === '') {
            $model->remove($this->currentUser()->id, $verseId);

            return $this->ok(['highlighted' => false]);
        }

        return $this->ok($model->set($this->currentUser()->id, $verseId, $color));
    }

    public function addNote(int $verseId)
    {
        $body = (string) ($this->request->getJSON(true)['body'] ?? '');
        $id   = (new BibleNoteModel())->insert(['user_id' => $this->currentUser()->id, 'verse_id' => $verseId, 'body' => $body], true);

        return $this->ok(['id' => $id], 201);
    }

    public function streak()
    {
        return $this->ok(['streak' => (new BibleReadingLogModel())->currentStreak($this->currentUser()->id)]);
    }
}
