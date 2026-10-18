<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The brand colour moved from purple (#4b1e82) to navy (#232d47). Saved
 * settings still holding the old purple default are switched over; colours
 * someone deliberately chose in the Studio are left alone.
 */
class RebrandPurpleToNavy extends Migration
{
    private const OLD = '#4b1e82';
    private const NEW = '#232d47';

    public function up()
    {
        $this->swap(self::OLD, self::NEW);
    }

    public function down()
    {
        $this->swap(self::NEW, self::OLD);
    }

    private function swap(string $from, string $to): void
    {
        $this->db->table('app_settings')
            ->where('setting_key', 'brand_color')
            ->whereIn('setting_value', [$from, strtoupper($from)])
            ->update(['setting_value' => $to]);

        $courses = $this->db->table('courses')->select('id, settings')->like('settings', 'brand_color')->get()->getResultArray();
        foreach ($courses as $course) {
            $settings = json_decode((string) $course['settings'], true);
            if (! is_array($settings) || strtolower((string) ($settings['brand_color'] ?? '')) !== $from) {
                continue;
            }
            $settings['brand_color'] = $to;
            $this->db->table('courses')->where('id', $course['id'])
                ->update(['settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
    }
}
