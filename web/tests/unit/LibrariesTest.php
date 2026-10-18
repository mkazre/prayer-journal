<?php

use App\Libraries\HtmlSanitizer;
use App\Libraries\Scripture;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class LibrariesTest extends CIUnitTestCase
{
    public function testParsesCommonReferenceStyles(): void
    {
        $r = Scripture::parse('John 3:16');
        $this->assertSame([43, 3, 16, 16], [$r['book'], $r['chapter'], $r['verse_start'], $r['verse_end']]);

        $r = Scripture::parse('1 Cor. 13:4-7');
        $this->assertSame([46, 13, 4, 7], [$r['book'], $r['chapter'], $r['verse_start'], $r['verse_end']]);

        $r = Scripture::parse('Psalm 23');
        $this->assertSame([19, 23, null], [$r['book'], $r['chapter'], $r['verse_start']]);

        $this->assertNull(Scripture::parse('gratitude'));
        $this->assertNull(Scripture::parse('Hezekiah 4:1'));
    }

    public function testReferenceUrlsUseBookSlugs(): void
    {
        $this->assertStringEndsWith('bible/1-peter/2#v9', Scripture::url(Scripture::parse('1 Peter 2:9')));
    }

    public function testLinkifyLeavesExistingLinksAndTagsAlone(): void
    {
        $html = html_entity_decode(Scripture::linkify('<p>Read Romans 8:28 today. <a href="/x">John 1:1</a></p>'), ENT_QUOTES | ENT_HTML5);

        $this->assertStringContainsString('bible/romans/8#v28', $html);
        $this->assertSame(1, substr_count($html, 'bible/'));
    }

    public function testUploadSizeLimitComparesBytes(): void
    {
        // A 5 MB PDF is well under a 200 MB limit (this used to be rejected).
        $this->assertFalse(\App\Libraries\Uploads::exceeds(5 * 1024 * 1024, 204800));
        $this->assertFalse(\App\Libraries\Uploads::exceeds(204800 * 1024, 204800));
        $this->assertTrue(\App\Libraries\Uploads::exceeds(204800 * 1024 + 1, 204800));
    }

    public function testReferenceUrlsCarryVerseRanges(): void
    {
        $this->assertStringEndsWith('bible/philippians/4#v6-7', Scripture::url(Scripture::parse('Phil 4:6-7')));
    }

    public function testLinkifyHandlesJournalStyleReferences(): void
    {
        $links = static function (string $text): array {
            preg_match_all('/data-ref="([^"]+)"/', html_entity_decode(Scripture::linkify(esc($text)), ENT_QUOTES | ENT_HTML5), $m);

            return $m[1];
        };

        $this->assertSame(['Psalms 23:3', 'Proverbs 6:22', 'Zechariah 4:6'], $links("Psalm 23:3\nProverbs 6:22\nZechariah 4:6"));
        // Roman numerals name the numbered book rather than falling through to "John".
        $this->assertSame(['1 John 1:9', '2 Kings 2:9', '3 John 1:4'], $links('I John 1:9, II Kings 2:9 and III John 1:4'));
        $this->assertSame(['Jeremiah 29:11'], $links('Jeremiah 29 : 11'));
        $this->assertSame([], $links('Meet Mark at 10:30, then Job interview'));
    }

    public function testCleanKjvRemovesEditorialMarkup(): void
    {
        $this->assertSame('And God said, Let there be light.', Scripture::cleanKjv('And God {said}, Let there be light{marginal: Heb. light}.'));
    }

    public function testSanitizerStripsScriptsAndEventHandlers(): void
    {
        $out = HtmlSanitizer::clean('<p onclick="steal()">Hi <strong>there</strong><script>alert(1)</script><img src=x onerror="alert(1)"></p><a href="javascript:alert(1)">x</a>');

        $this->assertStringContainsString('<strong>there</strong>', $out);
        $this->assertStringNotContainsString('script', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('javascript:', $out);
    }

    public function testSanitizerOnlyAllowsTrustedVideoEmbeds(): void
    {
        $this->assertStringContainsString('youtube.com/embed/abc', HtmlSanitizer::clean('<iframe src="https://www.youtube.com/embed/abc"></iframe>'));
        $this->assertStringNotContainsString('iframe', HtmlSanitizer::clean('<iframe src="https://evil.example/x"></iframe>'));
    }
}
