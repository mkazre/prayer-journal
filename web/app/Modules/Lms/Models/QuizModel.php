<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class QuizModel extends Model
{
    protected $table            = 'quizzes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'course_id', 'section_id', 'lesson_id', 'title', 'instructions', 'pass_percent', 'time_limit_mins',
        'max_attempts', 'position', 'shuffle_questions', 'show_answers', 'status',
    ];
    protected $useTimestamps = true;

    public const QUESTION_TYPES = [
        'single'     => 'Multiple choice (one answer)',
        'multiple'   => 'Multiple choice (several answers)',
        'true_false' => 'True or false',
        'short_text' => 'Short answer',
    ];

    public function withQuestions(int $quizId): ?array
    {
        $quiz = $this->find($quizId);
        if (! $quiz) {
            return null;
        }
        $questions = $this->db->table('quiz_questions')->where('quiz_id', $quizId)->orderBy('position', 'asc')->orderBy('id', 'asc')->get()->getResultArray();
        foreach ($questions as &$q) {
            $q['type']    = $q['type'] ?: 'single';
            $q['options'] = $this->db->table('quiz_options')->where('question_id', $q['id'])->orderBy('position', 'asc')->get()->getResultArray();
        }
        $quiz['questions'] = $questions;

        return $quiz;
    }

    /**
     * Saves builder data, keeping existing question rows (matched by "id") so
     * learners' past answers stay linked. Questions missing from the list are
     * removed; options are rewritten.
     */
    public function saveQuestions(int $quizId, array $questions): int
    {
        $db       = $this->db;
        $existing = array_map('intval', array_column($db->table('quiz_questions')->select('id')->where('quiz_id', $quizId)->get()->getResultArray(), 'id'));
        $kept     = [];
        $position = 0;
        $db->transStart();
        foreach ($questions as $q) {
            $text = trim((string) ($q['question'] ?? ''));
            if ($text === '') {
                continue;
            }
            $type = isset(self::QUESTION_TYPES[$q['type'] ?? '']) ? $q['type'] : 'single';
            $row  = [
                'quiz_id'      => $quizId,
                'question'     => $text,
                'type'         => $type,
                'points'       => max(1, (int) ($q['points'] ?? 1)),
                'position'     => ++$position,
                'explanation'  => trim((string) ($q['explanation'] ?? '')) ?: null,
                'correct_text' => $type === 'short_text' ? trim((string) ($q['correct_text'] ?? '')) : null,
            ];
            $id = (int) ($q['id'] ?? 0);
            if ($id && in_array($id, $existing, true)) {
                $db->table('quiz_questions')->where('id', $id)->update($row);
                $db->table('quiz_options')->where('question_id', $id)->delete();
            } else {
                $db->table('quiz_questions')->insert($row);
                $id = (int) $db->insertID();
            }
            $kept[] = $id;
            if ($type === 'short_text') {
                continue;
            }
            // Keep original indexes so "correct" still points at the right option after blanks are skipped.
            $options = $type === 'true_false' ? ['True', 'False'] : array_filter(array_map('trim', (array) ($q['options'] ?? [])), 'strlen');
            $correct = array_map('intval', (array) ($q['correct'] ?? []));
            $pos     = 0;
            foreach ($options as $i => $optionText) {
                $db->table('quiz_options')->insert(['question_id' => $id, 'option_text' => $optionText, 'is_correct' => in_array((int) $i, $correct, true) ? 1 : 0, 'position' => $pos++]);
            }
        }
        $removed = array_diff($existing, $kept);
        if ($removed !== []) {
            $db->table('quiz_options')->whereIn('question_id', $removed)->delete();
            $db->table('quiz_questions')->whereIn('id', $removed)->delete();
        }
        $db->transComplete();

        return $position;
    }

    /**
     * Replaces a quiz's questions with the posted builder data.
     *
     * @param list<array{question:string,type?:string,points?:int,explanation?:string,correct_text?:string,options?:list<string>,correct?:int|list<int>}> $questions
     */
    public function replaceQuestions(int $quizId, array $questions): int
    {
        $db  = $this->db;
        $ids = array_column($db->table('quiz_questions')->select('id')->where('quiz_id', $quizId)->get()->getResultArray(), 'id');
        $db->transStart();
        if ($ids !== []) {
            $db->table('quiz_options')->whereIn('question_id', $ids)->delete();
            $db->table('quiz_questions')->where('quiz_id', $quizId)->delete();
        }
        $position = 0;
        foreach ($questions as $q) {
            $text = trim((string) ($q['question'] ?? ''));
            if ($text === '') {
                continue;
            }
            $type = isset(self::QUESTION_TYPES[$q['type'] ?? '']) ? $q['type'] : 'single';
            $db->table('quiz_questions')->insert([
                'quiz_id'      => $quizId,
                'question'     => $text,
                'type'         => $type,
                'points'       => max(1, (int) ($q['points'] ?? 1)),
                'position'     => ++$position,
                'explanation'  => trim((string) ($q['explanation'] ?? '')) ?: null,
                'correct_text' => $type === 'short_text' ? trim((string) ($q['correct_text'] ?? '')) : null,
            ]);
            $questionId = (int) $db->insertID();

            if ($type === 'short_text') {
                continue;
            }
            $options = $type === 'true_false' ? ['True', 'False'] : array_filter(array_map('trim', (array) ($q['options'] ?? [])), 'strlen');
            $correct = array_map('intval', (array) ($q['correct'] ?? []));
            $pos     = 0;
            foreach ($options as $i => $optionText) {
                $db->table('quiz_options')->insert([
                    'question_id' => $questionId,
                    'option_text' => $optionText,
                    'is_correct'  => in_array((int) $i, $correct, true) ? 1 : 0,
                    'position'    => $pos++,
                ]);
            }
        }
        $db->transComplete();

        return $position;
    }
}
