<?php

namespace App\Modules\Bible\Models;

use CodeIgniter\Model;

class BibleNoteModel extends Model
{
    protected $table         = 'bible_notes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'verse_id', 'body'];
    protected $useTimestamps = true;

    public function forVerse(int $userId, int $verseId): array
    {
        return $this->where(['user_id' => $userId, 'verse_id' => $verseId])->orderBy('created_at', 'desc')->findAll();
    }
}
