<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class UserSubscriptionModel extends Model
{
    protected $table         = 'user_subscriptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'plan_id', 'status', 'started_at', 'expires_at'];
    protected $useTimestamps = false;

    public function hasActive(int $userId): bool
    {
        return (bool) $this->where('user_id', $userId)
            ->where('status', 'active')
            ->groupStart()
                ->where('expires_at IS NULL')
                ->orWhere('expires_at >', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->first();
    }

    public function activate(int $subscriptionId, ?array $plan = null): void
    {
        $expiresAt = null;
        if ($plan && $plan['type'] === 'subscription') {
            $expiresAt = $plan['billing_interval'] === 'year'
                ? date('Y-m-d H:i:s', strtotime('+1 year'))
                : date('Y-m-d H:i:s', strtotime('+1 month'));
        }

        $this->update($subscriptionId, [
            'status'     => 'active',
            'started_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
        ]);
    }
}
