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

    /**
     * Grades and stores a submitted attempt.
     *
     * @param array<int,int|null> $answers question_id => option_id
     */
    public function submit(int $quizId, int $userId, array $answers, int $passPercent): array
    {
        $questions = $this->db->table('quiz_questions')->where('quiz_id', $quizId)->get()->getResultArray();

        $attemptId = $this->insert([
            'quiz_id'    => $quizId,
            'user_id'    => $userId,
            'started_at' => date('Y-m-d H:i:s'),
        ], true);

        $totalPoints  = 0;
        $earnedPoints = 0;

        foreach ($questions as $question) {
            $totalPoints  += (int) $question['points'];
            $selectedId    = $answers[$question['id']] ?? null;
            $isCorrect     = false;

            if ($selectedId !== null) {
                $option    = $this->db->table('quiz_options')->where('id', $selectedId)->where('question_id', $question['id'])->get()->getRowArray();
                $isCorrect = $option && (int) $option['is_correct'] === 1;
            }

            if ($isCorrect) {
                $earnedPoints += (int) $question['points'];
            }

            $this->db->table('quiz_answers')->insert([
                'attempt_id'  => $attemptId,
                'question_id' => $question['id'],
                'option_id'   => $selectedId,
                'is_correct'  => $isCorrect ? 1 : 0,
            ]);
        }

        $scorePercent = $totalPoints > 0 ? (int) round(($earnedPoints / $totalPoints) * 100) : 0;
        $passed       = $scorePercent >= $passPercent;

        $this->update($attemptId, [
            'score_percent' => $scorePercent,
            'passed'        => $passed ? 1 : 0,
            'submitted_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->find($attemptId);
    }
}
