<?php

namespace App\Modules\Api\Controllers;

use App\Modules\Lms\Models\JournalEntryModel;

/**
 * The learner's private Prayer Journal: prayer points, meditations,
 * gratitude and free notes, with answered-prayer tracking.
 */
class JournalApiController extends BaseApiController
{
    public function index()
    {
        $userId  = $this->currentUser()->id;
        $model   = new JournalEntryModel();
        $filters = ['kind' => (string) $this->request->getGet('kind'), 'status' => (string) $this->request->getGet('status'), 'q' => trim((string) $this->request->getGet('q'))];
        $limit   = min(100, max(1, (int) ($this->request->getGet('limit') ?: 30)));
        $offset  = max(0, (int) $this->request->getGet('offset'));
        $rows    = $model->search($userId, $filters)->limit($limit, $offset)->get()->getResultArray();

        return $this->ok(['stats' => $model->stats($userId), 'entries' => array_map([$this, 'present'], $rows)]);
    }

    public function create()
    {
        $data = $this->validated($this->body());
        if (is_string($data)) {
            return $this->fail($data, 422);
        }
        $model = new JournalEntryModel();
        $id    = (int) $model->insert($data + ['user_id' => $this->currentUser()->id, 'entry_date' => $data['entry_date'] ?? date('Y-m-d')], true);

        return $this->ok($this->present($model->find($id)), 201);
    }

    public function update(int $id)
    {
        $model = new JournalEntryModel();
        $entry = $this->own($model, $id);
        if (! $entry) {
            return $this->fail('Entry not found.', 404);
        }
        $data = $this->validated(array_merge($entry, $this->body()));
        if (is_string($data)) {
            return $this->fail($data, 422);
        }
        $model->update($id, $data);

        return $this->ok($this->present($model->find($id)));
    }

    public function toggleAnswered(int $id)
    {
        $model = new JournalEntryModel();
        $entry = $this->own($model, $id);
        if (! $entry) {
            return $this->fail('Entry not found.', 404);
        }
        $answered = ! (int) $entry['is_answered'];
        $model->update($id, [
            'is_answered' => $answered ? 1 : 0,
            'answered_at' => $answered ? date('Y-m-d H:i:s') : null,
            'answer_note' => $answered ? (trim((string) ($this->body()['answer_note'] ?? '')) ?: null) : null,
        ]);

        return $this->ok($this->present($model->find($id)));
    }

    public function delete(int $id)
    {
        $model = new JournalEntryModel();
        if (! $this->own($model, $id)) {
            return $this->fail('Entry not found.', 404);
        }
        $model->delete($id);

        return $this->ok(['deleted' => true]);
    }

    private function own(JournalEntryModel $model, int $id): ?array
    {
        $entry = $model->find($id);

        return $entry && (int) $entry['user_id'] === (int) $this->currentUser()->id ? $entry : null;
    }

    /**
     * @return array|string the clean row, or an error message
     */
    private function validated(array $in)
    {
        $body = trim((string) ($in['body'] ?? ''));
        if ($body === '') {
            return 'Write something first.';
        }
        $kind = (string) ($in['kind'] ?? 'note');
        $date = (string) ($in['entry_date'] ?? '');

        return array_filter([
            'kind'       => isset(JournalEntryModel::KINDS[$kind]) ? $kind : 'note',
            'title'      => trim(mb_substr((string) ($in['title'] ?? ''), 0, 200)) ?: null,
            'body'       => mb_substr($body, 0, 20000),
            'scripture'  => trim(mb_substr((string) ($in['scripture'] ?? ''), 0, 120)) ?: null,
            'entry_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null,
        ], static fn ($v) => $v !== null) + ['title' => null, 'scripture' => null];
    }

    private function present(array $e): array
    {
        return [
            'id' => (int) $e['id'], 'kind' => $e['kind'], 'title' => $e['title'], 'body' => $e['body'], 'scripture' => $e['scripture'],
            'is_answered' => (bool) $e['is_answered'], 'answered_at' => $e['answered_at'], 'answer_note' => $e['answer_note'],
            'entry_date' => $e['entry_date'] ?: substr((string) $e['created_at'], 0, 10),
            'course_title' => $e['course_title'] ?? null, 'lesson_title' => $e['lesson_title'] ?? null,
            'lesson_id' => $e['lesson_id'] ? (int) $e['lesson_id'] : null, 'created_at' => $e['created_at'],
        ];
    }
}
