<?php

namespace App\Modules\Bible\Models;

use CodeIgniter\Model;

class BibleBookmarkModel extends Model
{
    protected $table         = 'bible_bookmarks';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'verse_id', 'created_at'];

    public function toggle(int $userId, int $verseId): bool
    {
        $existing = $this->where(['user_id' => $userId, 'verse_id' => $verseId])->first();
        if ($existing) {
            $this->delete($existing['id']);

            return false;
        }
        $this->insert(['user_id' => $userId, 'verse_id' => $verseId, 'created_at' => date('Y-m-d H:i:s')]);

        return true;
    }

    public function forUser(int $userId): array
    {
        return $this->db->table('bible_bookmarks')
            ->select('bible_bookmarks.*, bible_verses.chapter, bible_verses.verse, bible_verses.text, bible_books.name as book_name, bible_books.abbrev')
            ->join('bible_verses', 'bible_verses.id = bible_bookmarks.verse_id')
            ->join('bible_books', 'bible_books.id = bible_verses.book_id')
            ->where('bible_bookmarks.user_id', $userId)
            ->orderBy('bible_bookmarks.created_at', 'desc')
            ->get()->getResultArray();
    }
}
