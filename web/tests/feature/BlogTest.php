<?php

use App\Modules\Blog\Services\BlogAccess;
use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use Config\Filters;
use Tests\Support\AppTestCase;

/**
 * Blog archives, posts, access levels and protected resources.
 *
 * @internal
 */
final class BlogTest extends AppTestCase
{
    use AuthenticationTesting;

    private string $file = '';

    protected function setUp(): void
    {
        parent::setUp();
        // These tests exercise the controllers; CSRF itself is covered in SecurityTest.
        $filters = config(Filters::class);
        unset($filters->globals['before']['csrf']);
        Factories::injectMock('config', 'Filters', $filters);
    }

    protected function tearDown(): void
    {
        if ($this->file && is_file($this->file)) {
            @unlink($this->file);
        }
        parent::tearDown();
    }

    /**
     * A published post in the seeded Prayerthon archive, with one PDF resource.
     *
     * @return array{post: array, resource: int}
     */
    private function makePost(string $access = 'inherit', string $body = '<p>Kilometre one: pray for your family.</p>'): array
    {
        $db      = db_connect();
        $archive = $db->table('blog_archives')->where('slug', 'prayerthon')->get()->getRowArray();
        $db->table('blog_posts')->insert([
            'archive_id' => $archive['id'], 'title' => 'Kilometre 1', 'slug' => 'kilometre-1-' . bin2hex(random_bytes(2)), 'excerpt' => 'Starting well.',
            'body' => $body, 'access' => $access, 'status' => 'published', 'published_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ]);
        $post = $db->table('blog_posts')->where('id', $db->insertID())->get()->getRowArray();

        $rel = 'private/blog/test-' . bin2hex(random_bytes(4)) . '.pdf';
        $this->file = WRITEPATH . 'uploads/' . substr($rel, 8);
        @mkdir(dirname($this->file), 0755, true);
        file_put_contents($this->file, '%PDF-1.4 prayerthon test file');
        $db->table('blog_resources')->insert(['post_id' => $post['id'], 'title' => 'Prayer map', 'file_path' => $rel, 'kind' => 'pdf', 'mime' => 'application/pdf', 'size_bytes' => filesize($this->file), 'position' => 1]);

        return ['post' => $post, 'resource' => (int) $db->insertID()];
    }

    public function testHomePageShowsPrayerthonBlockAndMenuLink(): void
    {
        $result = $this->get('/');
        $result->assertOK();
        $result->assertSee('Embark on a guided prayer journey, with each kilometre inspiring focused prayer, reflection and a deeper connection with GOD.');
        $result->assertSee('href="' . site_url('blog/prayerthon') . '"', null);
        $archive = $this->get('blog/prayerthon');
        $archive->assertOK();
        $archive->assertSee('Prayerthon');
    }

    public function testGuestsSeeTheIntroButNotTheTextOrFiles(): void
    {
        ['post' => $post, 'resource' => $rid] = $this->makePost();
        $page = $this->get('blog/prayerthon/' . $post['slug']);
        $page->assertOK();
        $page->assertSee('Starting well.');
        $page->assertSee('Sign in to read the rest');
        $page->assertDontSee('pray for your family');
        $page->assertDontSee('blog/resources/' . $rid);

        $this->get('blog/resources/' . $rid)->assertRedirect();
    }

    public function testMembersReadAndDownloadMembersPosts(): void
    {
        ['post' => $post, 'resource' => $rid] = $this->makePost();
        $user = $this->makeUser();

        $page = $this->actingAs($user)->get('blog/prayerthon/' . $post['slug']);
        $page->assertOK();
        $page->assertSee('pray for your family');

        $file = $this->actingAs($user)->get('blog/resources/' . $rid . '?download=1');
        // The whole-file response streams on send, so check what the browser is told.
        $file->assertStatus(200);
        $this->assertInstanceOf(\CodeIgniter\HTTP\DownloadResponse::class, $file->response());
    }

    public function testRangeRequestsAreServedForMediaPlayers(): void
    {
        ['resource' => $rid] = $this->makePost();
        $result = $this->actingAs($this->makeUser())->withHeaders(['Range' => 'bytes=0-7'])->get('blog/resources/' . $rid);

        $result->assertStatus(206);
        $this->assertSame('%PDF-1.4', $result->response()->getBody());
        $this->assertStringStartsWith('bytes 0-7/', $result->response()->getHeaderLine('Content-Range'));
    }

    public function testCustomerOnlyPostsNeedAPurchase(): void
    {
        ['post' => $post, 'resource' => $rid] = $this->makePost('customers');
        $user = $this->makeUser();

        $locked = $this->actingAs($user)->get('blog/prayerthon/' . $post['slug']);
        $locked->assertSee('This post is free for our customers');
        $locked->assertDontSee('pray for your family');
        $this->actingAs($user)->get('blog/resources/' . $rid)->assertRedirect();

        // Buying a course makes them a customer.
        ['course' => $course] = $this->makeCourse(1);
        db_connect()->table('enrollments')->insert(['course_id' => $course['id'], 'user_id' => $user->id, 'status' => 'active', 'source' => 'purchase', 'enrolled_at' => date('Y-m-d H:i:s')]);
        $this->assertTrue(BlogAccess::isCustomer((int) $user->id));
        $this->actingAs($user)->get('blog/prayerthon/' . $post['slug'])->assertSee('pray for your family');
    }

    public function testArchiveSettingAppliesUnlessThePostOverridesIt(): void
    {
        $archive = ['access' => 'customers'];
        $this->assertSame('customers', BlogAccess::level(['access' => 'inherit'], $archive));
        $this->assertSame('members', BlogAccess::level(['access' => 'members'], $archive));
        $this->assertSame('customers', BlogAccess::level(['access' => 'customers'], ['access' => 'members']));
    }

    public function testDraftsAreHiddenFromReaders(): void
    {
        ['post' => $post] = $this->makePost();
        db_connect()->table('blog_posts')->where('id', $post['id'])->update(['status' => 'draft']);

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->actingAs($this->makeUser())->get('blog/prayerthon/' . $post['slug']);
    }

    public function testManagersCreateArchivesAndPostsInStudio(): void
    {
        $manager = $this->makeUser('manager');
        $list = $this->actingAs($manager)->get('studio/blog');
        $list->assertOK();
        $list->assertSee('Prayerthon');

        $this->actingAs($manager)->post('studio/blog/archives/new', ['title' => 'Advent Reflections', 'access' => 'customers', 'status' => 'published', 'show_in_menu' => '1'])->assertRedirect();
        $archive = db_connect()->table('blog_archives')->where('slug', 'advent-reflections')->get()->getRowArray();
        $this->assertSame('customers', $archive['access']);

        $this->actingAs($manager)->post('studio/blog/archives/' . $archive['id'] . '/posts/new', [
            'title' => 'Week one', 'excerpt' => 'Hope', 'body' => '<p>Read Isaiah 9:6.</p><script>alert(1)</script>', 'status' => 'published', 'access' => 'inherit',
        ])->assertRedirect();
        $post = db_connect()->table('blog_posts')->where('archive_id', $archive['id'])->get()->getRowArray();
        $this->assertSame('week-one', $post['slug']);
        $this->assertStringNotContainsString('<script', $post['body']);
        $this->assertNotNull($post['published_at']);

        // The new archive shows in the site menus.
        $page = $this->get('blog/advent-reflections');
        $page->assertOK();
        $page->assertSee('href="' . site_url('blog/advent-reflections') . '"', null);
    }

    public function testStudentsCannotOpenTheBlogStudio(): void
    {
        $this->actingAs($this->makeUser())->get('studio/blog')->assertRedirect();
    }
}
