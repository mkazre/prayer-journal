import React, { useEffect, useRef, useState } from 'react';
import { FlatList, Pressable, Share, StyleSheet, Text, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Haptics from 'expo-haptics';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { api } from '../api/client';
import { Chapter } from '../api/types';
import { type } from '../theme/colors';
import Icon from '../components/Icon';
import { AppHeader, ErrorState, HeaderAction, Loading } from '../components/ui';
import { bookSlug, getBooks, LAST_READ_KEY } from './BibleScreen';

const COLORS = ['yellow', 'green', 'blue', 'pink', 'purple'] as const;

export default function BibleChapterScreen({ route, navigation }: any) {
  const { book, chapter, verse: focusVerse } = route.params as { book: string; chapter: number; verse?: number };
  const { c, readScale, setTextScale, textScale } = useTheme();
  const insets = useSafeAreaInsets();
  const { data, error, loading, refresh, setData } = useApi<Chapter>(`/bible/${book}/${chapter}`, { refetchOnFocus: false });
  const [selected, setSelected] = useState<number[]>([]);
  const listRef = useRef<FlatList>(null);

  useEffect(() => {
    setSelected([]);
    if (data) {
      AsyncStorage.setItem(LAST_READ_KEY, JSON.stringify({ book: data.book.slug, name: data.book.name, chapter })).catch(() => undefined);
      if (focusVerse) {
        const idx = data.verses.findIndex((v) => v.verse === focusVerse);
        if (idx > 0) setTimeout(() => listRef.current?.scrollToIndex({ index: idx, viewPosition: 0.2, animated: true }), 300);
        setSelected(idx >= 0 ? [data.verses[idx].id] : []);
      }
    }
  }, [data?.book.id, chapter]);

  async function move(dir: -1 | 1) {
    if (! data) return;
    const books = await getBooks();
    const i = books.findIndex((b) => b.id === data.book.id);
    if (dir === -1) {
      if (chapter > 1) return navigation.setParams({ chapter: chapter - 1, verse: undefined });
      const prev = books[i - 1];
      if (prev) navigation.setParams({ book: bookSlug(prev), chapter: prev.chapter_count, verse: undefined });
    } else {
      if (chapter < data.book.chapter_count) return navigation.setParams({ chapter: chapter + 1, verse: undefined });
      const next = books[i + 1];
      if (next) navigation.setParams({ book: bookSlug(next), chapter: 1, verse: undefined });
    }
  }

  function toggle(id: number) {
    Haptics.selectionAsync().catch(() => undefined);
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
  }

  const picked = (data?.verses ?? []).filter((v) => selected.includes(v.id));
  const refText = data && picked.length ? `${data.book.name} ${chapter}:${picked.length > 1 ? `${picked[0].verse}-${picked[picked.length - 1].verse}` : picked[0].verse}` : '';

  async function highlight(color: string | null) {
    if (! data) return;
    const hl = { ...data.highlights };
    await Promise.all(picked.map((v) => {
      if (color) hl[v.id] = color; else delete hl[v.id];
      return api.post(`/bible/verses/${v.id}/highlight`, { color: color ?? '' }).catch(() => undefined);
    }));
    setData({ ...data, highlights: hl });
    setSelected([]);
  }

  async function bookmark() {
    if (! data) return;
    let marks = [...data.bookmarks];
    for (const v of picked) {
      const res = await api.post<{ bookmarked: boolean }>(`/bible/verses/${v.id}/bookmark`).catch(() => null);
      if (res) marks = res.bookmarked ? [...marks, v.id] : marks.filter((x) => x !== v.id);
    }
    setData({ ...data, bookmarks: marks });
    setSelected([]);
  }

  function share() {
    Share.share({ message: `“${picked.map((v) => v.text).join(' ')}” — ${refText} (KJV)` });
  }

  function journal() {
    navigation.navigate('JournalEntry', { kind: 'meditation', scripture: refText, body: '' });
    setSelected([]);
  }

  const sizes = ['sm', 'md', 'lg', 'xl'] as const;
  const right = (
    <HeaderAction icon="type" label="Text size" onPress={() => setTextScale(sizes[(sizes.indexOf(textScale) + 1) % sizes.length])} />
  );

  return (
    <View style={{ flex: 1, backgroundColor: c.bg }}>
      <AppHeader title={data ? `${data.book.name} ${chapter}` : 'Bible'} subtitle="KJV" back right={right} />
      {loading ? <Loading /> : error || ! data ? <View style={{ padding: 16 }}><ErrorState message={error ?? ''} onRetry={refresh} /></View> : (
        <FlatList
          ref={listRef}
          data={data.verses}
          keyExtractor={(v) => String(v.id)}
          contentContainerStyle={{ padding: 20, paddingBottom: 140 }}
          onScrollToIndexFailed={() => undefined}
          ListHeaderComponent={<Text style={{ color: c.text, fontSize: 30, fontWeight: '800', fontFamily: type.serif, marginBottom: 16 }}>{data.book.name} {chapter}</Text>}
          renderItem={({ item }) => {
            const hl = data.highlights[item.id];
            const isSel = selected.includes(item.id);
            return (
              <Pressable onPress={() => toggle(item.id)} style={[styles.verse, hl ? { backgroundColor: c.highlight[hl] } : null, isSel && { backgroundColor: c.brandSoft, borderColor: c.brand }]}>
                <Text style={{ color: c.text, fontFamily: type.serif, fontSize: 18 * readScale, lineHeight: 30 * readScale }}>
                  <Text style={{ color: c.brand, fontSize: 12 * readScale, fontWeight: '800' }}>{item.verse}  </Text>
                  {item.text}
                  {data.bookmarks.includes(item.id) ? <Text>  <Icon name="bookmark" size={13 * readScale} color={c.accent} /></Text> : null}
                </Text>
              </Pressable>
            );
          }}
          ListFooterComponent={
            <View style={styles.pager}>
              <Pressable onPress={() => move(-1)} disabled={! data.has_prev} style={[styles.pageBtn, { borderColor: c.border, opacity: data.has_prev ? 1 : 0.4 }]}>
                <Icon name="chevron-left" size={18} color={c.text} /><Text style={{ color: c.text, fontWeight: '700' }}>Previous</Text>
              </Pressable>
              <Pressable onPress={() => move(1)} disabled={! data.has_next} style={[styles.pageBtn, { borderColor: c.border, opacity: data.has_next ? 1 : 0.4 }]}>
                <Text style={{ color: c.text, fontWeight: '700' }}>Next</Text><Icon name="chevron-right" size={18} color={c.text} />
              </Pressable>
            </View>
          }
        />
      )}

      {picked.length ? (
        <View style={[styles.sheet, { backgroundColor: c.surface, borderColor: c.border, paddingBottom: insets.bottom + 12 }]}>
          <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
            <Text style={{ color: c.text, fontWeight: '800' }}>{refText}</Text>
            <Pressable onPress={() => setSelected([])} hitSlop={10}><Icon name="x" size={20} color={c.muted} /></Pressable>
          </View>
          <View style={{ flexDirection: 'row', gap: 10, alignItems: 'center' }}>
            {COLORS.map((col) => (
              <Pressable key={col} onPress={() => highlight(col)} style={[styles.swatch, { backgroundColor: c.highlight[col], borderColor: c.border }]} accessibilityLabel={`Highlight ${col}`} />
            ))}
            <Pressable onPress={() => highlight(null)} style={[styles.swatch, { borderColor: c.border, alignItems: 'center', justifyContent: 'center' }]} accessibilityLabel="Remove highlight">
              <Icon name="slash" size={16} color={c.muted} />
            </Pressable>
          </View>
          <View style={{ flexDirection: 'row', gap: 8 }}>
            <SheetBtn icon="bookmark" label="Bookmark" onPress={bookmark} />
            <SheetBtn icon="feather" label="Journal" onPress={journal} />
            <SheetBtn icon="share-2" label="Share" onPress={share} />
          </View>
        </View>
      ) : null}
    </View>
  );
}

function SheetBtn({ icon, label, onPress }: { icon: any; label: string; onPress: () => void }) {
  const { c } = useTheme();
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.sheetBtn, { backgroundColor: c.surface2, opacity: pressed ? 0.7 : 1 }]}>
      <Icon name={icon} size={18} color={c.brand} />
      <Text style={{ color: c.text, fontSize: 12, fontWeight: '700' }}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  verse: { paddingVertical: 4, paddingHorizontal: 6, marginHorizontal: -6, borderRadius: 8, borderWidth: 1, borderColor: 'transparent', marginBottom: 4 },
  pager: { flexDirection: 'row', gap: 10, marginTop: 24 },
  pageBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, height: 48, borderRadius: 12, borderWidth: 1 },
  sheet: { position: 'absolute', left: 0, right: 0, bottom: 0, borderTopWidth: 1, borderTopLeftRadius: 22, borderTopRightRadius: 22, padding: 18, gap: 14, elevation: 12, shadowColor: '#000', shadowOpacity: 0.15, shadowRadius: 16, shadowOffset: { width: 0, height: -4 } },
  swatch: { width: 36, height: 36, borderRadius: 18, borderWidth: 1 },
  sheetBtn: { flex: 1, alignItems: 'center', gap: 4, paddingVertical: 12, borderRadius: 12 },
});
