<?php

namespace App\Modules\Api\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseApiController extends Controller
{
    protected $helpers = ['auth', 'setting', 'ui', 'text'];

    protected function currentUser()
    {
        return auth('tokens')->user();
    }

    /**
     * The token user on public endpoints, when the app sent a valid token.
     */
    protected function optionalUser()
    {
        if ($this->request->getHeaderLine('Authorization') === '') {
            return null;
        }

        return auth('tokens')->loggedIn() ? auth('tokens')->user() : null;
    }

    /**
     * JSON body, falling back to form fields.
     */
    protected function body(): array
    {
        $json = $this->request->getJSON(true);

        return is_array($json) ? $json : (array) $this->request->getPost();
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
