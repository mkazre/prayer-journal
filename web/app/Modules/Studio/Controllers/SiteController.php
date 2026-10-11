<?php

namespace App\Modules\Studio\Controllers;

use App\Libraries\Uploads;
use App\Modules\Core\Models\AppSettingModel;
use App\Modules\Core\Models\PrayerRequestModel;

/**
 * Site-wide administration: branding & settings, users and roles, and
 * prayer wall moderation.
 */
class SiteController extends StudioController
{
    /**
     * Every editable setting: key => [label, type, help].
     */
    public const SETTINGS = [
        'Branding' => [
            'site_name'        => ['Site name', 'text', 'Shown in the header, emails and browser tabs.'],
            'tagline'          => ['Tagline', 'text', ''],
            'brand_color'      => ['Brand colour', 'color', 'Buttons, links and highlights.'],
            'accent_color'     => ['Accent colour', 'color', 'Gold accents and calls to action.'],
            'meta_description' => ['Search engine description', 'textarea', ''],
        ],
        'Home page' => [
            'hero_eyebrow'  => ['Hero label', 'text', ''],
            'hero_title'    => ['Hero headline', 'text', 'Leave empty for the default headline.'],
            'hero_subtitle' => ['Hero text', 'textarea', ''],
            'announcement'  => ['Announcement bar', 'text', 'Shown at the top of every page. Leave empty to hide.'],
        ],
        'Contact & footer' => [
            'company_name'  => ['Company / ministry name', 'text', ''],
            'contact_email' => ['Contact email', 'email', ''],
            'footer_blurb'  => ['Footer text', 'textarea', ''],
        ],
        'Store' => [
            'currency'                 => ['Currency code', 'text', 'e.g. ZAR, USD. Used for new prices.'],
            'store_country'            => ['Store country', 'text', 'Two-letter code used for default shipping and tax.'],
            'prices_include_tax'       => ['Prices include tax', 'bool', 'On for VAT-inclusive pricing (South Africa).'],
            'tax_label'                => ['Tax name', 'text', 'e.g. VAT, GST, Sales tax.'],
            'free_shipping_over_cents' => ['Free shipping over (cents)', 'number', 'e.g. 75000 = R750. 0 turns it off.'],
            'shipping_promise'         => ['Delivery promise on product pages', 'text', ''],
            'returns_promise'          => ['Returns promise on product pages', 'text', ''],
            'shop_title'               => ['Shop page title', 'text', ''],
            'shop_subtitle'            => ['Shop page text', 'textarea', ''],
            'allow_test_payments'      => ['Allow test payments in production', 'bool', 'Only for staging sites — orders are marked paid without taking money.'],
        ],
    ];

