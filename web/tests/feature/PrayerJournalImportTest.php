<?php

use Tests\Support\AppTestCase;

/**
 * The bundled "My Prayer Journal" course builds from the PDF-derived JSON.
 *
 * @internal
 */
final class PrayerJournalImportTest extends AppTestCase
{
    public function testImportBuildsTheFullEditableCourse(): void
    {
        $this->makeUser('admin');
        command('import:prayer-journal');

        $db     = db_connect();
        $course = $db->table('courses')->where('slug', 'my-prayer-journal')->get()->getRowArray();
        $this->assertNotNull($course);
        $this->assertSame('published', $course['status']);
        $this->assertSame(8, $db->table('course_sections')->where('course_id', $course['id'])->countAllResults());
        $this->assertSame(131, $db->table('lessons')->where('course_id', $course['id'])->countAllResults());
        $this->assertSame(6, $db->table('quizzes')->where('course_id', $course['id'])->countAllResults());

        // Re-running without --force must not duplicate the course.
        command('import:prayer-journal');
        $this->assertSame(1, $db->table('courses')->where('slug', 'my-prayer-journal')->countAllResults());
    }
}
