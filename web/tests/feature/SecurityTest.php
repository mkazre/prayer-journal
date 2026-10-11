<?php

use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use Config\Filters;
use Tests\Support\AppTestCase;

/**
 * CSRF, role filters and ownership checks.
 *
 * @internal
 */
final class SecurityTest extends AppTestCase
{
    use AuthenticationTesting;

    public function testFormPostWithoutCsrfTokenIsRejected(): void
    {
        $user = $this->makeUser();

        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->actingAs($user)->post('prayer-requests/create', ['body' => 'Please pray for my family.']);
    }

    public function testApiIsExemptFromCsrfButNeedsAToken(): void
    {
        $this->post('api/v1/journal', ['body' => 'x'])->assertStatus(401);
    }

    public function testGuestsAreSentToLoginFromStudio(): void
    {
        $this->get('studio')->assertRedirectTo(site_url('login'));
    }

    public function testStudentsCannotOpenStudio(): void
    {
        $result = $this->actingAs($this->makeUser('student'))->get('studio');

        $this->assertNotSame(200, $result->response()->getStatusCode());
    }

    public function testInstructorsCanOpenStudioButNotAdminPages(): void
    {
        $instructor = $this->makeUser('instructor');
        $this->actingAs($instructor)->get('studio')->assertOK();

        $this->actingAs($instructor)->get('studio/settings')->assertRedirectTo(site_url('studio'));
    }

    public function testAdminsCanOpenEveryStudioArea(): void
    {
        $admin = $this->makeUser('admin');
        foreach (['studio', 'studio/courses', 'studio/products', 'studio/orders', 'studio/discounts', 'studio/shipping', 'studio/reports', 'studio/settings', 'studio/users', 'studio/prayer'] as $path) {
            $this->actingAs($admin)->get($path)->assertOK();
        }
    }

    public function testOrdersAreOnlyVisibleToTheirOwner(): void
    {
        $owner    = $this->makeUser();
        $stranger = $this->makeUser();
        db_connect()->table('orders')->insert([
            'user_id' => $owner->id, 'order_number' => 'PJ-TEST-1', 'email' => 'owner@example.test', 'status' => 'pending',
            'currency' => 'ZAR', 'subtotal_cents' => 1000, 'total_cents' => 1000, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = db_connect()->insertID();

        $this->actingAs($owner)->get('orders/' . $id)->assertOK();

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->actingAs($stranger)->get('orders/' . $id);
    }

    public function testUsersCannotDeleteSomeoneElsesPrayerRequest(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        db_connect()->table('prayer_requests')->insert(['user_id' => $owner->id, 'body' => 'Healing', 'created_at' => date('Y-m-d H:i:s')]);
        $id = db_connect()->insertID();

        try {
            $this->withoutCsrf()->actingAs($other)->post('prayer-requests/' . $id . '/delete');
            $this->fail('Expected a 404.');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException) {
            // expected
        }
        $this->seeInDatabase('prayer_requests', ['id' => $id]);
    }

    private function withoutCsrf(): self
    {
        $filters = config(Filters::class);
        $filters->globals['before'] = array_values(array_filter($filters->globals['before'], static fn ($f, $k) => $f !== 'csrf' && $k !== 'csrf', ARRAY_FILTER_USE_BOTH));
        unset($filters->globals['before']['csrf']);
        Factories::injectMock('config', 'Filters', $filters);

        return $this;
    }
}
