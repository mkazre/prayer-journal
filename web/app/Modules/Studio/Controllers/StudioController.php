<?php

namespace App\Modules\Studio\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\CourseModel;
use CodeIgniter\HTTP\Exceptions\RedirectException;

/**
 * Shared access rules for the Studio back office. The route group already
 * requires a staff group; these helpers narrow access per area.
 */
abstract class StudioController extends BaseController
{
    protected function isAdmin(): bool
    {
        return auth()->user()->inGroup('superadmin', 'admin');
    }

    /**
     * Managers run the day-to-day business (courses, shop, orders, reports,
     * moderation) but cannot change site settings or user accounts.
     */
    protected function isManager(): bool
    {
        return $this->isAdmin() || auth()->user()->inGroup('manager');
    }

    protected function isInstructor(): bool
    {
        return $this->isManager() || auth()->user()->inGroup('instructor');
    }

    protected function isVendor(): bool
    {
        return $this->isManager() || auth()->user()->inGroup('vendor');
    }

    protected function requireAdmin(): void
    {
        if (! $this->isAdmin()) {
            throw new RedirectException(redirect()->to(site_url('studio'))->with('error', 'That area is for administrators.'));
        }
    }

    protected function requireManager(): void
    {
        if (! $this->isManager()) {
            throw new RedirectException(redirect()->to(site_url('studio'))->with('error', 'That area is for managers and administrators.'));
        }
    }

    protected function requireInstructor(): void
    {
        if (! $this->isInstructor()) {
            throw new RedirectException(redirect()->to(site_url('studio'))->with('error', 'That area is for instructors.'));
        }
    }

    protected function requireVendor(): void
    {
        if (! $this->isVendor()) {
            throw new RedirectException(redirect()->to(site_url('studio'))->with('error', 'That area is for shop managers.'));
        }
    }

    /**
     * A course the current user may manage (owner, co-instructor or admin).
     */
    protected function course(int $courseId): array
    {
        $this->requireInstructor();
        $model  = new CourseModel();
        $course = $model->find($courseId);
        if (! $course || ! $model->isInstructor($courseId, $this->userId())) {
            $this->notFound();
        }

        return $course;
    }

    protected function render(string $view, array $data = []): string
    {
        return view('Studio/' . $view, $data);
    }

    protected function post(string $key, $default = null)
    {
        return $this->request->getPost($key) ?? $default;
    }

    protected function money(string $key): int
    {
        return (int) round((float) str_replace([',', ' '], ['.', ''], (string) $this->request->getPost($key)) * 100);
    }

    protected function bool(string $key): int
    {
        return $this->request->getPost($key) ? 1 : 0;
    }
}
