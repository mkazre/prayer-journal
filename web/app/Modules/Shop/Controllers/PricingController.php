<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Shop\Models\SubscriptionPlanModel;

class PricingController extends BaseController
{
    public function index(): string
    {
        return view('Shop/pricing', ['title' => 'Pricing', 'plans' => (new SubscriptionPlanModel())->active()]);
    }
}
