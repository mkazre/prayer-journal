<?php

namespace Tests\Support;

use App\Modules\Core\Models\AppSettingModel;
use App\Modules\Lms\Models\CourseModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Base class for tests that need the full schema: every migration runs
 * against a fresh in-memory SQLite database before each test.
 */
abstract class AppTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();
        // Fresh services (session, auth) so one test's login never leaks into the next.
        $this->resetServices();
        $this->resetFactories();
        site_setting('__flush__');
    }

    protected function setting(string $key, string $value): void
    {
        (new AppSettingModel())->put($key, $value);
        site_setting('__flush__');
    }

    protected function makeUser(string $group = 'student', string $username = ''): User
    {
        $provider = auth()->getProvider();
        $username = $username ?: $group . bin2hex(random_bytes(3));
        $user     = new User(['username' => $username, 'email' => $username . '@example.test', 'password' => 'Secret-Pass-123!']);
        $provider->save($user);
        $user = $provider->findById($provider->getInsertID());
        $user->addGroup($group);

        return $user;
    }

    protected function tokenFor(User $user): string
    {
        return $user->generateAccessToken('test')->raw_token;
    }

    /**
     * A published course with one module of $lessons published lessons.
     *
     * @return array{course: array, lessons: list<int>, section: int}
     */
    protected function makeCourse(int $lessons = 3, array $settings = [], array $overrides = []): array
    {
        $db      = db_connect();
        $author  = $this->makeUser('instructor');
        $model   = new CourseModel();
        $id      = (int) $model->insert(array_merge([
            'title' => 'Learning to Pray', 'slug' => 'learning-to-pray-' . bin2hex(random_bytes(2)), 'summary' => 'A short course.',
            'status' => 'published', 'access_mode' => 'free', 'price_cents' => 0, 'currency' => 'ZAR', 'created_by' => $author->id,
            'settings' => json_encode(array_merge(CourseModel::DEFAULT_SETTINGS, $settings)),
        ], $overrides), true);
        $db->table('course_sections')->insert(['course_id' => $id, 'title' => 'Week 1', 'position' => 1]);
        $section = (int) $db->insertID();
        $ids     = [];
        for ($i = 1; $i <= $lessons; $i++) {
            $db->table('lessons')->insert([
                'course_id' => $id, 'section_id' => $section, 'title' => 'Lesson ' . $i, 'type' => 'text', 'content' => '<p>Lesson ' . $i . '</p>',
                'position' => $i, 'status' => 'published', 'price_cents' => 0, 'duration_mins' => 5,
            ]);
            $ids[] = (int) $db->insertID();
        }

        return ['course' => $model->find($id), 'lessons' => $ids, 'section' => $section];
    }

    /**
     * A physical product with one variant and $stock units at the default location.
     *
     * @return array{product: int, variant: int}
     */
    protected function makeProduct(int $priceCents = 25000, int $stock = 10, string $type = 'physical'): array
    {
        $db = db_connect();
        $db->table('products')->insert([
            'title' => 'Prayer Journal', 'slug' => 'prayer-journal-' . bin2hex(random_bytes(2)), 'description' => 'A journal.',
            'status' => 'published', 'product_type' => $type, 'track_inventory' => 1, 'allow_backorder' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $product = (int) $db->insertID();
        $db->table('product_variants')->insert(['product_id' => $product, 'sku' => 'PJ-' . $product, 'price_cents' => $priceCents, 'is_active' => 1, 'position' => 0]);
        $variant  = (int) $db->insertID();
        $location = $db->table('inventory_locations')->get()->getRowArray();
        if (! $location) {
            $db->table('inventory_locations')->insert(['name' => 'Warehouse']);
            $location = ['id' => $db->insertID()];
        }
        $db->table('inventory_levels')->insert(['variant_id' => $variant, 'location_id' => $location['id'], 'quantity' => $stock]);

        return ['product' => $product, 'variant' => $variant];
    }
}
