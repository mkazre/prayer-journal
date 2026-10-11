<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class UserSubscriptionModel extends Model
{
    protected $table         = 'user_subscriptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'plan_id', 'status', 'started_at', 'expires_at', 'gateway', 'gateway_token', 'renews', 'last_payment_at', 'cancelled_at', 'reminded_for', 'notice_stage', 'updated_at'];
    protected $useTimestamps = false;

    public function hasActive(int $userId): bool
    {
        return \App\Modules\Shop\Services\Membership::hasAccess($userId);
    }
}
