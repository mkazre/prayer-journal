<?php

namespace App\Modules\Bible\Models;

use App\Libraries\Scripture;
use CodeIgniter\Model;

class ReadingPlanModel extends Model
{
    protected $table         = 'reading_plans';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['slug', 'title', 'description', 'days', 'position', 'created_at'];

    public function allWithProgress(?int $userId): array
    {
        $plans = $this->orderBy('position')->findAll();
        $mine  = $userId ? $this->userPlans($userId) : [];
        foreach ($plans as &$p) {
            $p['user_plan'] = $mine[(int) $p['id']] ?? null;
        }

        return $plans;
    }

    /**
     * Plans the user has started, keyed by plan id, with completion counts
     * and the next unfinished day.
     */
    public function userPlans(int $userId): array
    {
        $rows = $this->db->table('user_reading_plans up')
            ->select('up.*, (SELECT COUNT(*) FROM user_reading_plan_days d WHERE d.user_plan_id = up.id) AS days_done', false)
            ->where('up.user_id', $userId)->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['plan_id']] = $r;
        }

        return $out;
    }

    public function forUser(int $userId): array
    {
        $out = [];
        foreach ($this->userPlans($userId) as $planId => $up) {
            if ($up['completed_at']) {
                continue;
            }
            $plan = $this->find($planId);
            if (! $plan) {
                continue;
            }
            $done      = $this->doneDays((int) $up['id']);
            $nextDay   = 1;
            while (in_array($nextDay, $done, true) && $nextDay <= (int) $plan['days']) {
                $nextDay++;
            }
            $day = $this->day((int) $planId, min($nextDay, (int) $plan['days']));
            $out[] = ['plan' => $plan, 'user_plan' => $up, 'next_day' => $nextDay, 'today' => $day, 'percent' => (int) floor(count($done) / max(1, (int) $plan['days']) * 100)];
        }

        return $out;
    }

    public function doneDays(int $userPlanId): array
    {
        return array_map('intval', array_column($this->db->table('user_reading_plan_days')->select('day')->where('user_plan_id', $userPlanId)->get()->getResultArray(), 'day'));
    }

    public function day(int $planId, int $day): ?array
    {
        $row = $this->db->table('reading_plan_days')->where(['plan_id' => $planId, 'day' => $day])->get()->getRowArray();
        if ($row) {
            $row['readings'] = json_decode($row['readings'], true) ?: [];
        }

        return $row;
    }

    public function days(int $planId): array
    {
        $rows = $this->db->table('reading_plan_days')->where('plan_id', $planId)->orderBy('day')->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['readings'] = json_decode($r['readings'], true) ?: [];
        }

        return $rows;
    }

    /**
     * Turns a day's readings ([[book, chapter], ...]) into reader links.
     *
     * @return list<array{label:string,url:string}>
     */
    public static function links(array $readings): array
    {
        $out = [];
        foreach ($readings as [$book, $chapter]) {
            $out[] = ['label' => Scripture::bookName((int) $book) . ' ' . $chapter, 'url' => site_url('bible/' . Scripture::slug((int) $book) . '/' . $chapter)];
        }

        return $out;
    }
}
