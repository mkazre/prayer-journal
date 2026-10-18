<?php

use App\Libraries\SignedLink;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use Tests\Support\AppTestCase;

/**
 * Flipbook reading of purchased PDFs, the download switch, signed links for
 * the mobile app, and the app's library and blog endpoints.
 *
 * @internal
 */
final class FlipbookTest extends AppTestCase
{
    use AuthenticationTesting;

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function privateFile(string $ext, string $body): string
    {
        $rel = 'private/digital/test-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $abs = WRITEPATH . 'uploads/' . substr($rel, 8);
        @mkdir(dirname($abs), 0755, true);
        file_put_contents($abs, $body);
        $this->files[] = $abs;

        return $rel;
    }

    /**
     * A paid order for a digital PDF product.
     *
     * @return array{user: \CodeIgniter\Shield\Entities\User, order: int, item: int, product: int}
     */
    private function purchase(bool $flipbook, bool $download, string $status = 'paid', string $ext = 'pdf'): array
    {
        $db   = db_connect();
        $user = $this->makeUser();
        ['product' => $product, 'variant' => $variant] = $this->makeProduct(9900, 0, 'digital');
        $db->table('products')->where('id', $product)->update(['flipbook' => $flipbook ? 1 : 0, 'allow_download' => $download ? 1 : 0]);
        $db->table('product_variants')->where('id', $variant)->update(['digital_file_path' => $this->privateFile($ext, '%PDF-1.4 sacred pages')]);
        $db->table('orders')->insert([
            'user_id' => $user->id, 'order_number' => 'PJ-' . bin2hex(random_bytes(3)), 'email' => $user->email, 'status' => $status,
            'currency' => 'ZAR', 'subtotal_cents' => 9900, 'total_cents' => 9900, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $order = (int) $db->insertID();
        $db->table('order_items')->insert(['order_id' => $order, 'product_id' => $product, 'variant_id' => $variant, 'title_snapshot' => 'The Prayer Book', 'quantity' => 1, 'unit_price_cents' => 9900]);

        return ['user' => $user, 'order' => $order, 'item' => (int) $db->insertID(), 'product' => $product];
    }

    public function testOrderPageOffersReadOnlineAndDownload(): void
    {
        ['user' => $u, 'order' => $o, 'item' => $i] = $this->purchase(true, true);
        $page = $this->actingAs($u)->get('orders/' . $o);
        $page->assertSee('Read online');
        $page->assertSee('orders/' . $o . '/read/' . $i, null);
        $page->assertSee('orders/' . $o . '/download/' . $i, null);

        $reader = $this->actingAs($u)->get('orders/' . $o . '/read/' . $i);
        $reader->assertOK();
        $reader->assertSee('class="flip-body protect"', null);
        $reader->assertSee('assets/vendor/page-flip/page-flip.browser.js', null);
    }

    public function testReadOnlyBooksCannotBeDownloaded(): void
    {
        ['user' => $u, 'order' => $o, 'item' => $i] = $this->purchase(true, false);
        $this->actingAs($u)->get('orders/' . $o)->assertDontSee('orders/' . $o . '/download/' . $i, null);

        $dl = $this->actingAs($u)->get('orders/' . $o . '/download/' . $i);
        $dl->assertRedirectTo(site_url('orders/' . $o . '/read/' . $i));
    }

    public function testReaderFileIsOnlyForTheBuyerAndNotForDirectVisits(): void
    {
        ['user' => $u, 'order' => $o, 'item' => $i] = $this->purchase(true, false);
        $file = 'orders/' . $o . '/read/' . $i . '/file';

        $ok = $this->actingAs($u)->withHeaders(['Sec-Fetch-Dest' => 'empty'])->get($file);
        $ok->assertOK();
        $this->assertSame('%PDF-1.4 sacred pages', $ok->response()->getBody());
        $this->assertStringContainsString('no-store', $ok->response()->getHeaderLine('Cache-Control'));

        // Typing the file URL into the address bar goes back to the reader.
        $this->actingAs($u)->withHeaders(['Sec-Fetch-Dest' => 'document'])->get($file)->assertRedirectTo(site_url('orders/' . $o . '/read/' . $i));

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->actingAs($this->makeUser())->get($file);
    }

    public function testUnpaidOrdersAndNonFlipbookProductsHaveNoReader(): void
    {
        ['user' => $u, 'order' => $o, 'item' => $i] = $this->purchase(true, true, 'pending');
        try {
            $this->actingAs($u)->get('orders/' . $o . '/read/' . $i);
            $this->fail('Unpaid order opened the reader.');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
        }

        ['user' => $u2, 'order' => $o2, 'item' => $i2] = $this->purchase(false, true);
        $this->actingAs($u2)->get('orders/' . $o2)->assertDontSee('Read online');
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->actingAs($u2)->get('orders/' . $o2 . '/read/' . $i2);
    }

    public function testSignedLinksOpenTheReaderWithoutASession(): void
    {
        ['user' => $u, 'order' => $o, 'item' => $i] = $this->purchase(true, false);
        $sig = SignedLink::sign('flipbook:' . $o . ':' . $i, (int) $u->id);

        $reader = $this->get('orders/' . $o . '/read/' . $i . '?' . $sig);
        $reader->assertOK();
        // The reader fetches the file with the same signature.
        $reader->assertSee('s=' . parse_str_array($sig)['s'], null);
        $this->withHeaders(['Sec-Fetch-Dest' => 'empty'])->get('orders/' . $o . '/read/' . $i . '/file?' . $sig)->assertOK();

        // Tampered, expired or reused-for-another-item signatures fail.
        $this->get('orders/' . $o . '/read/' . $i . '?' . str_replace('s=', 's=0', $sig))->assertRedirectTo(site_url('login'));
        $this->assertNull(SignedLink::verify('flipbook:' . $o . ':' . $i, ['u' => $u->id, 'e' => time() - 1, 's' => 'x']));
        $this->assertNull(SignedLink::verify('flipbook:' . $o . ':999', parse_str_array($sig)));
    }

    public function testLibraryApiListsPurchasesWithSignedLinks(): void
    {
        ['user' => $u, 'order' => $o, 'item' => $i] = $this->purchase(true, false);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $this->tokenFor($u)])->get('api/v1/library');
        $res->assertOK();
        $items = json_decode($res->getJSON(), true);

        $this->assertCount(1, $items);
        $this->assertNull($items[0]['download_url']);
        $this->assertStringContainsString('orders/' . $o . '/read/' . $i . '?u=' . $u->id, $items[0]['read_url']);
        $this->get(substr($items[0]['read_url'], strlen(site_url())))->assertOK();
    }

