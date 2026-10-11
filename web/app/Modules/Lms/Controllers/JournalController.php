<?php

namespace App\Modules\Lms\Controllers;

use App\Controllers\BaseController;
use App\Modules\Lms\Models\JournalEntryModel;

/**
 * The learner's private prayer journal.
 */
class JournalController extends BaseController
{
    private const PER_PAGE = 30;

    public function index(): string
    {
        $model   = new JournalEntryModel();
        $filters = [
            'kind'   => (string) $this->request->getGet('kind'),
            'status' => (string) $this->request->getGet('status'),
            'q'      => trim((string) $this->request->getGet('q')),
            'course' => (int) $this->request->getGet('course'),
        ];
        $page    = max(1, (int) $this->request->getGet('page'));
        $total   = $model->search($this->userId(), $filters)->countAllResults();
        $entries = $model->search($this->userId(), $filters)->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->get()->getResultArray();

        $grouped = [];
        foreach ($entries as $e) {
            $grouped[$e['entry_date'] ?: substr((string) $e['created_at'], 0, 10)][] = $e;
        }

        $courses = db_connect()->table('journal_entries j')->select('c.id, c.title')->join('courses c', 'c.id = j.course_id')
            ->where('j.user_id', $this->userId())->groupBy('c.id, c.title')->get()->getResultArray();

        return view('Lms/journal/index', [
            'title'   => 'My Prayer Journal',
            'grouped' => $grouped,
            'filters' => $filters,
            'stats'   => $model->stats($this->userId()),
            'courses' => $courses,
            'page'    => $page,
            'pages'   => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'   => $total,
        ]);
    }

    public function printable(): string
    {
        $model = new JournalEntryModel();

        return view('Lms/journal/print', [
            'title'   => 'My Prayer Journal',
            'entries' => $model->search($this->userId(), ['kind' => (string) $this->request->getGet('kind'), 'status' => (string) $this->request->getGet('status')])->get()->getResultArray(),
            'stats'   => $model->stats($this->userId()),
        ]);
    }

    public function create()
    {
        $data = $this->validated();
        if (is_string($data)) {
            return $this->failed($data);
        }
        $data['user_id'] = $this->userId();
        $id              = (new JournalEntryModel())->insert($data, true);

        return $this->done('Saved to your journal.', ['id' => $id], site_url('journal'));
    }

    public function update(int $id)
    {
        $entry = $this->owned($id);
        $data  = $this->validated($entry);
        if (is_string($data)) {
            return $this->failed($data);
        }
        (new JournalEntryModel())->update($entry['id'], $data);

        return $this->done('Entry updated.', ['id' => $id], site_url('journal'));
    }

    public function toggleAnswered(int $id)
    {
        $entry    = $this->owned($id);
        $answered = ! (int) $entry['is_answered'];
        $note     = trim((string) ($this->input()['answer_note'] ?? ''));
        (new JournalEntryModel())->update($id, [
            'is_answered' => $answered ? 1 : 0,
            'answered_at' => $answered ? date('Y-m-d H:i:s') : null,
            'answer_note' => $answered ? ($note !== '' ? $note : $entry['answer_note']) : $entry['answer_note'],
            'kind'        => $answered ? 'prayer' : $entry['kind'],
        ]);

        return $this->done($answered ? 'Praise God! Marked as answered.' : 'Marked as still praying.', ['answered' => $answered]);
    }

    public function delete(int $id)
    {
        $this->owned($id);
        (new JournalEntryModel())->delete($id);

        return $this->done('Entry deleted.', [], site_url('journal'));
    }

    private function owned(int $id): array
    {
        $entry = (new JournalEntryModel())->where(['id' => $id, 'user_id' => $this->userId()])->first();
        if (! $entry) {
            $this->notFound();
        }

        return $entry;
    }

    /**
     * @return array|string data to save, or an error message
     */
    private function validated(?array $existing = null)
    {
        $in   = $this->input();
        $body = trim((string) ($in['body'] ?? ''));
        if ($body === '') {
            return 'Write something before saving.';
        }
        $kind = (string) ($in['kind'] ?? ($existing['kind'] ?? 'note'));
        $date = (string) ($in['entry_date'] ?? '');

        return [
            'kind'       => isset(JournalEntryModel::KINDS[$kind]) ? $kind : 'note',
            'title'      => trim(mb_substr((string) ($in['title'] ?? ($existing['title'] ?? '')), 0, 200)) ?: null,
            'body'       => mb_substr($body, 0, 20000),
            'scripture'  => trim(mb_substr((string) ($in['scripture'] ?? ($existing['scripture'] ?? '')), 0, 150)) ?: null,
            'entry_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : ($existing['entry_date'] ?? date('Y-m-d')),
        ];
    }
}
