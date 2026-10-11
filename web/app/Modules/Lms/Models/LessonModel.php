<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class LessonModel extends Model
{
    protected $table            = 'lessons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'course_id', 'section_id', 'title', 'type', 'summary', 'content', 'video_url', 'attachment_path',
        'bible_reference', 'position', 'price_cents', 'available_at', 'status', 'duration_mins', 'is_preview',
        'drip_days', 'settings',
    ];
    protected $useTimestamps = true;

    public const TYPES = ['text' => 'Reading', 'devotion' => 'Devotion', 'video' => 'Video', 'audio' => 'Audio', 'pdf' => 'Document', 'embed' => 'Interactive'];

    public function forCourse(int $courseId, bool $publishedOnly = true)
    {
        $builder = $this->where('course_id', $courseId)->orderBy('position', 'asc');
        if ($publishedOnly) {
            $builder->where('status', 'published')
                ->groupStart()
                    ->where('available_at IS NULL')
                    ->orWhere('available_at <=', date('Y-m-d H:i:s'))
                ->groupEnd();
        }

        return $builder->findAll();
    }

    public static function settings(array $lesson): array
    {
        $s = json_decode((string) ($lesson['settings'] ?? ''), true);

        return array_merge(['prompts' => [], 'resources' => [], 'key_verse' => ''], is_array($s) ? $s : []);
    }

    /**
     * Journaling prompts for a lesson, falling back to the course defaults.
     *
     * @return list<array{key:string,label:string,hint:string,kind:string}>
     */
    public static function prompts(array $lesson, array $courseSettings): array
    {
        $prompts = self::settings($lesson)['prompts'] ?: ($courseSettings['default_prompts'] ?? []);
        $out     = [];
        foreach ($prompts as $i => $p) {
            if (empty($p['label'])) {
                continue;
            }
            $out[] = [
                'key'   => $p['key'] ?? ('p' . $i),
                'label' => (string) $p['label'],
                'hint'  => (string) ($p['hint'] ?? ''),
                'kind'  => in_array($p['kind'] ?? '', ['prayer', 'meditation', 'gratitude', 'note'], true) ? $p['kind'] : 'note',
            ];
        }

        return $out;
    }
}
