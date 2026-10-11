<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Base for every web controller. Adds small helpers for responding to both
 * classic form posts (redirect + flash) and fetch() requests (JSON).
 */
abstract class BaseController extends Controller
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    protected function wantsJson(): bool
    {
        return $this->request->isAJAX() || str_contains($this->request->getHeaderLine('Accept'), 'application/json');
    }

    /**
     * JSON for fetch() callers, otherwise redirect back (or to $to) with a flash message.
     */
    protected function done(string $message, array $data = [], ?string $to = null)
    {
        if ($this->wantsJson()) {
            return $this->response->setJSON(array_merge(['ok' => true, 'message' => $message, 'csrf' => csrf_hash()], $data));
        }
        $redirect = $to ? redirect()->to($to) : redirect()->back();

        return $redirect->with('message', $message);
    }

    protected function failed(string $message, int $code = 422, ?string $to = null)
    {
        if ($this->wantsJson()) {
            return $this->response->setStatusCode($code)->setJSON(['ok' => false, 'error' => $message, 'csrf' => csrf_hash()]);
        }
        $redirect = $to ? redirect()->to($to) : redirect()->back()->withInput();

        return $redirect->with('error', $message);
    }

    protected function notFound(): never
    {
        throw PageNotFoundException::forPageNotFound();
    }

    protected function userId(): int
    {
        return (int) auth()->id();
    }

    /**
     * JSON or form body as an array.
     */
    protected function input(): array
    {
        if (str_contains($this->request->getHeaderLine('Content-Type'), 'application/json')) {
            return (array) ($this->request->getJSON(true) ?? []);
        }

        return (array) $this->request->getPost();
    }
}