    public function settings()
    {
        $this->requireAdmin();
        $model = new AppSettingModel();
        if ($this->request->getMethod() === 'POST') {
            foreach (self::SETTINGS as $group) {
                foreach ($group as $key => [$label, $type]) {
                    $value = $type === 'bool' ? ($this->post($key) ? '1' : '0') : trim((string) $this->post($key));
                    if ($type === 'color' && ! preg_match('/^#[0-9a-f]{6}$/i', $value)) {
                        $value = '';
                    }
                    $model->put($key, $value);
                }
            }
            try {
                if ($logo = Uploads::image($this->request->getFile('logo'), 'branding', 2048)) {
                    $model->put('logo_path', $logo);
                }
            } catch (\RuntimeException $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
            if ($this->post('reset_logo')) {
                $model->put('logo_path', '');
            }
            site_setting('__flush__');

            return redirect()->to(site_url('studio/settings'))->with('message', 'Settings saved.');
        }
        $values = [];
        foreach ($model->findAll() as $r) {
            $values[$r['setting_key']] = $r['setting_value'];
        }

        return $this->render('site/settings', ['title' => 'Site settings', 'active' => 'settings', 'groups' => self::SETTINGS, 'values' => $values]);
    }

    public function users(): string
    {
        $this->requireAdmin();
        $q      = trim((string) $this->request->getGet('q'));
        $group  = (string) $this->request->getGet('group');
        $status = (string) $this->request->getGet('status');
        $b      = db_connect()->table('users u')
            ->select("u.id, u.username, u.status, u.status_message, u.created_at, u.last_active, ai.secret AS email, p.display_name, (SELECT GROUP_CONCAT(g.`group`) FROM auth_groups_users g WHERE g.user_id = u.id) AS groups_list", false)
            ->select('(SELECT COUNT(*) FROM enrollments e WHERE e.user_id = u.id) AS courses', false)
            ->select("(SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.status IN ('paid','processing','shipped','completed')) AS orders", false)
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left')
            ->join('user_profiles p', 'p.user_id = u.id', 'left')
            ->where('u.deleted_at IS NULL');
        if ($q !== '') {
            $b->groupStart()->like('u.username', $q)->orLike('ai.secret', $q)->orLike('p.display_name', $q)->groupEnd();
        }
        if ($group !== '') {
            $b->where('u.id IN (SELECT user_id FROM auth_groups_users WHERE `group` = ' . db_connect()->escape($group) . ')', null, false);
        }
        if ($status === 'suspended') {
            $b->where('u.status', 'banned');
        }
        $counts = [];
        foreach (db_connect()->table('auth_groups_users g')->select('g.group, COUNT(*) AS n')->join('users u', 'u.id = g.user_id')->where('u.deleted_at IS NULL')->groupBy('g.group')->get()->getResultArray() as $r) {
            $counts[$r['group']] = (int) $r['n'];
        }

        return $this->render('site/users', [
            'title' => 'Users & roles', 'active' => 'users', 'users' => $b->orderBy('u.created_at', 'DESC')->limit(500)->get()->getResultArray(),
            'groups' => config('AuthGroups')->groups, 'assignable' => $this->assignableGroups(), 'counts' => $counts,
            'q' => $q, 'group' => $group, 'status' => $status,
        ]);
    }

    /**
     * Creates an account from the admin panel. Leave the password blank to
     * generate one; it is shown once so the admin can pass it on.
     */
    public function createUser()
    {
        $this->requireAdmin();
        $email    = strtolower(trim((string) $this->post('email')));
        $username = trim((string) $this->post('username')) ?: $this->usernameFrom($email);
        $password = (string) $this->post('password');
        $generated = $password === '';
        if ($generated) {
            $password = $this->generatePassword();
        }
        if ($error = $this->validateAccount($username, $email, $password)) {
            return $this->failed($error);
        }
        $groups = $this->requestedGroups();
        if (is_string($groups)) {
            return $this->failed($groups);
        }

        $provider = auth()->getProvider();
        $user     = new \CodeIgniter\Shield\Entities\User(['username' => $username, 'email' => $email, 'password' => $password]);
        $provider->save($user);
        $user = $provider->findById($provider->getInsertID());
        $user->activate();
        $user->syncGroups(...($groups ?: ['student']));
        if ($name = trim((string) $this->post('display_name'))) {
            (new \App\Modules\Core\Models\UserProfileModel())->upsert((int) $user->id, ['display_name' => mb_substr($name, 0, 120)]);
        }

        $message = 'Account created for ' . $email . '.';
        if ($generated) {
            $message .= ' Temporary password: ' . $password . ' (copy it now; it will not be shown again).';
        }

        return redirect()->to(site_url('studio/users'))->with('message', $message);
    }

    /**
     * Edits name, username, email, roles and (optionally) password.
     */
    public function updateUser(int $id)
    {
        $this->requireAdmin();
        $provider = auth()->getProvider();
        $user     = $provider->findById($id) ?? $this->notFound();
        if ($error = $this->guardTarget($user)) {
            return $this->failed($error);
        }
        $email    = strtolower(trim((string) $this->post('email')));
        $username = trim((string) $this->post('username'));
        $password = (string) $this->post('password');
        if ($error = $this->validateAccount($username, $email, $password === '' ? null : $password, $id)) {
            return $this->failed($error);
        }
        $groups = $this->requestedGroups($user);
        if (is_string($groups)) {
            return $this->failed($groups);
        }
        if ($id === $this->userId() && ! array_intersect($groups, ['superadmin', 'admin'])) {
            return $this->failed('You cannot remove your own admin access.');
        }

        $user->username = $username;
        $user->email    = $email;
        if ($password !== '') {
            $user->password = $password;
        }
        $provider->save($user);
        $user->syncGroups(...($groups ?: ['student']));
        (new \App\Modules\Core\Models\UserProfileModel())->upsert($id, ['display_name' => trim(mb_substr((string) $this->post('display_name'), 0, 120)) ?: null]);
        if ($password !== '') {
            // Sign the person out everywhere so the new password takes effect.
            db_connect()->table('auth_identities')->where(['user_id' => $id, 'type' => 'access_token'])->delete();
            db_connect()->table('auth_remember_tokens')->where('user_id', $id)->delete();
        }

        return $this->done('Saved changes to ' . $username . '.', [], site_url('studio/users'));
    }

    /**
     * Kept for the quick roles dialog.
     */
    public function userGroups(int $id)
    {
        $this->requireAdmin();
        $user = auth()->getProvider()->findById($id) ?? $this->notFound();
        if ($error = $this->guardTarget($user)) {
            return $this->failed($error);
        }
        $want = $this->requestedGroups($user);
        if (is_string($want)) {
            return $this->failed($want);
        }
        if ($id === $this->userId() && ! array_intersect($want, ['superadmin', 'admin'])) {
            return $this->failed('You cannot remove your own admin access.');
        }
        $user->syncGroups(...($want ?: ['student']));

        return $this->done('Roles updated for ' . $user->username . '.');
    }

    /**
     * Suspends (or restores) an account: a suspended person cannot sign in on
     * the website or the app.
     */
    public function suspendUser(int $id)
    {
        $this->requireAdmin();
        $user = auth()->getProvider()->findById($id) ?? $this->notFound();
        if ($id === $this->userId()) {
            return $this->failed('You cannot suspend your own account.');
        }
        if ($error = $this->guardTarget($user)) {
            return $this->failed($error);
        }
        if ($user->isBanned()) {
            $user->unBan();

            return $this->done($user->username . ' can sign in again.');
        }
        $user->ban(trim((string) $this->post('reason')) ?: 'Your account has been suspended. Please contact us.');
        db_connect()->table('auth_identities')->where(['user_id' => $id, 'type' => 'access_token'])->delete();

        return $this->done($user->username . ' has been suspended.');
    }

    /**
     * Deletes an account. Orders, payments and course history are kept for
     * your records; the email address is released so it can be used again.
     */
    public function deleteUser(int $id)
    {
        $this->requireAdmin();
        $provider = auth()->getProvider();
        $user     = $provider->findById($id) ?? $this->notFound();
        if ($id === $this->userId()) {
            return $this->failed('You cannot delete your own account.');
        }
        if ($error = $this->guardTarget($user)) {
            return $this->failed($error);
        }
        $db = db_connect();
        $db->table('auth_identities')->where('user_id', $id)->where('type !=', 'email_password')->delete();
        $db->table('auth_identities')->where(['user_id' => $id, 'type' => 'email_password'])->update(['secret' => 'deleted-' . $id . '-' . bin2hex(random_bytes(4)) . '@invalid']);
        $db->table('auth_remember_tokens')->where('user_id', $id)->delete();
        $db->table('users')->where('id', $id)->update(['username' => 'deleted-' . $id . '-' . $user->username]);
        $provider->delete($id);

        return $this->done('Deleted ' . $user->username . '.', [], site_url('studio/users'));
    }

    /**
     * Roles the current admin may hand out: only a superadmin can create or
     * edit superadmins.
     *
     * @return array<string, array>
     */
    private function assignableGroups(): array
    {
        $groups = config('AuthGroups')->groups;
        if (! auth()->user()->inGroup('superadmin')) {
            unset($groups['superadmin']);
        }

        return $groups;
    }

    /**
     * @return list<string>|string the valid groups, or an error
     */
    private function requestedGroups(?\CodeIgniter\Shield\Entities\User $target = null)
    {
        $want = array_values(array_intersect(array_keys(config('AuthGroups')->groups), (array) $this->post('groups', [])));
        if (in_array('superadmin', $want, true) && ! auth()->user()->inGroup('superadmin') && ! ($target && $target->inGroup('superadmin'))) {
            return 'Only a superadmin can grant superadmin.';
        }

        return $want;
    }

    private function guardTarget(\CodeIgniter\Shield\Entities\User $user): ?string
    {
        if ($user->inGroup('superadmin') && ! auth()->user()->inGroup('superadmin')) {
            return 'Only a superadmin can change a superadmin account.';
        }

        return null;
    }

    private function validateAccount(string $username, string $email, ?string $password, ?int $ignoreId = null): ?string
    {
        if (! preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username)) {
            return 'Usernames are 3–30 characters: letters, numbers, dots, dashes or underscores.';
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }
        if ($password !== null && mb_strlen($password) < 8) {
            return 'Passwords need at least 8 characters.';
        }
        $db = db_connect();
        if ($db->table('users')->where('username', $username)->where('id !=', (int) $ignoreId)->countAllResults()) {
            return 'That username is already taken.';
        }
        if ($db->table('auth_identities')->where(['type' => 'email_password', 'secret' => $email])->where('user_id !=', (int) $ignoreId)->countAllResults()) {
            return 'An account with that email already exists.';
        }

        return null;
    }

