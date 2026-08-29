<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class SubscriptionPlanModel extends Model
{
    protected $table         = 'subscription_plans';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'type', 'billing_interval', 'price_cents', 'currency', 'is_active'];
    protected $useTimestamps = false;

    public function active(): array
    {
        return $this->where('is_active', 1)->findAll();
    }
}
