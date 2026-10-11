<?php

namespace App\Modules\Bible\Models;

use CodeIgniter\Model;

class BibleHighlightModel extends Model
{
    protected $table         = 'bible_highlights';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'verse_id', 'color', 'created_at'];

    public function setColor(int $userId, int $verseId, string $color): array
    {
        $existing = $this->where(['user_id' => $userId, 'verse_id' => $verseId])->first();
        if ($existing) {
            $this->update($existing['id'], ['color' => $color]);

            return $this->find($existing['id']);
        }
        $id = $this->insert(['user_id' => $userId, 'verse_id' => $verseId, 'color' => $color, 'created_at' => date('Y-m-d H:i:s')], true);

        return $this->find($id);
    }

    public function remove(int $userId, int $verseId): void
    {
        $this->where(['user_id' => $userId, 'verse_id' => $verseId])->delete();
    }

    public function forChapter(int $userId, array $verseIds): array
    {
        if ($verseIds === []) {
            return [];
        }

        $rows = $this->where('user_id', $userId)->whereIn('verse_id', $verseIds)->findAll();

        return array_column($rows, 'color', 'verse_id');
    }
}
