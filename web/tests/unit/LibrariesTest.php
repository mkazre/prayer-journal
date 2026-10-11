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