    public function testBlogApiGatesBodyAndResources(): void
    {
        $db = db_connect();
        $archive = $db->table('blog_archives')->where('slug', 'prayerthon')->get()->getRowArray();
        $db->table('blog_posts')->insert(['archive_id' => $archive['id'], 'title' => 'Km 1', 'slug' => 'km-1', 'excerpt' => 'Intro', 'body' => '<p>Read Psalm 23:1.</p>', 'access' => 'inherit', 'status' => 'published', 'published_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))]);
        $post = (int) $db->insertID();
        $db->table('blog_resources')->insert(['post_id' => $post, 'title' => 'Map', 'file_path' => $this->privateFile('pdf', '%PDF map'), 'kind' => 'pdf', 'size_bytes' => 8]);

        $guest = json_decode($this->get('api/v1/blog/prayerthon/km-1')->getJSON(), true);
        $this->assertFalse($guest['can_read']);
        $this->assertNull($guest['body']);
        $this->assertNull($guest['resources'][0]['url']);

        $user   = $this->makeUser();
        $member = json_decode($this->withHeaders(['Authorization' => 'Bearer ' . $this->tokenFor($user)])->get('api/v1/blog/prayerthon/km-1')->getJSON(), true);
        $this->assertTrue($member['can_read']);
        $this->assertStringContainsString('bible-ref', $member['body']);
        $this->get(substr($member['resources'][0]['url'], strlen(site_url())))->assertStatus(200);

        $config = json_decode($this->get('api/v1/app')->getJSON(), true);
        $this->assertSame([['slug' => 'prayerthon', 'title' => 'Prayerthon']], $config['blog_menu']);
    }
}

function parse_str_array(string $q): array
{
    parse_str($q, $out);

    return $out;
}
