<?php

use App\Modules\Lms\Models\EnrollmentModel;
use App\Modules\Lms\Models\QuizAttemptModel;
use App\Modules\Lms\Services\Curriculum;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use Tests\Support\AppTestCase;

/**
 * Curriculum locks, progress and quiz grading.
 *
 * @internal
 */
final class LmsTest extends AppTestCase
{
    use AuthenticationTesting;

    private function items(array $course, ?array $enrollment, int $userId): array
    {
        return Curriculum::flatten(Curriculum::annotate(Curriculum::load((int) $course['id']), $course, $enrollment, $userId));
    }

    public function testGuestsOnlySeePreviewLessonsUnlocked(): void
    {
        ['course' => $course, 'lessons' => $lessons] = $this->makeCourse(2);
        db_connect()->table('lessons')->where('id', $lessons[0])->update(['is_preview' => 1]);

        $items = $this->items($course, null, 0);
        $this->assertFalse($items[0]['locked']);
        $this->assertTrue($items[1]['locked']);
    }

    public function testSequentialCoursesUnlockOneStepAtATime(): void
    {
        ['course' => $course, 'lessons' => $lessons] = $this->makeCourse(3, ['sequential' => true]);
        $user       = $this->makeUser();
        $model      = new EnrollmentModel();
        $enrollment = $model->enroll((int) $course['id'], (int) $user->id);

        $items = $this->items($course, $enrollment, (int) $user->id);
        $this->assertFalse($items[0]['locked']);
        $this->assertTrue($items[1]['locked']);

        $model->markLessonComplete((int) $enrollment['id'], $lessons[0]);
        $items = $this->items($course, $model->find($enrollment['id']), (int) $user->id);
        $this->assertFalse($items[1]['locked']);
        $this->assertTrue($items[2]['locked']);
    }

    public function testDripLessonsOpenDaysAfterEnrollment(): void
    {
        ['course' => $course, 'lessons' => $lessons] = $this->makeCourse(2, ['drip' => 'enrollment']);
        db_connect()->table('lessons')->where('id', $lessons[1])->update(['drip_days' => 7]);
        $user       = $this->makeUser();
        $enrollment = (new EnrollmentModel())->enroll((int) $course['id'], (int) $user->id);

        $items = $this->items($course, $enrollment, (int) $user->id);
        $this->assertTrue($items[1]['locked']);
        $this->assertStringStartsWith('Opens ', (string) $items[1]['reason']);
    }

    public function testCompletingEveryLessonIssuesACertificateAndUndoReopens(): void
    {
        ['course' => $course, 'lessons' => $lessons] = $this->makeCourse(2);
        $user  = $this->makeUser();
        $model = new EnrollmentModel();
        $e     = $model->enroll((int) $course['id'], (int) $user->id);

        $this->assertSame(50, $model->markLessonComplete((int) $e['id'], $lessons[0])['percent']);
        $done = $model->markLessonComplete((int) $e['id'], $lessons[1]);
        $this->assertTrue($done['complete']);
        $this->seeInDatabase('certificates', ['course_id' => $course['id'], 'user_id' => $user->id]);

        $undo = $model->markLessonIncomplete((int) $e['id'], $lessons[1]);
        $this->assertFalse($undo['complete']);
        $this->seeInDatabase('enrollments', ['id' => $e['id'], 'status' => 'active']);
    }

    public function testQuizGradesEveryQuestionType(): void
    {
        ['course' => $course] = $this->makeCourse(1);
        $db = db_connect();
        $db->table('quizzes')->insert(['course_id' => $course['id'], 'title' => 'Review', 'pass_percent' => 75, 'status' => 'published']);
        $quiz = (int) $db->insertID();

        $q = [];
        foreach ([['single', 'Pray in secret?', null], ['multiple', 'Fruits of the Spirit?', null], ['true_false', 'God hears prayer.', null], ['short_text', 'Book after Acts?', 'Romans|Rom']] as $i => [$type, $text, $correct]) {
            $db->table('quiz_questions')->insert(['quiz_id' => $quiz, 'question' => $text, 'type' => $type, 'points' => 1, 'position' => $i, 'correct_text' => $correct]);
            $q[$type] = (int) $db->insertID();
        }
        $opt = static function (int $question, string $text, bool $correct) use ($db): int {
            $db->table('quiz_options')->insert(['question_id' => $question, 'option_text' => $text, 'is_correct' => $correct ? 1 : 0]);

            return (int) $db->insertID();
        };
        $secret = $opt($q['single'], 'Yes', true);
        $opt($q['single'], 'No', false);
        $love   = $opt($q['multiple'], 'Love', true);
        $joy    = $opt($q['multiple'], 'Joy', true);
        $pride  = $opt($q['multiple'], 'Pride', false);
        $true   = $opt($q['true_false'], 'True', true);
        $opt($q['true_false'], 'False', false);

        $user     = $this->makeUser();
        $attempts = new QuizAttemptModel();

        // All right (short answer is case/punctuation-insensitive).
        $r = $attempts->submit($quiz, (int) $user->id, [$q['single'] => $secret, $q['multiple'] => [$joy, $love], $q['true_false'] => $true, $q['short_text'] => ' romans! '], 75);
        $this->assertSame(100, (int) $r['score_percent']);
        $this->assertSame(1, (int) $r['passed']);

        // A partly-right multiple choice earns nothing; 2/4 = 50% fails a 75% pass mark.
        $r = $attempts->submit($quiz, (int) $user->id, [$q['single'] => $secret, $q['multiple'] => [$love, $pride], $q['true_false'] => $true, $q['short_text'] => 'Corinthians'], 75);
        $this->assertSame(50, (int) $r['score_percent']);
        $this->assertSame(0, (int) $r['passed']);
        $this->assertFalse($r['review'][$q['multiple']]['correct']);
    }

    public function testLearnerJournalAutosaveFromLesson(): void
    {
        ['course' => $course, 'lessons' => $lessons] = $this->makeCourse(1, ['default_prompts' => [['key' => 'gratitude', 'label' => 'My Gratitude', 'kind' => 'gratitude']]]);
        $user = $this->makeUser();
        (new EnrollmentModel())->enroll((int) $course['id'], (int) $user->id);

        $result = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->withBodyFormat('json')
            ->call('post', "api/v1/study/{$course['slug']}/lesson/{$lessons[0]}/journal", ['prompt' => 'gratitude', 'body' => 'Thankful for family']);
        // Session auth is not accepted by the token API.
        $result->assertStatus(401);

        $token = $this->tokenFor($user);
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->withBodyFormat('json')
            ->post("api/v1/study/{$course['slug']}/lesson/{$lessons[0]}/journal", ['prompt' => 'gratitude', 'body' => 'Thankful for family'])
            ->assertOK();
        $this->seeInDatabase('journal_entries', ['user_id' => $user->id, 'lesson_id' => $lessons[0], 'kind' => 'gratitude', 'body' => 'Thankful for family']);
    }
}
