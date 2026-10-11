<?php

namespace App\Modules\Studio\Controllers;

use App\Modules\Lms\Models\AssignmentModel;

class AssignmentController extends StudioController
{
    public function create(int $courseId)
    {
        $course = $this->course($courseId);
        if ($this->request->getMethod() === 'POST') {
            return $this->save($course, null);
        }

        return $this->form($course, ['id' => null, 'title' => '', 'instructions' => '', 'max_points' => 10, 'due_at' => null, 'allow_file' => 0, 'section_id' => (int) $this->request->getGet('section') ?: null]);
    }

    public function edit(int $id)
    {
        $a = (new AssignmentModel())->find($id);
        if (! $a) {
            $this->notFound();
        }
        $course = $this->course((int) $a['course_id']);
        if ($this->request->getMethod() === 'POST') {
            return $this->save($course, $a);
        }

        return $this->form($course, $a);
    }

    public function delete(int $id)
    {
        $a = (new AssignmentModel())->find($id);
        if (! $a) {
            $this->notFound();
        }
        $this->course((int) $a['course_id']);
        (new AssignmentModel())->delete($id);

        return $this->done('Assignment deleted.', [], site_url('studio/courses/' . $a['course_id'] . '/curriculum'));
    }

    private function form(array $course, array $a): string
    {
        return $this->render('assignments/form', [
            'title' => $a['id'] ? 'Edit assignment' : 'New assignment', 'active' => 'courses', 'course' => $course, 'assignment' => $a,
            'sections' => db_connect()->table('course_sections')->where('course_id', $course['id'])->orderBy('position')->get()->getResultArray(),
            'submissions' => $a['id'] ? db_connect()->table('assignment_submissions')->where('assignment_id', $a['id'])->countAllResults() : 0,
        ]);
    }

    private function save(array $course, ?array $a)
    {
        $p = (array) $this->request->getPost();
        if (trim((string) ($p['title'] ?? '')) === '') {
            return redirect()->back()->withInput()->with('error', 'Give the assignment a title.');
        }
        $sectionId = (int) ($p['section_id'] ?? 0);
        $data      = [
            'course_id'    => $course['id'],
            'section_id'   => $sectionId && db_connect()->table('course_sections')->where(['id' => $sectionId, 'course_id' => $course['id']])->countAllResults() ? $sectionId : null,
            'title'        => mb_substr(trim($p['title']), 0, 200),
            'instructions' => trim((string) ($p['instructions'] ?? '')) ?: null,
            'max_points'   => max(1, (int) ($p['max_points'] ?? 10)),
            'due_at'       => ! empty($p['due_at']) ? date('Y-m-d H:i:s', strtotime($p['due_at'])) : null,
            'allow_file'   => ! empty($p['allow_file']) ? 1 : 0,
        ];
        $model = new AssignmentModel();
        if ($a) {
            $model->update($a['id'], $data);
            $id = (int) $a['id'];
        } else {
            $data['position'] = (int) db_connect()->table('lessons')->selectMax('position')->where('course_id', $course['id'])->get()->getRow('position') + 1;
            $id               = (int) $model->insert($data, true);
        }

        return redirect()->to(site_url('studio/assignments/' . $id))->with('message', 'Assignment saved.');
    }
}
