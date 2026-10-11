<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

/**
 * The learner's personal prayer journal: prayer points (which can be
 * marked answered), meditations, gratitude and free notes. Entries made
 * from a lesson's journaling prompts keep a link back to that lesson.
 */
class JournalEntryModel extends Model
{
    protected $table         = 'journal_entries';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'user_id', 'course_id', 'lesson_id', 'kind', 'title', 'body', 'scripture',
        'is_answered', 'answered_at', 'answer_note', 'entry_date',
    ];
    protected $useTimestamps = true;

    public const KINDS = [
        'prayer'     => ['Prayer point', 'hands', 'kind-prayer'],
        'meditation' => ['Meditation', 'feather', 'kind-meditation'],
        'gratitude'  => ['Gratitude', 'sunrise', 'kind-gratitude'],
        'note'       => ['Note', 'note', 'kind-note'],
    ];

    public function search(int $userId, array $filters = [])
    {
        $b = $this->db->table('journal_entries j')
            ->select('j.*, l.title AS lesson_title, c.title AS course_title, c.slug AS course_slug')
            ->join('lessons l', 'l.id = j.lesson_id', 'left')
            ->join('courses c', 'c.id = j.course_id', 'left')
            ->where('j.user_id', $userId);
        if (! empty($filters['kind']) && isset(self::KINDS[$filters['kind']])) {
            $b->where('j.kind', $filters['kind']);
        }
        if (($filters['status'] ?? '') === 'answered') {
            $b->where('j.is_answered', 1);
        } elseif (($filters['status'] ?? '') === 'open') {
            $b->where('j.kind', 'prayer')->where('j.is_answered', 0);
        }
        if (! empty($filters['q'])) {
            $b->groupStart()->like('j.body', $filters['q'])->orLike('j.title', $filters['q'])->orLike('j.scripture', $filters['q'])->groupEnd();
        }
        if (! empty($filters['course'])) {
            $b->where('j.course_id', (int) $filters['course']);
        }

        return $b->orderBy('COALESCE(j.entry_date, DATE(j.created_at))', 'DESC', false)->orderBy('j.id', 'DESC');
    }

    public function stats(int $userId): array
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS total,
                SUM(CASE WHEN kind = 'prayer' THEN 1 ELSE 0 END) AS prayers,
                SUM(CASE WHEN kind = 'prayer' AND is_answered = 1 THEN 1 ELSE 0 END) AS answered,
                SUM(CASE WHEN kind = 'gratitude' THEN 1 ELSE 0 END) AS gratitude,
                SUM(CASE WHEN kind = 'meditation' THEN 1 ELSE 0 END) AS meditations
             FROM journal_entries WHERE user_id = ?",
            [$userId]
        )->getRowArray();

        return array_map('intval', $row ?: []);
    }

    /**
     * Entries the learner wrote against a lesson's prompts, keyed by prompt key (stored in title).
     */
    public function forLessonPrompts(int $userId, int $lessonId): array
    {
        $rows = $this->where(['user_id' => $userId, 'lesson_id' => $lessonId])->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['title'] ?? ''] = $r;
        }

        return $out;
    }
}
