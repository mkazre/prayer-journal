import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useTheme } from '../context/ThemeContext';
import { api } from '../api/client';
import { BibleVerse } from '../api/types';
import { type } from '../theme/colors';
import Icon from '../components/Icon';
import { Card, EmptyState, Field, Screen } from '../components/ui';
import { bookSlug, getBooks } from './BibleScreen';

type Passage = { reference: string; book: string; chapter: number; verses: { verse: number; text: string }[] };

export default function BibleSearchScreen({ route, navigation }: any) {
  const { c } = useTheme();
  const [q, setQ] = useState<string>(route.params?.q ?? '');
  const [passage, setPassage] = useState<Passage | null>(null);
  const [results, setResults] = useState<{ total: number; verses: (BibleVerse & { book_name: string; book_position: number; chapter: number })[] } | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    const term = q.trim();
    if (term.length < 2) {
      setPassage(null);
      setResults(null);
      return;
    }
    const t = setTimeout(async () => {
      setBusy(true);
      const [p, r] = await Promise.all([
        api.get<Passage>(`/bible/passage?ref=${encodeURIComponent(term)}`).catch(() => null),
        api.get<any>(`/bible/search?q=${encodeURIComponent(term)}`).catch(() => null),
      ]);
      setPassage(p && p.verses.length ? p : null);
      setResults(r);
      setBusy(false);
    }, 400);
    return () => clearTimeout(t);
  }, [q]);

  async function open(position: number, chapter: number, verse: number) {
    const books = await getBooks();
    const b = books.find((x) => x.position === position);
    if (b) navigation.navigate('BibleChapter', { book: bookSlug(b), chapter, verse });
  }

  const words = q.trim().replace(/"/g, '').split(/\s+/).filter((w) => w.length > 1);
  const mark = (text: string) => {
    if (! words.length) return text;
    const re = new RegExp(`(${words.map((w) => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})`, 'gi');
    return text.split(re).map((part, i) => (i % 2 === 1 ? <Text key={i} style={{ backgroundColor: c.accentSoft, fontWeight: '700' }}>{part}</Text> : part));
  };

  return (
    <Screen title="Search the Bible" back>
      <Field icon="search" value={q} onChangeText={setQ} placeholder="Words, a phrase in quotes, or John 3:16" autoFocus returnKeyType="search" autoCorrect={false} />
      {busy ? <ActivityIndicator color={c.brand} /> : null}

      {passage ? (
        <Card style={{ backgroundColor: c.brandSoft, borderColor: c.brandSoft }} onPress={() => navigation.navigate('BibleChapter', { book: passage.book, chapter: passage.chapter, verse: passage.verses[0]?.verse })}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
            <Icon name="book-open" size={16} color={c.brand} />
            <Text style={{ color: c.brand, fontWeight: '800', flex: 1 }}>{passage.reference}</Text>
            <Icon name="arrow-right" size={16} color={c.brand} />
          </View>
          <Text style={{ color: c.text, fontFamily: type.serif, fontSize: 16, lineHeight: 25, marginTop: 8 }} numberOfLines={6}>
            {passage.verses.map((v) => `${v.verse} ${v.text}`).join(' ')}
          </Text>
        </Card>
      ) : null}

      {results ? <Text style={{ color: c.muted, fontSize: 13 }}>{results.total.toLocaleString()} verses found{results.total > results.verses.length ? ` · showing ${results.verses.length}` : ''}</Text> : null}
      {results && ! results.total && ! passage && ! busy ? <EmptyState icon="search" title="No verses found" body="Try fewer words, or put an exact phrase in quotes." /> : null}

      {results?.verses.map((v) => (
        <Pressable key={v.id} onPress={() => open(v.book_position, v.chapter, v.verse)} style={({ pressed }) => [{ backgroundColor: c.surface, borderColor: c.border, borderWidth: 1, borderRadius: 14, padding: 14, opacity: pressed ? 0.8 : 1 }]}>
          <Text style={{ color: c.brand, fontWeight: '800', fontSize: 13, marginBottom: 4 }}>{v.book_name} {v.chapter}:{v.verse}</Text>
          <Text style={{ color: c.text, fontFamily: type.serif, fontSize: 15.5, lineHeight: 23 }}>{mark(v.text)}</Text>
        </Pressable>
      ))}

      {! q ? (
        <View style={{ gap: 8 }}>
          <Text style={[type.tiny, { color: c.muted }]}>Try</Text>
          {['Psalm 23', 'Philippians 4:6-7', '"be anxious for nothing"', 'faith hope love'].map((s) => (
            <Pressable key={s} onPress={() => setQ(s)} style={{ flexDirection: 'row', gap: 10, alignItems: 'center', paddingVertical: 8 }}>
              <Icon name="corner-down-right" size={16} color={c.muted} /><Text style={{ color: c.text2, fontSize: 15 }}>{s}</Text>
            </Pressable>
          ))}
        </View>
      ) : null}
    </Screen>
  );
}
