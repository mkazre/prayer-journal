<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer for rich text written in the editor (lessons,
 * course descriptions, product descriptions). Removes scripts, event
 * handlers, styles and unsafe URLs; only YouTube/Vimeo iframes survive.
 */
class HtmlSanitizer
{
    private const TAGS = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'a' => ['href', 'title', 'target'], 'img' => ['src', 'alt', 'title'], 'hr' => [], 'code' => [], 'pre' => [],
        'figure' => [], 'figcaption' => [], 'span' => ['class'], 'div' => ['class'], 'sub' => [], 'sup' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
        'iframe' => ['src', 'allow', 'allowfullscreen', 'title'],
    ];

    private const CLASSES = ['scripture', 'callout', 'video-embed'];

    private const DROP_WITH_CONTENT = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'noscript', 'svg', 'math', 'template', 'head', 'title', 'meta', 'link'];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return esc(strip_tags($html));
        }
        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(DOMNode $node): void
    {
        // Iterate over a static copy since we mutate the tree.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! array_key_exists($tag, self::TAGS)) {
                // Unwrap unknown elements but keep their text/children.
                self::walk($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (! in_array($name, self::TAGS[$tag], true)) {
                    $child->removeAttribute($attr->name);

                    continue;
                }
                $value = trim($attr->value);
                if (in_array($name, ['href', 'src'], true) && ! self::safeUrl($value, $tag)) {
                    $child->removeAttribute($attr->name);
                }
                if ($name === 'class') {
                    $kept = array_intersect(preg_split('/\s+/', $value) ?: [], self::CLASSES);
                    $kept === [] ? $child->removeAttribute('class') : $child->setAttribute('class', implode(' ', $kept));
                }
            }

            if ($tag === 'iframe' && ! $child->hasAttribute('src')) {
                $node->removeChild($child);

                continue;
            }
            if ($tag === 'a') {
                if ($child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $child->removeAttribute('target');
                }
            }

            self::walk($child);
        }
    }

    private static function safeUrl(string $url, string $tag): bool
    {
        if ($tag === 'iframe') {
            return (bool) preg_match('~^https://(www\.youtube(-nocookie)?\.com/embed/|player\.vimeo\.com/video/)~i', $url);
        }
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, $tag === 'img' ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'], true);
    }
}
