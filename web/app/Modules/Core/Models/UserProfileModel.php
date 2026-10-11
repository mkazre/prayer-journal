<?php

namespace App\Modules\Core\Models;

use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table            = 'user_profiles';
    protected $primaryKey       = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['user_id', 'display_name', 'bio', 'avatar_path', 'preferences', 'updated_at'];

    public const PREF_OPTIONS = [
        'theme'        => ['system', 'light', 'dark'],
        'text'         => ['sm', 'md', 'lg', 'xl'],
        'reading'      => ['serif', 'sans'],
        'bible_layout' => ['paragraph', 'verse'],
    ];

    public function upsert(int $userId, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($this->find($userId)) {
            $this->update($userId, $data);

            return;
        }
        $this->insert(['user_id' => $userId] + $data);
    }

    public function savePreferences(int $userId, array $changes): array
    {
        $row   = $this->find($userId);
        $prefs = json_decode((string) ($row['preferences'] ?? ''), true) ?: [];
        foreach ($changes as $key => $value) {
            if (isset(self::PREF_OPTIONS[$key])) {
                if (in_array($value, self::PREF_OPTIONS[$key], true)) {
                    $prefs[$key] = $value;
                }
            } elseif (in_array($key, ['verse_numbers', 'journal_reminders'], true)) {
                $prefs[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }
        $this->upsert($userId, ['preferences' => json_encode($prefs)]);

        return $prefs;
    }
}
