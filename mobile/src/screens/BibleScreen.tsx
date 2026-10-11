import React, { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useFocusEffect } from '@react-navigation/native';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { api } from '../api/client';
import { BibleBook, Verse } from '../api/types';
import { type } from '../theme/colors';
import Icon from '../components/Icon';
import { Card, Chip, Chips, ErrorState, HeaderAction, IconTile, Loading, Screen } from '../components/ui';

export const LAST_READ_KEY = 'pj_last_read';

let booksCache: BibleBook[] | null = null;
export async function getBooks(): Promise<BibleBook[]> {
  if (! booksCache) booksCache = await api.get<BibleBook[]>('/bible/books');
  return booksCache;
}

export function bookSlug(b: BibleBook): string {
  return b.name.toLowerCase().replace(/[^a-z0-9]+/g, '-');
}

export default function BibleScreen({ navigation }: any) {
  const { c } = useTheme();
  const books = useApi<BibleBook[]>('/bible/books', { refetchOnFocus: false });
  const votd = useApi<Verse>('/bible/verse-of-the-day', { refetchOnFocus: false });
  const [testament, setTestament] = useState<'OT' | 'NT'>('NT');
  const [expanded, setExpanded] = useState<number | null>(null);
  const [last, setLast] = useState<{ book: string; name: string; chapter: number } | null>(null);

  useFocusEffect(useCallback(() => {
    AsyncStorage.getItem(LAST_READ_KEY).then((v) => setLast(v ? JSON.parse(v) : null)).catch(() => undefined);
  }, []));

  const right = <HeaderAction icon="search" label="Search" onPress={() => navigation.navigate('BibleSearch')} />;
  if (books.loading) return <Screen title="Holy Bible" subtitle="King James Version" right={right}><Loading /></Screen>;
  if (books.error || ! books.data) return <Screen title="Holy Bible" right={right}><ErrorState message={books.error ?? ''} onRetry={books.refresh} /></Screen>;

  const list = books.data.filter((b) => b.testament === testament);

  return (
    <Screen title="Holy Bible" subtitle="King James Version" right={right}>
      <Pressable onPress={() => navigation.navigate('BibleSearch')} style={[styles.search, { backgroundColor: c.surface, borderColor: c.border }]}>
        <Icon name="search" size={18} color={c.muted} />
        <Text style={{ color: c.muted, fontSize: 15 }}>Search words or go to “John 3:16”</Text>
      </Pressable>

      {last ? (
        <Card onPress={() => navigation.navigate('BibleChapter', { book: last.book, chapter: last.chapter })}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 14 }}>
            <IconTile icon="bookmark" />
            <View style={{ flex: 1 }}>
              <Text style={[type.tiny, { color: c.muted }]}>Continue reading</Text>
              <Text style={[type.heading, { color: c.text }]}>{last.name} {last.chapter}</Text>
            </View>
            <Icon name="chevron-right" size={20} color={c.muted} />
          </View>
        </Card>
      ) : null}

      {votd.data ? (
        <Card style={{ backgroundColor: c.brandSoft, borderColor: c.brandSoft }} onPress={() => navigation.navigate('BibleChapter', { book: votd.data!.book, chapter: votd.data!.chapter, verse: votd.data!.verse })}>
          <Text style={[type.tiny, { color: c.brand }]}>Verse of the day</Text>
          <Text style={{ color: c.text, fontFamily: type.serif, fontSize: 17, lineHeight: 26, marginTop: 8 }}>“{votd.data.text}”</Text>
          <Text style={{ color: c.brandInk, fontWeight: '700', marginTop: 8 }}>{votd.data.reference}</Text>
        </Card>
      ) : null}

      <Chips>
        <Chip label="Old Testament" active={testament === 'OT'} onPress={() => { setTestament('OT'); setExpanded(null); }} />
        <Chip label="New Testament" active={testament === 'NT'} onPress={() => { setTestament('NT'); setExpanded(null); }} />
      </Chips>

      <View style={[styles.list, { backgroundColor: c.surface, borderColor: c.border }]}>
        {list.map((b, i) => (
          <View key={b.id} style={i ? { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: c.border } : undefined}>
            <Pressable
              onPress={() => (b.chapter_count === 1 ? navigation.navigate('BibleChapter', { book: bookSlug(b), chapter: 1 }) : setExpanded(expanded === b.id ? null : b.id))}
              style={styles.bookRow}
            >
              <Text style={{ color: c.muted, width: 26, fontSize: 12, fontWeight: '700' }}>{b.position}</Text>
              <Text style={{ color: c.text, fontSize: 16, fontWeight: '600', flex: 1 }}>{b.name}</Text>
              <Text style={{ color: c.muted, fontSize: 12 }}>{b.chapter_count} ch</Text>
              <Icon name={expanded === b.id ? 'chevron-up' : 'chevron-down'} size={18} color={c.muted} />
            </Pressable>
            {expanded === b.id ? (
              <View style={styles.grid}>
                {Array.from({ length: b.chapter_count }, (_, n) => n + 1).map((n) => (
                  <Pressable key={n} onPress={() => navigation.navigate('BibleChapter', { book: bookSlug(b), chapter: n })} style={({ pressed }) => [styles.cell, { backgroundColor: pressed ? c.brand : c.surface2 }]}>
                    <Text style={{ color: c.text, fontWeight: '700' }}>{n}</Text>
                  </Pressable>
                ))}
              </View>
            ) : null}
          </View>
        ))}
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  search: { flexDirection: 'row', alignItems: 'center', gap: 10, borderWidth: 1, borderRadius: 12, paddingHorizontal: 14, height: 50 },
  list: { borderWidth: 1, borderRadius: 16, overflow: 'hidden' },
  bookRow: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 14, paddingHorizontal: 14 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, paddingHorizontal: 14, paddingBottom: 14 },
  cell: { width: 48, height: 44, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
});
