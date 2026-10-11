<?php

namespace App\Modules\Lms\Services;

use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\SectionUnlockModel;
use App\Modules\Shop\Services\Membership;

/**
 * Who may study what. A course can be:
 *   - free
 *   - bought outright (one-time price)
 *   - included in membership (access_mode "subscription", or a paid course
 *     with "Also included in membership" ticked)
 *   - bought module by module (any module with its own price)
 *
 * Membership enrollments only work while the membership is active, so a
 * lapsed or suspended member loses access automatically. Module buyers get
 * an enrollment with source "module" that unlocks just the modules they own.
 */
class CourseAccess
{
    public static function inMembership(array $course): bool
    {
        $mode = $course['access_mode'] ?? 'free';

        return $mode === 'subscription' || ($mode === 'paid' && ! empty(CourseModel::settings($course)['in_membership']));
    }

    /**
     * The learner's usable enrollment, creating one for active members.
     */
    public static function resolve(array $course, int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        $model      = new EnrollmentModel();
        $enrollment = $model->findFor((int) $course['id'], $userId);

        if (EnrollmentModel::isActive($enrollment) && $enrollment['source'] !== 'subscription') {
            return $enrollment;
        }
        if (self::inMembership($course) && Membership::hasAccess($userId)) {
            return EnrollmentModel::isActive($enrollment) ? $enrollment : $model->enroll((int) $course['id'], $userId, 'subscription');
        }

        return null;
    }

    /**
     * True when the learner had membership access to this course that has
     * since lapsed (used for a friendlier message).
     */
    public static function lapsed(array $course, int $userId): bool
    {
        $e = (new EnrollmentModel())->findFor((int) $course['id'], $userId);

        return $e && $e['source'] === 'subscription' && ! Membership::hasAccess($userId);
    }

    /**
     * Section ids a module-only learner owns; null means the whole course.
     *
     * @return list<int>|null
     */
    public static function ownedSections(?array $enrollment, int $courseId, int $userId): ?array
    {
        if (! $enrollment || ($enrollment['source'] ?? '') !== 'module') {
            return null;
        }

        return (new SectionUnlockModel())->forCourse($userId, $courseId);
    }
}
