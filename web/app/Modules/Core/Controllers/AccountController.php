<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Uploads;
use App\Modules\Core\Models\UserProfileModel;

class AccountController extends BaseController
{
    public function index(): string
    {
        return view('Core/account', [
            'title'   => 'Account settings',
            'user'    => auth()->user(),
            'profile' => user_profile(),
            'prefs'   => user_prefs(),
        ]);
    }

    public function profile()
    {
        $data = [
            'display_name' => trim(mb_substr((string) $this->request->getPost('display_name'), 0, 120)) ?: null,
            'bio'          => trim(mb_substr((string) $this->request->getPost('bio'), 0, 1000)) ?: null,
        ];
        try {
            $avatar = Uploads::image($this->request->getFile('avatar'), 'avatars', 3072);
        } catch (\RuntimeException $e) {
            return $this->failed($e->getMessage());
        }
        $profiles = new UserProfileModel();
        if ($avatar) {
            Uploads::delete(user_profile()['avatar_path'] ?? null);
            $data['avatar_path'] = $avatar;
        }
        if ($this->request->getPost('remove_avatar')) {
            Uploads::delete(user_profile()['avatar_path'] ?? null);
            $data['avatar_path'] = null;
        }
        $profiles->upsert($this->userId(), $data);

        return $this->done('Profile saved.', [], site_url('account'));
    }

    public function password()
    {
        $user    = auth()->user();
        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');
        if (! password_verify($current, (string) $user->password_hash)) {
            return $this->failed('Your current password is incorrect.', 422, site_url('account#security'));
        }
        if (strlen($new) < 8 || $new !== (string) $this->request->getPost('new_password_confirm')) {
            return $this->failed('New passwords must match and be at least 8 characters.', 422, site_url('account#security'));
        }
        $user->fill(['password' => $new]);
        auth()->getProvider()->save($user);

        return $this->done('Password updated.', [], site_url('account#security'));
    }

    /**
     * Saves display preferences (theme, text size, reading font, Bible layout).
     */
    public function preferences()
    {
        $prefs = (new UserProfileModel())->savePreferences($this->userId(), $this->input());

        return $this->done('Preferences saved.', ['preferences' => $prefs], site_url('account#preferences'));
    }

    public function notifications()
    {
        $db   = db_connect();
        $rows = $db->table('notifications')->where('user_id', $this->userId())->orderBy('created_at', 'DESC')->limit(50)->get()->getResultArray();
        $db->table('notifications')->where('user_id', $this->userId())->where('read_at', null)->update(['read_at' => date('Y-m-d H:i:s')]);

        return view('Core/notifications', ['title' => 'Notifications', 'items' => $rows]);
    }
}
