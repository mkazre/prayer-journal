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

    /**
     * Kept for the API and site search: first $limit verses containing the phrase.
     */
    public function search(string $term, int $limit = 50): array
    {
        return $this->searchPage($term, [], $limit, 0);
    }

    public function searchPage(string $term, array $filters, int $limit, int $offset): array
    {
        return $this->searchBuilder($term, $filters)
            ->select('bible_verses.*, bible_books.name AS book_name, bible_books.abbrev, bible_books.position AS book_position')
            ->orderBy('bible_books.position')->orderBy('bible_verses.chapter')->orderBy('bible_verses.verse')
            ->limit($limit, $offset)->get()->getResultArray();
    }

    public function searchCount(string $term, array $filters): int
    {
        return $this->searchBuilder($term, $filters)->countAllResults();
    }

    /**
     * Quoted text matches as a phrase; otherwise every word must appear.
     */
    private function searchBuilder(string $term, array $filters)
    {
        $b = $this->db->table('bible_verses')->join('bible_books', 'bible_books.id = bible_verses.book_id');
        $term = trim($term);
        if (preg_match('/^"(.+)"$/u', $term, $m)) {
            $b->like('bible_verses.text', $m[1]);
        } else {
            foreach (array_slice(array_filter(preg_split('/\s+/u', $term) ?: []), 0, 8) as $word) {
                $b->like('bible_verses.text', $word);
            }
        }
        if (! empty($filters['testament'])) {
            $b->where('bible_books.testament', $filters['testament']);
        }
        if (! empty($filters['book'])) {
            $b->where('bible_books.position', (int) $filters['book']);
        }

        return $b;
    }
}
