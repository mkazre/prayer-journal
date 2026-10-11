<?php

namespace App\Modules\Shop\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'order_id', 'course_id', 'plan_id', 'lesson_id', 'subscription_id',
        'user_id', 'gateway', 'gateway_ref', 'amount_cents', 'currency', 'status',
    ];
    protected $useTimestamps = true;
}
