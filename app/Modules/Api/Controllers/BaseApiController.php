<?php

namespace App\Modules\Api\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseApiController extends Controller
{
    protected function currentUser()
    {
        return auth('tokens')->user();
    }

    protected function ok($data = [], int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON($data);
    }

    protected function fail(string $message, int $code = 400): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON(['error' => $message]);
    }
}
