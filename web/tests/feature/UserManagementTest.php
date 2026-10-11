<?php

use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use Config\Filters;
use Tests\Support\AppTestCase;

/**
 * Creating and managing accounts from Studio → Users, and the Manager role.
 *
 * @internal
 */
final class UserManagementTest extends AppTestCase
{
    use AuthenticationTesting;

    protected function setUp(): void
    {
        parent::setUp();
        // These tests exercise the controllers; CSRF itself is covered in SecurityTest.
        $filters = config(Filters::class);
        unset($filters->globals['before']['csrf']);
        Factories::injectMock('config', 'Filters', $filters);
    }

    private function identity(string $email): ?array
    {
        return db_connect()->table('auth_identities')->where(['type' => 'email_password', 'secret' => $email])->get()->getRowArray();
    }

    public function testAdminCreatesAManagerWithAGeneratedPassword(): void
    {
        $admin  = $this->makeUser('admin');
        $result = $this->actingAs($admin)->post('studio/users', ['display_name' => 'Thandi Mokoena', 'email' => 'Thandi@Example.test', 'groups' => ['manager']]);

        $result->assertRedirectTo(site_url('studio/users'));
        $this->assertStringContainsString('Temporary password:', (string) session('message'));
        $row  = $this->identity('thandi@example.test');
        $this->assertNotNull($row);
        $user = auth()->getProvider()->findById((int) $row['user_id']);
        $this->assertSame('thandi', $user->username);
        $this->assertTrue($user->inGroup('manager'));
        $this->assertTrue($user->isActivated());
        $this->seeInDatabase('user_profiles', ['user_id' => $user->id, 'display_name' => 'Thandi Mokoena']);

        // The generated password works.
        preg_match('/Temporary password: (\S+)/', (string) session('message'), $m);
        $this->assertTrue(auth()->check(['email' => 'thandi@example.test', 'password' => $m[1]])->isOK());
    }

    public function testCustomersAreTheDefaultAndDuplicatesAreRejected(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post('studio/users', ['email' => 'buyer@example.test', 'password' => 'Long-Enough-1']);
        $user = auth()->getProvider()->findById((int) $this->identity('buyer@example.test')['user_id']);
        $this->assertSame(['student'], $user->getGroups());

        $this->actingAs($admin)->post('studio/users', ['email' => 'buyer@example.test', 'password' => 'Long-Enough-1']);
        $this->assertStringContainsString('already exists', (string) session('error'));
    }

    public function testOnlySuperadminsCanGrantSuperadmin(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post('studio/users', ['email' => 'boss@example.test', 'password' => 'Long-Enough-1', 'groups' => ['superadmin']]);
        $this->assertNull($this->identity('boss@example.test'));

        $super = $this->makeUser('superadmin');
        $this->actingAs($super)->post('studio/users', ['email' => 'boss@example.test', 'password' => 'Long-Enough-1', 'groups' => ['superadmin']]);
        $this->assertNotNull($this->identity('boss@example.test'));
    }

    public function testEditingChangesDetailsRolesAndPassword(): void
    {
        $admin  = $this->makeUser('admin');
        $person = $this->makeUser('student', 'grace');
        $this->tokenFor($person);

        $this->actingAs($admin)->post('studio/users/' . $person->id, [
            'display_name' => 'Grace M', 'username' => 'gracem', 'email' => 'grace.m@example.test', 'password' => 'Brand-New-Pass-1', 'groups' => ['instructor', 'student'],
        ])->assertRedirectTo(site_url('studio/users'));

        $user = auth()->getProvider()->findById($person->id);
        $this->assertSame('gracem', $user->username);
        $this->assertTrue($user->inGroup('instructor'));
        $this->assertTrue(auth()->check(['email' => 'grace.m@example.test', 'password' => 'Brand-New-Pass-1'])->isOK());
        // App tokens are revoked after a password reset.
        $this->dontSeeInDatabase('auth_identities', ['user_id' => $person->id, 'type' => 'access_token']);
    }

    public function testSuspendedUsersCannotUseTheApi(): void
    {
        $admin  = $this->makeUser('admin');
        $person = $this->makeUser();
        $token  = $this->tokenFor($person);

        $this->actingAs($admin)->post('studio/users/' . $person->id . '/suspend');
        $this->assertTrue(auth()->getProvider()->findById($person->id)->isBanned());
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('api/v1/dashboard')->assertStatus(401);

        $this->actingAs($admin)->post('studio/users/' . $person->id . '/suspend');
        $this->assertFalse(auth()->getProvider()->findById($person->id)->isBanned());
    }

    public function testDeletingFreesTheEmailAndBlocksSelfDeletion(): void
    {
        $admin  = $this->makeUser('admin');
        $person = $this->makeUser('student', 'leaving');

        $this->actingAs($admin)->post('studio/users/' . $person->id . '/delete');
        $this->assertNull(auth()->getProvider()->findById($person->id));
        $this->assertNull($this->identity('leaving@example.test'));

        $this->actingAs($admin)->post('studio/users/' . $admin->id . '/delete');
        $this->assertNotNull(auth()->getProvider()->findById($admin->id));
    }

    public function testManagersRunTheBusinessButNotSettingsOrUsers(): void
    {
        $manager = $this->makeUser('manager');
        foreach (['studio', 'studio/courses', 'studio/products', 'studio/orders', 'studio/discounts', 'studio/reports', 'studio/prayer'] as $path) {
            $this->actingAs($manager)->get($path)->assertOK();
        }
        $this->actingAs($manager)->get('studio/users')->assertRedirectTo(site_url('studio'));
        $this->actingAs($manager)->get('studio/settings')->assertRedirectTo(site_url('studio'));
    }
}
