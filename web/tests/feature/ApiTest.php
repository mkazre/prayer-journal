<?php

use Tests\Support\AppTestCase;

/**
 * The /api/v1 contract the mobile app depends on.
 *
 * @internal
 */
final class ApiTest extends AppTestCase
{
    private function json($result): array
    {
        return json_decode($result->getJSON(), true) ?? [];
    }

    public function testRegisterIssuesATokenThatOpensTheDashboard(): void
    {
        $result = $this->withBodyFormat('json')->post('api/v1/auth/register', ['username' => 'newbie', 'email' => 'newbie@example.test', 'password' => 'Long-Enough-1']);
        $result->assertStatus(201);
        $token = $this->json($result)['token'];

        $dash = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('api/v1/dashboard');
        $dash->assertOK();
        $data = $this->json($dash);
        $this->assertSame('newbie', $data['user']['username']);
        $this->assertSame(0, $data['journal']['total']);
    }

    public function testProtectedEndpointsRejectMissingOrBadTokens(): void
    {
        $this->get('api/v1/dashboard')->assertStatus(401);
        $this->withHeaders(['Authorization' => 'Bearer not-a-token'])->get('api/v1/journal')->assertStatus(401);
    }

    public function testAppConfigIsPublic(): void
    {
        $data = $this->json($this->get('api/v1/app'));
        $this->assertArrayHasKey('logo_url', $data);
        $this->assertStringContainsString('logo', $data['logo_url']);
    }

    public function testJournalCrudIsScopedToTheOwner(): void
    {
        $alice = $this->makeUser();
        $bob   = $this->makeUser();
        $ha    = ['Authorization' => 'Bearer ' . $this->tokenFor($alice)];
        $hb    = ['Authorization' => 'Bearer ' . $this->tokenFor($bob)];

        $created = $this->withHeaders($ha)->withBodyFormat('json')->post('api/v1/journal', ['kind' => 'prayer', 'body' => 'Healing for my aunt', 'scripture' => 'James 5:16']);
        $created->assertStatus(201);
        $id = $this->json($created)['id'];

        $answered = $this->json($this->withHeaders($ha)->withBodyFormat('json')->post("api/v1/journal/{$id}/answered", ['answer_note' => 'She is home!']));
        $this->assertTrue($answered['is_answered']);
        $this->assertSame('She is home!', $answered['answer_note']);

        // Bob can neither read nor change Alice's entry.
        $this->assertSame([], $this->json($this->withHeaders($hb)->get('api/v1/journal'))['entries']);
        $this->withHeaders($hb)->withBodyFormat('json')->post("api/v1/journal/{$id}", ['body' => 'hijack'])->assertStatus(404);
        $this->withHeaders($hb)->delete("api/v1/journal/{$id}")->assertStatus(404);

        $list = $this->json($this->withHeaders($ha)->get('api/v1/journal?status=answered'));
        $this->assertSame(1, $list['stats']['answered']);
        $this->assertCount(1, $list['entries']);

        $this->withHeaders($ha)->delete("api/v1/journal/{$id}")->assertOK();
        $this->dontSeeInDatabase('journal_entries', ['id' => $id]);
    }

    public function testEnrollAndCompleteLessonsUpdatesProgress(): void
    {
        ['course' => $course, 'lessons' => $lessons] = $this->makeCourse(2);
        $h = ['Authorization' => 'Bearer ' . $this->tokenFor($this->makeUser())];

        $this->withHeaders($h)->get("api/v1/study/{$course['slug']}")->assertStatus(403);
        $this->withHeaders($h)->post("api/v1/courses/{$course['slug']}/enroll")->assertStatus(201);

        $first = $this->json($this->withHeaders($h)->post("api/v1/study/{$course['slug']}/lesson/{$lessons[0]}/complete"));
        $this->assertSame(50, $first['progress']);
        $last = $this->json($this->withHeaders($h)->post("api/v1/study/{$course['slug']}/lesson/{$lessons[1]}/complete"));
        $this->assertTrue($last['complete']);
        $this->seeInDatabase('certificates', ['course_id' => $course['id']]);
    }

    public function testPaidCoursesCannotBeEnrolledForFree(): void
    {
        ['course' => $course] = $this->makeCourse(1, [], ['price_cents' => 9900, 'access_mode' => 'paid']);
        $h = ['Authorization' => 'Bearer ' . $this->tokenFor($this->makeUser())];

        $this->withHeaders($h)->post("api/v1/courses/{$course['slug']}/enroll")->assertStatus(402);
        $this->dontSeeInDatabase('enrollments', ['course_id' => $course['id']]);
    }

    public function testPrayerWallShowsWhoPrayed(): void
    {
        $h = ['Authorization' => 'Bearer ' . $this->tokenFor($this->makeUser())];
        $id = $this->json($this->withHeaders($h)->withBodyFormat('json')->post('api/v1/prayer-requests', ['body' => 'Pray for our church plant', 'category' => 'guidance']))['id'];

        $prayed = $this->json($this->withHeaders($h)->post("api/v1/prayer-requests/{$id}/pray"));
        $this->assertSame(1, $prayed['prayer_count']);
        // Praying twice does not double count.
        $this->assertSame(1, $this->json($this->withHeaders($h)->post("api/v1/prayer-requests/{$id}/pray"))['prayer_count']);

        $feed = $this->json($this->withHeaders($h)->get('api/v1/prayer-requests'));
        $this->assertTrue($feed[0]['i_prayed']);
        $this->assertTrue($feed[0]['is_mine']);
    }

    public function testQuizAnswersAreNeverSentToTheDevice(): void
    {
        ['course' => $course, 'section' => $section] = $this->makeCourse(1);
        $db = db_connect();
        $db->table('quizzes')->insert(['course_id' => $course['id'], 'section_id' => $section, 'title' => 'Check', 'pass_percent' => 50, 'status' => 'published', 'position' => 9]);
        $quiz = $db->insertID();
        $db->table('quiz_questions')->insert(['quiz_id' => $quiz, 'question' => 'Who taught us to pray?', 'type' => 'single', 'points' => 1, 'explanation' => 'Luke 11:1']);
        $q = $db->insertID();
        $db->table('quiz_options')->insertBatch([['question_id' => $q, 'option_text' => 'Jesus', 'is_correct' => 1, 'position' => 0], ['question_id' => $q, 'option_text' => 'Pharaoh', 'is_correct' => 0, 'position' => 1]]);
        $right = $db->table('quiz_options')->where(['question_id' => $q, 'is_correct' => 1])->get()->getRow('id');

        $h = ['Authorization' => 'Bearer ' . $this->tokenFor($this->makeUser())];
        $this->withHeaders($h)->post("api/v1/courses/{$course['slug']}/enroll");
        $raw = $this->withHeaders($h)->get("api/v1/study/{$course['slug']}/quiz/{$quiz}")->getJSON();
        $this->assertStringNotContainsString('is_correct', $raw);

        $res = $this->json($this->withHeaders($h)->withBodyFormat('json')->post("api/v1/study/{$course['slug']}/quiz/{$quiz}/submit", ['answers' => [$q => (int) $right]]));
        $this->assertSame(100, (int) $res['score_percent']);
        $this->assertSame('Luke 11:1', $res['review'][$q]['explanation']);
    }
}
