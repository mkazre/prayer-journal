<?php

namespace App\Modules\Core\Models;

use CodeIgniter\Model;

class AppSettingModel extends Model
{
    protected $table      = 'app_settings';
    protected $primaryKey = 'setting_key';
    protected $returnType = 'array';

    public function get(string $key, ?string $default = null): ?string
    {
        $row = $this->find($key);

        return $row['setting_value'] ?? $default;
    }

    public function put(string $key, string $value): void
    {
        $this->db->table('app_settings')->replace(['setting_key' => $key, 'setting_value' => $value]);
    }
}
