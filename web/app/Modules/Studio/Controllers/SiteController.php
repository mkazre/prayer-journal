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
        $q     = trim((string) $this->request->getGet('q'));
        $group = (string) $this->request->getGet('group');
        $b     = db_connect()->table('users u')
            ->select("u.id, u.username, u.created_at, u.last_active, ai.secret AS email, (SELECT GROUP_CONCAT(g.`group`) FROM auth_groups_users g WHERE g.user_id = u.id) AS groups_list", false)
            ->select('(SELECT COUNT(*) FROM enrollments e WHERE e.user_id = u.id) AS courses', false)
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left')
            ->where('u.deleted_at IS NULL');
        if ($q !== '') {
            $b->groupStart()->like('u.username', $q)->orLike('ai.secret', $q)->groupEnd();
        }
        if ($group !== '') {
            $b->where('u.id IN (SELECT user_id FROM auth_groups_users WHERE `group` = ' . db_connect()->escape($group) . ')', null, false);
        }

        return $this->render('site/users', ['title' => 'Users & roles', 'active' => 'users', 'users' => $b->orderBy('u.created_at', 'DESC')->limit(300)->get()->getResultArray(),
            'groups' => config('AuthGroups')->groups, 'q' => $q, 'group' => $group]);
    }

    public function userGroups(int $id)
    {
        $this->requireAdmin();
        $user = auth()->getProvider()->findById($id) ?? $this->notFound();
        $want = array_values(array_intersect(array_keys(config('AuthGroups')->groups), (array) $this->post('groups', [])));
        if ($id === $this->userId() && ! array_intersect($want, ['superadmin', 'admin'])) {
            return $this->failed('You cannot remove your own admin access.');
        }
        if (in_array('superadmin', $want, true) && ! auth()->user()->inGroup('superadmin') && ! $user->inGroup('superadmin')) {
            return $this->failed('Only a superadmin can grant superadmin.');
        }
        $user->syncGroups(...($want ?: [config('AuthGroups')->defaultGroup]));

        return $this->done('Roles updated for ' . $user->username . '.');
    }

    public function prayer(): string
    {
        $this->requireAdmin();

        return $this->render('site/prayer', ['title' => 'Prayer wall', 'active' => 'prayer', 'requests' => (new PrayerRequestModel())->feed(200)]);
    }

    public function deletePrayer(int $id)
    {
        $this->requireAdmin();
        db_connect()->table('prayer_request_prayers')->where('request_id', $id)->delete();
        (new PrayerRequestModel())->delete($id);

        return $this->done('Request removed.');
    }
}
