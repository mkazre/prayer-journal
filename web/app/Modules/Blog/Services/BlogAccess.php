<?php

namespace App\Modules\Blog\Services;

use App\Modules\Shop\Services\Membership;

/**
 * Who may read a post's full text and download its resources.
 *
 *  members   - any signed-in user
 *  customers - signed in and has bought something: a paid shop order, a
 *              course or course module, or an active membership
 *
 * Staff (anyone who can open the Studio) always have access. A post set to
 * "inherit" follows its archive.
 */
class BlogAccess
{
    public const LEVELS = [
        'members'   => 'Signed-in members',
        'customers' => 'Customers only (bought a product, course or membership)',
    ];

    public static function level(array $post, array $archive): string
    {
        $level = $post['access'] ?? 'inherit';

        return isset(self::LEVELS[$level]) ? $level : (isset(self::LEVELS[$archive['access']]) ? $archive['access'] : 'members');
    }

    /**
     * @param bool|null $staff whether the reader is staff; null = ask the web session
     */
    public static function canRead(array $post, array $archive, ?int $userId, ?bool $staff = null): bool
    {
        if (! $userId) {
            return false;
        }
        if ($staff ?? is_staff()) {
            return true;
        }

        return self::level($post, $archive) === 'members' || self::isCustomer($userId);
    }

    public static function isStaffUser(int $userId): bool
    {
        $user = auth()->getProvider()->findById($userId);

        return $user !== null && $user->inGroup('superadmin', 'admin', 'manager', 'instructor', 'vendor');
    }

    public static function isCustomer(int $userId): bool
    {
        $db = db_connect();

        return $db->table('orders')->where('user_id', $userId)->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->countAllResults() > 0
            || $db->table('enrollments')->where('user_id', $userId)->whereIn('source', ['purchase', 'module', 'subscription'])->countAllResults() > 0
            || Membership::hasAccess($userId);
    }
}
