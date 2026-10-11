<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class QuizAttemptModel extends Model
{
    protected $table            = 'quiz_attempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['quiz_id', 'user_id', 'score_percent', 'passed', 'started_at', 'submitted_at'];

    public function attemptsUsed(int $quizId, int $userId): int
    {
        return $this->where(['quiz_id' => $quizId, 'user_id' => $userId])->countAllResults();
    }

    public function best(int $quizId, int $userId): ?array
    {
        return $this->where(['quiz_id' => $quizId, 'user_id' => $userId])->orderBy('score_percent', 'DESC')->first();
    }

    public function passedQuizIds(int $userId, int $courseId): array
    {
        return array_map('intval', array_column($this->db->table('quiz_attempts a')
            ->select('DISTINCT a.quiz_id', false)
            ->join('quizzes q', 'q.id = a.quiz_id')
            ->where('a.user_id', $userId)->where('q.course_id', $courseId)->where('a.passed', 1)
            ->get()->getResultArray(), 'quiz_id'));
    }

    /**
     * Grades and stores a submitted attempt.
     *
     * $answers maps question_id to: an option id (single/true-false), a list
     * of option ids (multiple), or a string (short answer).
     *
     * @return array attempt row plus 'review' => per-question results
     */
    public function submit(int $quizId, int $userId, array $answers, int $passPercent, ?string $startedAt = null): array
    {
        $questions = $this->db->table('quiz_questions')->where('quiz_id', $quizId)->orderBy('position')->get()->getResultArray();
        $attemptId = $this->insert([
            'quiz_id'    => $quizId,
            'user_id'    => $userId,
            'started_at' => $startedAt ?: date('Y-m-d H:i:s'),
        ], true);

        $totalPoints = 0;
        $earned      = 0;
        $review      = [];
        foreach ($questions as $question) {
            $qid      = (int) $question['id'];
            $type     = $question['type'] ?: 'single';
            $points   = (int) $question['points'];
            $given    = $answers[$qid] ?? ($answers[(string) $qid] ?? null);
            $options  = $this->db->table('quiz_options')->where('question_id', $qid)->get()->getResultArray();
            $correctIds = array_map('intval', array_column(array_filter($options, static fn ($o) => (int) $o['is_correct'] === 1), 'id'));
            $optionIds  = array_map('intval', array_column($options, 'id'));
            $totalPoints += $points;
            $isCorrect = false;
            $optionId  = null;
            $response  = null;

            if ($type === 'short_text') {
                $response   = trim((string) (is_array($given) ? reset($given) : $given));
                $acceptable = array_filter(array_map([$this, 'normalise'], explode('|', (string) $question['correct_text'])), 'strlen');
                $isCorrect  = $response !== '' && in_array($this->normalise($response), $acceptable, true);
            } elseif ($type === 'multiple') {
                $picked = array_values(array_intersect(array_map('intval', (array) $given), $optionIds));
                sort($picked);
                $expected = $correctIds;
                sort($expected);
                $isCorrect = $picked !== [] && $picked === $expected;
                $response  = json_encode($picked);
            } else {
                $optionId  = ($given !== null && $given !== '' && in_array((int) $given, $optionIds, true)) ? (int) $given : null;
                $isCorrect = $optionId !== null && in_array($optionId, $correctIds, true);
            }

            if ($isCorrect) {
                $earned += $points;
            }
            $this->db->table('quiz_answers')->insert([
                'attempt_id'  => $attemptId,
                'question_id' => $qid,
                'option_id'   => $optionId,
                'response'    => $response,
                'is_correct'  => $isCorrect ? 1 : 0,
            ]);
            $review[$qid] = ['correct' => $isCorrect, 'given' => $type === 'multiple' ? json_decode((string) $response, true) : ($type === 'short_text' ? $response : $optionId), 'correct_ids' => $correctIds];
        }

        $score  = $totalPoints > 0 ? (int) round(($earned / $totalPoints) * 100) : 0;
        $passed = $score >= $passPercent;
        $this->update($attemptId, [
            'score_percent' => $score,
            'passed'        => $passed ? 1 : 0,
            'submitted_at'  => date('Y-m-d H:i:s'),
        ]);

        $attempt           = $this->find($attemptId);
        $attempt['review'] = $review;
        $attempt['earned'] = $earned;
        $attempt['total']  = $totalPoints;

        return $attempt;
    }

    private function normalise(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', '', $s);

        return preg_replace('/\s+/', ' ', $s);
    }
}
