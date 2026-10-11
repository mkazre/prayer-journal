<?php

namespace App\Modules\Studio\Controllers;

use App\Modules\Lms\Models\CourseModel;
use App\Modules\Lms\Models\CourseSectionModel;
use App\Modules\Lms\Services\Curriculum;

/**
 * Drag-and-drop course builder: modules and their lessons, quizzes and
 * assignments.
 */
class CurriculumController extends StudioController
{
    public function index(int $courseId): string
    {
        $course = $this->course($courseId);

        return $this->render('courses/curriculum', [
            'title'    => 'Curriculum · ' . $course['title'],
            'active'   => 'courses',
            'tab'      => 'curriculum',
            'course'   => $course,
            'sections' => Curriculum::load($courseId, false),
            'settings' => CourseModel::settings($course),
        ]);
    }

    /**
     * Saves the full order: {"sections":[{"id":3,"items":[{"kind":"lesson","id":9}, ...]}, ...]}
     */
    public function reorder(int $courseId)
    {
        $this->course($courseId);
        $db       = db_connect();
        $sections = (array) ($this->input()['sections'] ?? []);
        $tables   = ['lesson' => 'lessons', 'quiz' => 'quizzes', 'assignment' => 'assignments'];
        $position = 0;
        $db->transStart();
        foreach ($sections as $si => $section) {
            $sectionId = (int) ($section['id'] ?? 0) ?: null;
            if ($sectionId) {
                $db->table('course_sections')->where(['id' => $sectionId, 'course_id' => $courseId])->update(['position' => $si + 1]);
            }
            foreach ((array) ($section['items'] ?? []) as $item) {
                $table = $tables[$item['kind'] ?? ''] ?? null;
                if ($table) {
                    $db->table($table)->where(['id' => (int) $item['id'], 'course_id' => $courseId])->update(['section_id' => $sectionId, 'position' => ++$position]);
                }
            }
        }
        $db->transComplete();

        return $this->done('Order saved.');
    }

    public function createSection(int $courseId)
    {
        $this->course($courseId);
        $title = trim((string) $this->post('title'));
        if ($title === '') {
            return $this->failed('Give the module a title.');
        }
        $model = new CourseSectionModel();
        $id    = $model->insert(['course_id' => $courseId, 'title' => mb_substr($title, 0, 200), 'summary' => trim((string) $this->post('summary')) ?: null, 'position' => $model->nextPosition($courseId)], true);

        return $this->done('Module added.', ['id' => $id], site_url('studio/courses/' . $courseId . '/curriculum'));
    }

    public function updateSection(int $sectionId)
    {
        $section = $this->section($sectionId);
        $title   = trim((string) $this->post('title'));
        if ($title === '') {
            return $this->failed('Give the module a title.');
        }
        (new CourseSectionModel())->update($sectionId, ['title' => mb_substr($title, 0, 200), 'summary' => trim((string) $this->post('summary')) ?: null]);

        return $this->done('Module updated.', [], site_url('studio/courses/' . $section['course_id'] . '/curriculum'));
    }

    /**
     * Deletes a module; its items move to the previous module (or become unsorted).
     */
    public function deleteSection(int $sectionId)
    {
        $section = $this->section($sectionId);
        $db      = db_connect();
        $target  = $db->table('course_sections')->where('course_id', $section['course_id'])->where('id !=', $sectionId)->where('position <=', $section['position'])->orderBy('position', 'DESC')->get()->getRow('id');
        foreach (['lessons', 'quizzes', 'assignments'] as $t) {
            $db->table($t)->where('section_id', $sectionId)->update(['section_id' => $target]);
        }
        (new CourseSectionModel())->delete($sectionId);

        return $this->done('Module deleted. Its lessons were kept.', [], site_url('studio/courses/' . $section['course_id'] . '/curriculum'));
    }

    private function section(int $id): array
    {
        $section = (new CourseSectionModel())->find($id);
        if (! $section) {
            $this->notFound();
        }
        $this->course((int) $section['course_id']);

        return $section;
    }
}
