<?php

namespace App\Modules\Bible\Models;

use CodeIgniter\Model;

class BibleReadingLogModel extends Model
{
    protected $table         = 'bible_reading_log';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'read_date'];
    protected $useTimestamps = false;

    public function logToday(int $userId): void
    {
        $this->db->table('bible_reading_log')->ignore(true)->insert(['user_id' => $userId, 'read_date' => date('Y-m-d')]);
    }

    public function currentStreak(int $userId): int
    {
        $dates = array_column(
            $this->db->table('bible_reading_log')->select('read_date')->where('user_id', $userId)->orderBy('read_date', 'desc')->get()->getResultArray(),
            'read_date'
        );
        if ($dates === []) {
            return 0;
        }

        $streak  = 0;
        $cursor  = new \DateTime('today');
        $dateSet = array_flip($dates);

        // Today or yesterday must be present to keep the streak "alive".
        if (! isset($dateSet[$cursor->format('Y-m-d')])) {
            $cursor->modify('-1 day');
            if (! isset($dateSet[$cursor->format('Y-m-d')])) {
                return 0;
            }
        }

        while (isset($dateSet[$cursor->format('Y-m-d')])) {
            $streak++;
            $cursor->modify('-1 day');
        }

        return $streak;
    }
}
