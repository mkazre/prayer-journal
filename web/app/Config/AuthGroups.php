<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    public string $defaultGroup = 'student';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Complete control of the site.',
        ],
        'admin' => [
            'title'       => 'Admin',
            'description' => 'Day to day administrators of the site.',
        ],
        'manager' => [
            'title'       => 'Manager',
            'description' => 'Runs courses, the shop, orders, reports and prayer-wall moderation. Cannot change site settings or user accounts.',
        ],
        'instructor' => [
            'title'       => 'Instructor',
            'description' => 'Creates and manages courses, lessons, quizzes, and grades students.',
        ],
        'student' => [
            'title'       => 'Customer',
            'description' => 'A member of the site: studies courses, keeps a journal, shops the store.',
        ],
        'vendor' => [
            'title'       => 'Shop assistant',
            'description' => 'Manages their own products and orders in the store.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        'admin.access'        => 'Can access the site admin area',
        'admin.settings'      => 'Can access the main site settings',
        'users.manage-admins' => 'Can manage other admins',
        'users.create'        => 'Can create new non-admin users',
        'users.edit'          => 'Can edit existing non-admin users',
        'users.delete'        => 'Can delete existing non-admin users',
        'lms.author'          => 'Can create and manage courses, lessons, quizzes, and assignments',
        'lms.grade'           => 'Can grade student submissions and manage the gradebook',
        'shop.manage'         => 'Can manage the store back-office (products, orders, discounts, fulfillment)',
        'shop.sell'           => 'Can list and manage own products as a vendor',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    public array $matrix = [
        'superadmin' => [
            'admin.*',
            'users.*',
            'lms.*',
            'shop.*',
        ],
        'admin' => [
            'admin.access',
            'admin.settings',
            'users.create',
            'users.edit',
            'users.delete',
            'lms.author',
            'lms.grade',
            'shop.manage',
        ],
        'manager' => [
            'admin.access',
            'lms.author',
            'lms.grade',
            'shop.manage',
        ],
        'instructor' => [
            'lms.author',
            'lms.grade',
        ],
        'student' => [],
        'vendor' => [
            'shop.sell',
        ],
    ];
}
