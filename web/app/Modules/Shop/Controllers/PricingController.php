<?php

namespace App\Modules\Shop\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Shop\Models\SubscriptionPlanModel;
use App\Modules\Shop\Models\UserSubscriptionModel;

class PricingController extends BaseController
{
    public function index(): string
    {
        return view('Shop/pricing', [
            'title'   => 'Membership',
            'plans'   => (new SubscriptionPlanModel())->active(),
            'courses' => (new CourseModel())->published()->where('access_mode', 'subscription')->findAll(6),
            'active'  => auth()->loggedIn() && (new UserSubscriptionModel())->hasActive($this->userId()),
        ]);
    }
}
