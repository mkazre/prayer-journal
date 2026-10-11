<?php

namespace App\Modules\Studio\Controllers;

use App\Libraries\HtmlSanitizer;
use App\Libraries\Scripture;
use App\Libraries\Uploads;
use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\LessonModel;

class LessonController extends StudioController
{
    public function create(int $courseId)
    {
        $course = $this->course($courseId);
        if ($this->request->getMethod() === 'POST') {
            return $this->save($course, null);
        }
        $lesson = ['id' => null, 'title' => '', 'type' => (string) ($this->request->getGet('type') ?: 'text'), 'section_id' => (int) $this->request->getGet('section') ?: null,
            'summary' => '', 'content' => '', 'video_url' => '', 'attachment_path' => null, 'bible_reference' => '', 'duration_mins' => null,
            'is_preview' => 0, 'drip_days' => null, 'available_at' => null, 'price_cents' => 0, 'status' => 'published', 'settings' => null];

        return $this->form($course, $lesson);
    }

    public function edit(int $id)
    {
        $lesson = (new LessonModel())->find($id);
        if (! $lesson) {
            $this->notFound();
        }
        $course = $this->course((int) $lesson['course_id']);
        if ($this->request->getMethod() === 'POST') {
            return $this->save($course, $lesson);
        }

        return $this->form($course, $lesson);
    }

    public function delete(int $id)
    {
        $lesson = (new LessonModel())->find($id);
        if (! $lesson) {
            $this->notFound();
        }
        $this->course((int) $lesson['course_id']);
        Uploads::delete($lesson['attachment_path']);
        (new LessonModel())->delete($id);

        return $this->done('Lesson deleted.', [], site_url('studio/courses/' . $lesson['course_id'] . '/curriculum'));
    }

    public function duplicate(int $id)
    {
        $lesson = (new LessonModel())->find($id);
        if (! $lesson) {
            $this->notFound();
        }
        $this->course((int) $lesson['course_id']);
        unset($lesson['id'], $lesson['created_at'], $lesson['updated_at']);
        $lesson['title']           .= ' (copy)';
        $lesson['status']           = 'draft';
        $lesson['attachment_path']  = null;
        $lesson['position']         = (int) $lesson['position'] + 1;
        $newId                      = (new LessonModel())->insert($lesson, true);

        return redirect()->to(site_url('studio/lessons/' . $newId))->with('message', 'Lesson duplicated as a draft.');
    }

    private function form(array $course, array $lesson): string
    {
        return $this->render('lessons/form', [
            'title'    => $lesson['id'] ? 'Edit lesson' : 'New lesson',
            'active'   => 'courses',
            'course'   => $course,
            'lesson'   => $lesson,
            'settings' => LessonModel::settings($lesson),
            'course_settings' => CourseModel::settings($course),
            'sections' => db_connect()->table('course_sections')->where('course_id', $course['id'])->orderBy('position')->get()->getResultArray(),
        ]);
    }

    private function save(array $course, ?array $lesson)
    {
        $p     = (array) $this->request->getPost();
        $title = trim((string) ($p['title'] ?? ''));
        if ($title === '') {
            return redirect()->back()->withInput()->with('error', 'Give the lesson a title.');
        }
        $ref = trim((string) ($p['bible_reference'] ?? ''));
        if ($ref !== '' && ! Scripture::parse($ref)) {
            return redirect()->back()->withInput()->with('error', 'The key scripture "' . $ref . '" was not recognised. Try a format like "John 3:16" or "Psalm 23".');
        }
        $prompts = [];
        foreach ((array) ($p['prompts'] ?? []) as $row) {
            if (trim((string) ($row['label'] ?? '')) !== '') {
                $prompts[] = ['key' => url_title(mb_substr($row['label'], 0, 40), '-', true) ?: 'p' . count($prompts), 'label' => trim($row['label']), 'hint' => trim((string) ($row['hint'] ?? '')), 'kind' => in_array($row['kind'] ?? '', ['prayer', 'meditation', 'gratitude', 'note'], true) ? $row['kind'] : 'note'];
            }
        }
        $resources = [];
        foreach ((array) ($p['resources'] ?? []) as $row) {
            $url = trim((string) ($row['url'] ?? ''));
            if ($url !== '' && preg_match('~^https?://~i', $url)) {
                $resources[] = ['label' => trim((string) ($row['label'] ?? '')), 'url' => $url];
            }
        }
        $sectionId = (int) ($p['section_id'] ?? 0);
        if ($sectionId && ! db_connect()->table('course_sections')->where(['id' => $sectionId, 'course_id' => $course['id']])->countAllResults()) {
            $sectionId = 0;
        }
        $data = [
            'course_id'       => $course['id'],
            'section_id'      => $sectionId ?: null,
            'title'           => mb_substr($title, 0, 200),
            'type'            => isset(LessonModel::TYPES[$p['type'] ?? '']) ? $p['type'] : 'text',
            'summary'         => trim(mb_substr((string) ($p['summary'] ?? ''), 0, 500)) ?: null,
            'content'         => HtmlSanitizer::clean((string) ($p['content'] ?? '')),
            'video_url'       => trim((string) ($p['video_url'] ?? '')) ?: null,
            'bible_reference' => $ref !== '' ? Scripture::parse($ref)['label'] : null,
            'duration_mins'   => ($p['duration_mins'] ?? '') !== '' ? max(0, (int) $p['duration_mins']) : null,
            'is_preview'      => ! empty($p['is_preview']) ? 1 : 0,
            'drip_days'       => ($p['drip_days'] ?? '') !== '' ? max(0, (int) $p['drip_days']) : null,
            'available_at'    => ! empty($p['available_at']) ? date('Y-m-d H:i:s', strtotime($p['available_at'])) : null,
            'price_cents'     => max(0, $this->money('price')),
            'status'          => in_array($p['status'] ?? '', ['published', 'draft'], true) ? $p['status'] : 'draft',
            'settings'        => json_encode(['prompts' => $prompts, 'resources' => $resources, 'key_verse' => $ref], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        try {
            if ($file = Uploads::file($this->request->getFile('attachment'), 'lessons', 102400)) {
                Uploads::delete($lesson['attachment_path'] ?? null);
                $data['attachment_path'] = $file;
            }
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        if (! empty($p['remove_attachment'])) {
            Uploads::delete($lesson['attachment_path'] ?? null);
            $data['attachment_path'] = null;
        }

        $model = new LessonModel();
        if ($lesson) {
            $model->update($lesson['id'], $data);
            $id = (int) $lesson['id'];
        } else {
            $max              = db_connect()->table('lessons')->selectMax('position')->where('course_id', $course['id'])->get()->getRow('position');
            $data['position'] = (int) $max + 1;
            $id               = (int) $model->insert($data, true);
        }
        $next = ($p['after'] ?? '') === 'new' ? site_url('studio/courses/' . $course['id'] . '/lessons/new?section=' . ($sectionId ?: '')) : (($p['after'] ?? '') === 'curriculum' ? site_url('studio/courses/' . $course['id'] . '/curriculum') : site_url('studio/lessons/' . $id));

        return redirect()->to($next)->with('message', $lesson ? 'Lesson saved.' : 'Lesson created.');
    }
}
