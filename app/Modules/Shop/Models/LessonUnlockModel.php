<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class LessonUnlockModel extends Model
{
    protected $table         = 'lesson_unlocks';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'lesson_id', 'created_at'];

    public function hasUnlocked(int $userId, int $lessonId): bool
    {
        return (bool) $this->where(['user_id' => $userId, 'lesson_id' => $lessonId])->first();
    }

    public function unlock(int $userId, int $lessonId): void
    {
        if (! $this->hasUnlocked($userId, $lessonId)) {
            $this->insert(['user_id' => $userId, 'lesson_id' => $lessonId, 'created_at' => date('Y-m-d H:i:s')]);
        }
    }
}
