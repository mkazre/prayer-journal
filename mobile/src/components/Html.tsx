import React from 'react';
import { Linking, Text, View } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../context/ThemeContext';
import { type as t } from '../theme/colors';

/**
 * A small renderer for the sanitised HTML the API returns (headings,
 * paragraphs, lists, quotes, bold/italic and links). Avoids a WebView so
 * text follows the app's theme and reading size.
 */
type Span = { text: string; bold?: boolean; italic?: boolean; href?: string };
type Block = { tag: 'p' | 'h2' | 'h3' | 'h4' | 'li' | 'oli' | 'quote' | 'hr'; spans: Span[]; index?: number };

const ENTITIES: Record<string, string> = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ', rsquo: '’', lsquo: '‘', rdquo: '”', ldquo: '“', mdash: '—', ndash: '–', hellip: '…' };

export function decode(s: string): string {
  return s.replace(/&(#x?[0-9a-f]+|[a-z]+);/gi, (m, e: string) => {
    if (e[0] === '#') {
      const code = e[1] === 'x' || e[1] === 'X' ? parseInt(e.slice(2), 16) : parseInt(e.slice(1), 10);
      return Number.isFinite(code) ? String.fromCodePoint(code) : m;
    }
    return ENTITIES[e.toLowerCase()] ?? m;
  });
}

export function parseHtml(html: string): Block[] {
  const blocks: Block[] = [];
  let current = null as Block | null;
  let bold = 0;
  let italic = 0;
  let href: string | undefined;
  const lists: { ordered: boolean; n: number }[] = [];
  let inQuote = false;

  const open = (tag: Block['tag']) => {
    const list = lists[lists.length - 1];
    current = { tag: inQuote && tag === 'p' ? 'quote' : tag, spans: [] };
    if (tag === 'oli' && list) {
      current.index = ++list.n;
    }
    blocks.push(current);
  };
  const close = () => { current = null; };

  const re = /<\/?([a-z0-9]+)([^>]*)>|([^<]+)/gi;
  let m: RegExpExecArray | null;
  while ((m = re.exec(html)) !== null) {
    if (m[3] !== undefined) {
      const text = decode(m[3].replace(/\s+/g, ' '));
      if (! text.trim() && ! current) continue;
      if (! current) open(inQuote ? 'quote' : 'p');
      (current as unknown as Block).spans.push({ text, bold: bold > 0, italic: italic > 0, href });
      continue;
    }
    const closing = m[0][1] === '/';
    const tag = m[1].toLowerCase();
    switch (tag) {
      case 'p': case 'div':
        closing ? close() : open('p');
        break;
      case 'h1': case 'h2': closing ? close() : open('h2'); break;
      case 'h3': closing ? close() : open('h3'); break;
      case 'h4': case 'h5': case 'h6': closing ? close() : open('h4'); break;
      case 'ul': case 'ol':
        if (closing) lists.pop(); else lists.push({ ordered: tag === 'ol', n: 0 });
        close();
        break;
      case 'li':
        closing ? close() : open(lists[lists.length - 1]?.ordered ? 'oli' : 'li');
        break;
      case 'blockquote': inQuote = ! closing; close(); break;
      case 'hr': blocks.push({ tag: 'hr', spans: [] }); close(); break;
      case 'br': if (current) (current as Block).spans.push({ text: '\n' }); break;
      case 'strong': case 'b': bold += closing ? -1 : 1; break;
      case 'em': case 'i': italic += closing ? -1 : 1; break;
      case 'a': {
        const h = /href\s*=\s*"([^"]*)"/i.exec(m[2]);
        href = closing ? undefined : h ? decode(h[1]) : undefined;
        break;
      }
      default: break;
    }
  }

  return blocks
    .map((b) => ({ ...b, spans: b.spans.map((s, i) => (i === 0 ? { ...s, text: s.text.replace(/^\s+/, '') } : s)) }))
    .filter((b) => b.tag === 'hr' || b.spans.some((s) => s.text.trim() !== ''));
}

/**
 * "…/bible/john/3#v16" (the website's scripture links) => reader params.
 */
export function bibleTarget(href: string): { book: string; chapter: number; verse?: number } | null {
  const m = /\/bible\/([a-z0-9-]+)\/(\d+)(?:#v(\d+))?/i.exec(href);
  return m ? { book: m[1], chapter: Number(m[2]), verse: m[3] ? Number(m[3]) : undefined } : null;
}

export default function Html({ html, serif = true }: { html: string; serif?: boolean }) {
  const { c, readScale } = useTheme();
  const nav = useNavigation<any>();
  const base = { color: c.text, fontSize: 16.5 * readScale, lineHeight: 27 * readScale, fontFamily: serif ? t.serif : undefined };
  // Scripture references open in the app's Bible reader; other links in the browser.
  const open = (href: string) => {
    const bible = bibleTarget(href);
    if (bible) nav.navigate('BibleChapter', bible);
    else Linking.openURL(href);
  };

  const renderSpans = (spans: Span[]) => spans.map((s, i) => (
    <Text
      key={i}
      onPress={s.href ? () => open(s.href!) : undefined}
      style={[s.bold && { fontWeight: '700' }, s.italic && { fontStyle: 'italic' }, s.href ? { color: c.brand, textDecorationLine: 'underline' } : null]}
    >
      {s.text}
    </Text>
  ));

  return (
    <View style={{ gap: 12 }}>
      {parseHtml(html).map((b, i) => {
        switch (b.tag) {
          case 'h2': return <Text key={i} style={{ color: c.text, fontSize: 21 * readScale, fontWeight: '800', marginTop: 8 }}>{renderSpans(b.spans)}</Text>;
          case 'h3': return <Text key={i} style={{ color: c.brandInk, fontSize: 18 * readScale, fontWeight: '700', marginTop: 6 }}>{renderSpans(b.spans)}</Text>;
          case 'h4': return <Text key={i} style={{ color: c.text, fontSize: 16 * readScale, fontWeight: '700' }}>{renderSpans(b.spans)}</Text>;
          case 'hr': return <View key={i} style={{ height: 1, backgroundColor: c.border, marginVertical: 6 }} />;
          case 'quote':
            return (
              <View key={i} style={{ borderLeftWidth: 3, borderLeftColor: c.accent, backgroundColor: c.accentSoft, paddingVertical: 10, paddingHorizontal: 14, borderRadius: 8 }}>
                <Text style={[base, { fontStyle: 'italic' }]}>{renderSpans(b.spans)}</Text>
              </View>
            );
          case 'li': case 'oli':
            return (
              <View key={i} style={{ flexDirection: 'row', gap: 10, paddingLeft: 4 }}>
                <Text style={[base, { color: c.brand, fontWeight: '700', minWidth: 16 }]}>{b.tag === 'oli' ? `${b.index}.` : '•'}</Text>
                <Text style={[base, { flex: 1 }]}>{renderSpans(b.spans)}</Text>
              </View>
            );
          default: return <Text key={i} style={base}>{renderSpans(b.spans)}</Text>;
        }
      })}
    </View>
  );
}
