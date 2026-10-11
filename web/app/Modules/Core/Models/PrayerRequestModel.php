<?php

namespace App\Modules\Core\Models;

use CodeIgniter\Model;

class PrayerRequestModel extends Model
{
    protected $table         = 'prayer_requests';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'body', 'is_anonymous', 'prayer_count', 'created_at', 'category', 'is_answered', 'answer_note'];

    public const CATEGORIES = [
        'general'   => 'General',
        'healing'   => 'Healing',
        'family'    => 'Family',
        'provision' => 'Provision',
        'guidance'  => 'Guidance',
        'salvation' => 'Salvation',
        'thanks'    => 'Thanksgiving',
    ];

    public function feed(int $limit = 50, array $filters = [], int $offset = 0): array
    {
        $b = $this->db->table('prayer_requests pr')
            ->select('pr.*, users.username')
            ->join('users', 'users.id = pr.user_id');
        if (! empty($filters['category']) && isset(self::CATEGORIES[$filters['category']])) {
            $b->where('pr.category', $filters['category']);
        }
        if (($filters['status'] ?? '') === 'answered') {
            $b->where('pr.is_answered', 1);
        }
        if (! empty($filters['mine'])) {
            $b->where('pr.user_id', (int) $filters['mine']);
        }
        if (! empty($filters['viewer'])) {
            $b->select('(SELECT COUNT(*) FROM prayer_request_prayers p WHERE p.request_id = pr.id AND p.user_id = ' . (int) $filters['viewer'] . ') AS i_prayed', false);
        }

        return $b->orderBy('pr.created_at', 'desc')->limit($limit, $offset)->get()->getResultArray();
    }

    public function prayFor(int $requestId, int $userId): bool
    {
        $existing = $this->db->table('prayer_request_prayers')->where(['request_id' => $requestId, 'user_id' => $userId])->countAllResults();
        if ($existing) {
            return false;
        }
        $this->db->table('prayer_request_prayers')->insert(['request_id' => $requestId, 'user_id' => $userId]);
        $this->set('prayer_count', 'prayer_count + 1', false)->where('id', $requestId)->update();

        return true;
    }
}
