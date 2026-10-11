<?php

namespace App\Modules\Studio\Controllers;

use App\Modules\Lms\Models\QuizModel;

class QuizController extends StudioController
{
    public function create(int $courseId)
    {
        $course = $this->course($courseId);
        if ($this->request->getMethod() === 'POST') {
            return $this->save($course, null);
        }
        $quiz = ['id' => null, 'title' => '', 'instructions' => '', 'pass_percent' => 70, 'time_limit_mins' => null, 'max_attempts' => 0, 'shuffle_questions' => 0,
            'show_answers' => 1, 'status' => 'published', 'section_id' => (int) $this->request->getGet('section') ?: null, 'questions' => []];

        return $this->form($course, $quiz);
    }

    public function edit(int $id)
    {
        $quiz = (new QuizModel())->withQuestions($id);
        if (! $quiz) {
            $this->notFound();
        }
        $course = $this->course((int) $quiz['course_id']);
        if ($this->request->getMethod() === 'POST') {
            return $this->save($course, $quiz);
        }

        return $this->form($course, $quiz);
    }

    public function delete(int $id)
    {
        $quiz = (new QuizModel())->find($id);
        if (! $quiz) {
            $this->notFound();
        }
        $this->course((int) $quiz['course_id']);
        (new QuizModel())->delete($id);

        return $this->done('Quiz deleted.', [], site_url('studio/courses/' . $quiz['course_id'] . '/curriculum'));
    }

    private function form(array $course, array $quiz): string
    {
        $db = db_connect();

        return $this->render('quizzes/form', [
            'title'    => $quiz['id'] ? 'Edit quiz' : 'New quiz',
            'active'   => 'courses',
            'course'   => $course,
            'quiz'     => $quiz,
            'sections' => $db->table('course_sections')->where('course_id', $course['id'])->orderBy('position')->get()->getResultArray(),
            'stats'    => $quiz['id'] ? $db->table('quiz_attempts')->select('COUNT(*) AS attempts, AVG(score_percent) AS avg, SUM(passed) AS passes')->where('quiz_id', $quiz['id'])->get()->getRowArray() : null,
        ]);
    }

    private function save(array $course, ?array $quiz)
    {
        $p     = (array) $this->request->getPost();
        $title = trim((string) ($p['title'] ?? ''));
        if ($title === '') {
            return redirect()->back()->withInput()->with('error', 'Give the quiz a title.');
        }
        $sectionId = (int) ($p['section_id'] ?? 0);
        if ($sectionId && ! db_connect()->table('course_sections')->where(['id' => $sectionId, 'course_id' => $course['id']])->countAllResults()) {
            $sectionId = 0;
        }
        $data = [
            'course_id'         => $course['id'],
            'section_id'        => $sectionId ?: null,
            'title'             => mb_substr($title, 0, 200),
            'instructions'      => trim((string) ($p['instructions'] ?? '')) ?: null,
            'pass_percent'      => max(0, min(100, (int) ($p['pass_percent'] ?? 70))),
            'time_limit_mins'   => ($p['time_limit_mins'] ?? '') !== '' ? max(1, (int) $p['time_limit_mins']) : null,
            'max_attempts'      => max(0, (int) ($p['max_attempts'] ?? 0)),
            'shuffle_questions' => ! empty($p['shuffle_questions']) ? 1 : 0,
            'show_answers'      => ! empty($p['show_answers']) ? 1 : 0,
            'status'            => in_array($p['status'] ?? '', ['published', 'draft'], true) ? $p['status'] : 'draft',
        ];
        $model = new QuizModel();
        if ($quiz) {
            $model->update($quiz['id'], $data);
            $id = (int) $quiz['id'];
        } else {
            $data['position'] = (int) db_connect()->table('lessons')->selectMax('position')->where('course_id', $course['id'])->get()->getRow('position') + 1;
            $id               = (int) $model->insert($data, true);
        }
        $count = $model->saveQuestions($id, array_values((array) ($p['questions'] ?? [])));

        return redirect()->to(site_url('studio/quizzes/' . $id))->with('message', 'Quiz saved with ' . $count . ' question' . ($count === 1 ? '' : 's') . '.');
    }
}
