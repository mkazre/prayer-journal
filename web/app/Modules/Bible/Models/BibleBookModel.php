<?php

namespace App\Modules\Bible\Models;

use CodeIgniter\Model;

class BibleBookModel extends Model
{
    protected $table         = 'bible_books';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'abbrev', 'testament', 'position', 'chapter_count'];

    public function all(): array
    {
        return $this->orderBy('position', 'asc')->findAll();
    }
}
