<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $user = auth()->user();

        return view('Core/dashboard', [
            'title' => 'Dashboard',
            'user'  => $user,
            'roles' => $user->getGroups(),
        ]);
    }
}
