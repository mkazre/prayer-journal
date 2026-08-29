<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);

$routes->get('dashboard', '\App\Modules\Core\Controllers\DashboardController::index', ['filter' => 'session']);