    private function usernameFrom(string $email): string
    {
        $base = substr(preg_replace('/[^a-z0-9._-]/', '', strtolower(strtok($email, '@') ?: 'member')), 0, 24) ?: 'member';
        $base = strlen($base) < 3 ? $base . 'user' : $base;
        $name = $base;
        for ($i = 2; db_connect()->table('users')->where('username', $name)->countAllResults(); $i++) {
            $name = $base . $i;
        }

        return $name;
    }

    private function generatePassword(): string
    {
        $words = ['Grace', 'Hope', 'Faith', 'Light', 'Peace', 'Joy', 'Psalm', 'Shalom', 'Mercy', 'Glory'];

        return $words[random_int(0, 9)] . '-' . $words[random_int(0, 9)] . '-' . random_int(1000, 9999);
    }

    public function prayer(): string
    {
        $this->requireManager();

        return $this->render('site/prayer', ['title' => 'Prayer wall', 'active' => 'prayer', 'requests' => (new PrayerRequestModel())->feed(200)]);
    }

    public function deletePrayer(int $id)
    {
        $this->requireManager();
        db_connect()->table('prayer_request_prayers')->where('request_id', $id)->delete();
        (new PrayerRequestModel())->delete($id);

        return $this->done('Request removed.');
    }
}
