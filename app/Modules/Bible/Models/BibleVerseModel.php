<?php

namespace App\Modules\Bible\Models;

use CodeIgniter\Model;

class BibleVerseModel extends Model
{
    protected $table         = 'bible_verses';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['book_id', 'chapter', 'verse', 'text'];

    public function chapter(int $bookId, int $chapter): array
    {
        return $this->where(['book_id' => $bookId, 'chapter' => $chapter])->orderBy('verse', 'asc')->findAll();
    }

    public function search(string $term, int $limit = 50): array
    {
        return $this->db->table('bible_verses')
            ->select('bible_verses.*, bible_books.name as book_name, bible_books.abbrev')
            ->join('bible_books', 'bible_books.id = bible_verses.book_id')
            ->like('bible_verses.text', $term)
            ->limit($limit)
            ->get()->getResultArray();
    }
}
