<?php

namespace App\Modules\Core\Models;

use CodeIgniter\Model;

class PrayerRequestModel extends Model
{
    protected $table         = 'prayer_requests';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'body', 'is_anonymous', 'prayer_count', 'created_at'];

    public function feed(): array
    {
        return $this->db->table('prayer_requests')
            ->select('prayer_requests.*, users.username')
            ->join('users', 'users.id = prayer_requests.user_id')
            ->orderBy('created_at', 'desc')
            ->get()->getResultArray();
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
