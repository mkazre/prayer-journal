<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

/**
 * Modules (course sections) a learner bought individually.
 */
class SectionUnlockModel extends Model
{
    protected $table         = 'section_unlocks';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'section_id', 'created_at'];

    public function unlock(int $userId, int $sectionId): void
    {
        if (! $this->has($userId, $sectionId)) {
            $this->insert(['user_id' => $userId, 'section_id' => $sectionId, 'created_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function has(int $userId, int $sectionId): bool
    {
        return (bool) $this->where(['user_id' => $userId, 'section_id' => $sectionId])->countAllResults();
    }

    /**
     * @return list<int>
     */
    public function forCourse(int $userId, int $courseId): array
    {
        return array_map('intval', array_column($this->db->table('section_unlocks su')->select('su.section_id')
            ->join('course_sections s', 's.id = su.section_id')->where('su.user_id', $userId)->where('s.course_id', $courseId)
            ->get()->getResultArray(), 'section_id'));
    }
}
