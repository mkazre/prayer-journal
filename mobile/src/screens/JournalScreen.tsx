import React, { useEffect, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import * as Haptics from 'expo-haptics';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { api } from '../api/client';
import { JournalEntry, JournalStats } from '../api/types';
import { type } from '../theme/colors';
import Icon, { IconName } from '../components/Icon';
import { Badge, Chip, Chips, EmptyState, ErrorState, Field, HeaderAction, Loading, Screen } from '../components/ui';

export const KINDS: Record<string, { label: string; icon: IconName }> = {
  prayer: { label: 'Prayer', icon: 'heart' },
  meditation: { label: 'Meditation', icon: 'feather' },
  gratitude: { label: 'Gratitude', icon: 'sunrise' },
  note: { label: 'Note', icon: 'edit-3' },
};

function niceDate(d: string): string {
  const date = new Date(`${d.slice(0, 10)}T12:00:00`);
  return date.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

export default function JournalScreen({ navigation }: any) {
  const { c } = useTheme();
  const [kind, setKind] = useState('');
  const [status, setStatus] = useState('');
  const [q, setQ] = useState('');
  const [debounced, setDebounced] = useState('');
  useEffect(() => { const t = setTimeout(() => setDebounced(q), 350); return () => clearTimeout(t); }, [q]);

  const path = `/journal?kind=${kind}&status=${status}&q=${encodeURIComponent(debounced)}&limit=100`;
  const { data, error, loading, refreshing, refresh, setData } = useApi<{ stats: JournalStats; entries: JournalEntry[] }>(path);

  async function toggleAnswered(e: JournalEntry) {
    if (! data) return;
    const updated = await api.post<JournalEntry>(`/journal/${e.id}/answered`).catch(() => null);
    if (! updated) return;
    if (updated.is_answered) Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success).catch(() => undefined);
    setData({ ...data, entries: data.entries.map((x) => (x.id === e.id ? { ...x, ...updated } : x)), stats: { ...data.stats, answered: data.stats.answered + (updated.is_answered ? 1 : -1) } });
  }

  const right = <HeaderAction icon="plus" label="New entry" onPress={() => navigation.navigate('JournalEntry', { kind: kind || 'prayer' })} />;

  return (
    <Screen title="My Journal" subtitle="Private to you" right={right} refreshing={refreshing} onRefresh={refresh}>
      {data ? (
        <View style={styles.stats}>
          {[['Entries', data.stats.total, c.brand], ['Prayers', data.stats.prayers, c.text], ['Answered', data.stats.answered, c.success], ['Gratitude', data.stats.gratitude, c.accent]].map(([l, v, col]) => (
            <View key={String(l)} style={[styles.stat, { backgroundColor: c.surface, borderColor: c.border }]}>
              <Text style={{ fontSize: 20, fontWeight: '800', color: col as string }}>{v as number}</Text>
              <Text style={{ fontSize: 11, color: c.muted, fontWeight: '600' }}>{l as string}</Text>
            </View>
          ))}
        </View>
      ) : null}

      <Field icon="search" placeholder="Search your journal" value={q} onChangeText={setQ} returnKeyType="search" />
      <Chips>
        <Chip label="All" active={! kind && ! status} onPress={() => { setKind(''); setStatus(''); }} />
        <Chip label="Waiting" icon="clock" active={status === 'open'} onPress={() => { setStatus(status === 'open' ? '' : 'open'); setKind(''); }} />
        <Chip label="Answered" icon="check-circle" active={status === 'answered'} onPress={() => setStatus(status === 'answered' ? '' : 'answered')} />
        {Object.entries(KINDS).map(([k, v]) => <Chip key={k} label={v.label} icon={v.icon} active={kind === k} onPress={() => { setKind(kind === k ? '' : k); if (status === 'open') setStatus(''); }} />)}
      </Chips>

      {loading ? <Loading /> : error ? <ErrorState message={error} onRetry={refresh} /> : data && data.entries.length === 0 ? (
        <EmptyState icon="feather" title={q || kind || status ? 'Nothing matches' : 'Your journal is ready'} body="Write a prayer, a meditation on Scripture, or something you are grateful for." action="Write first entry" onAction={() => navigation.navigate('JournalEntry', { kind: kind || 'prayer' })} />
      ) : data?.entries.map((e) => {
        const k = KINDS[e.kind] ?? KINDS.note;
        return (
          <Pressable key={e.id} onPress={() => navigation.navigate('JournalEntry', { entry: e })} style={({ pressed }) => [styles.entry, { backgroundColor: c.surface, borderColor: e.is_answered ? c.success : c.border, opacity: pressed ? 0.85 : 1 }]}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
              <Icon name={k.icon} size={15} color={c.brand} />
              <Text style={{ color: c.brand, fontWeight: '700', fontSize: 12 }}>{k.label}</Text>
              <Text style={{ color: c.muted, fontSize: 12, marginLeft: 'auto' }}>{niceDate(e.entry_date)}</Text>
            </View>
            {e.title ? <Text style={[type.heading, { color: c.text }]} numberOfLines={1}>{e.title}</Text> : null}
            <Text style={{ color: c.text2, fontFamily: type.serif, fontSize: 15, lineHeight: 23 }} numberOfLines={4}>{e.body}</Text>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
              {e.scripture ? <Badge label={e.scripture} icon="book-open" tone="muted" /> : null}
              {e.course_title ? <Badge label={e.lesson_title ?? e.course_title} icon="award" tone="muted" /> : null}
              {e.kind === 'prayer' ? (
                <Pressable onPress={() => toggleAnswered(e)} hitSlop={8} style={[styles.answer, { backgroundColor: e.is_answered ? c.successSoft : c.surface2 }]}>
                  <Icon name="check-circle" size={14} color={e.is_answered ? c.success : c.muted} />
                  <Text style={{ fontSize: 12, fontWeight: '700', color: e.is_answered ? c.success : c.muted }}>{e.is_answered ? 'Answered' : 'Mark answered'}</Text>
                </Pressable>
              ) : null}
            </View>
          </Pressable>
        );
      })}
    </Screen>
  );
}

const styles = StyleSheet.create({
  stats: { flexDirection: 'row', gap: 8 },
  stat: { flex: 1, alignItems: 'center', paddingVertical: 12, borderRadius: 14, borderWidth: 1 },
  entry: { borderWidth: 1, borderRadius: 16, padding: 16, gap: 8 },
  answer: { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 10, paddingVertical: 5, borderRadius: 999, marginLeft: 'auto' },
});
