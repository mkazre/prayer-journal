import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, ActivityIndicator } from 'react-native';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

type Verse = { id: number; verse: number; text: string };
type ChapterData = { book: { name: string; abbrev: string; chapter_count: number }; chapter: number; verses: Verse[] };

export default function BibleChapterScreen({ route, navigation }: any) {
  const { abbrev, chapter, name } = route.params;
  const { user } = useAuth();
  const [data, setData] = useState<ChapterData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    navigation.setOptions({ title: `${name} ${chapter}` });
    setLoading(true);
    api.get<ChapterData>(`/bible/${abbrev}/${chapter}`, !! user).then(setData).finally(() => setLoading(false));
  }, [abbrev, chapter]);

  async function bookmark(verseId: number) {
    if (! user) return;
    await api.post(`/bible/verses/${verseId}/bookmark`);
  }

  if (loading || ! data) {
    return <ActivityIndicator style={styles.loader} size="large" color={colors.purple} />;
  }

  return (
    <View style={styles.container}>
      <FlatList
        contentContainerStyle={{ padding: 20 }}
        data={data.verses}
        keyExtractor={(v) => String(v.id)}
        renderItem={({ item }) => (
          <TouchableOpacity onLongPress={() => bookmark(item.id)} style={styles.verseRow}>
            <Text style={styles.verseNum}>{item.verse}</Text>
            <Text style={styles.verseText}>{item.text}</Text>
          </TouchableOpacity>
        )}
      />
      <View style={styles.nav}>
        <TouchableOpacity
          disabled={chapter <= 1}
          onPress={() => navigation.setParams({ chapter: chapter - 1 })}
          style={[styles.navBtn, chapter <= 1 && styles.navBtnDisabled]}
        >
          <Text style={styles.navText}>← Prev</Text>
        </TouchableOpacity>
        <TouchableOpacity
          disabled={chapter >= data.book.chapter_count}
          onPress={() => navigation.setParams({ chapter: chapter + 1 })}
          style={[styles.navBtn, chapter >= data.book.chapter_count && styles.navBtnDisabled]}
        >
          <Text style={styles.navText}>Next →</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg },
  loader: { flex: 1, backgroundColor: colors.bg },
  verseRow: { flexDirection: 'row', marginBottom: 10 },
  verseNum: { color: colors.gold, fontWeight: '700', width: 24 },
  verseText: { flex: 1, color: colors.text, lineHeight: 22 },
  nav: { flexDirection: 'row', justifyContent: 'space-between', padding: 16, borderTopWidth: 1, borderTopColor: colors.border },
  navBtn: { backgroundColor: colors.purple, borderRadius: 8, paddingHorizontal: 16, paddingVertical: 10 },
  navBtnDisabled: { backgroundColor: colors.border },
  navText: { color: colors.white, fontWeight: '600' },
});
