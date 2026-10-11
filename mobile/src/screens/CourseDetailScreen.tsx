import React, { useState } from 'react';
import { Alert, Image, Pressable, StyleSheet, Text, View } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { useTheme } from '../context/ThemeContext';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../hooks/useApi';
import { api, ApiError, money } from '../api/client';
import { CourseDetail, CurriculumItem } from '../api/types';
import { type } from '../theme/colors';
import Icon, { IconName } from '../components/Icon';
import Html from '../components/Html';
import { Button, Card, ErrorState, Loading, Progress, Screen, SectionHeader } from '../components/ui';
import { hours } from './CoursesScreen';

const ITEM_ICONS: Record<string, IconName> = { video: 'play-circle', audio: 'headphones', text: 'file-text', devotion: 'sunrise', quiz: 'check-square', assignment: 'edit', download: 'download' };

export default function CourseDetailScreen({ route, navigation }: any) {
  const { slug } = route.params;
  const { c } = useTheme();
  const { config } = useAuth();
  const { data, error, loading, refreshing, refresh, reload } = useApi<CourseDetail>(`/courses/${slug}`);
  const [open, setOpen] = useState<Record<number, boolean>>({ 0: true });
  const [busy, setBusy] = useState(false);

  if (loading) return <Screen title="Course" back><Loading /></Screen>;
  if (error || ! data) return <Screen title="Course" back><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  async function enroll() {
    setBusy(true);
    try {
      await api.post(`/courses/${slug}/enroll`);
      await reload();
    } catch (e: any) {
      if (e instanceof ApiError && e.status === 402) {
        Alert.alert('Purchase on the website', 'This course is paid or part of a membership. Open it on the website to complete checkout?', [
          { text: 'Not now', style: 'cancel' },
          { text: 'Open', onPress: () => WebBrowser.openBrowserAsync(`${config?.web_url ?? ''}courses/${slug}`) },
        ]);
      } else {
        Alert.alert('Could not enroll', e.message);
      }
    } finally {
      setBusy(false);
    }
  }

  function openItem(item: CurriculumItem) {
    if (item.locked) {
      Alert.alert('Locked', item.reason ?? 'Enroll to unlock this step.');
      return;
    }
    if (item.kind === 'lesson') navigation.navigate('Lesson', { slug, lessonId: item.id });
    else if (item.kind === 'quiz') navigation.navigate('Quiz', { slug, quizId: item.id });
    else WebBrowser.openBrowserAsync(`${config?.web_url ?? ''}study/${slug}/assignment/${item.id}`);
  }

  const items = data.sections.flatMap((s) => s.items);
  const next = items.find((i) => ! i.done && ! i.locked && i.kind === 'lesson');

  return (
    <Screen title={data.title} subtitle={data.author_name ? `with ${data.author_name}` : undefined} back refreshing={refreshing} onRefresh={refresh}>
      {data.thumbnail_url ? <Image source={{ uri: data.thumbnail_url }} style={styles.cover} /> : null}
      <View style={{ gap: 8 }}>
        {data.subtitle ? <Text style={[type.body, { color: c.text2, fontSize: 16 }]}>{data.subtitle}</Text> : null}
        <View style={styles.facts}>
          <Fact icon="list" text={`${data.lesson_count} lessons`} />
          {data.total_minutes ? <Fact icon="clock" text={hours(data.total_minutes)} /> : null}
          {data.level ? <Fact icon="bar-chart-2" text={data.level === 'all' ? 'All levels' : data.level.replace(/^\w/, (m) => m.toUpperCase())} /> : null}
          <Fact icon="award" text="Certificate" />
        </View>
      </View>

      {data.enrolled ? (
        <Card>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 }}>
            <Text style={[type.heading, { color: c.text }]}>Your progress</Text>
            <Text style={{ color: c.brand, fontWeight: '800' }}>{data.progress}%</Text>
          </View>
          <Progress value={data.progress} height={8} />
          {next ? <Button title={data.progress > 0 ? 'Continue' : 'Start course'} icon="play" style={{ marginTop: 14 }} onPress={() => openItem(next)} /> : null}
        </Card>
      ) : (
        <Button
          title={data.access_mode === 'subscription' ? 'Join with membership' : data.price_cents > 0 ? `Buy for ${money(data.price_cents, data.currency)}` : 'Enroll for free'}
          icon="unlock" variant="accent" loading={busy} onPress={enroll}
        />
      )}

      {data.what_you_learn.length ? (
        <Card>
          <SectionHeader title="What you’ll learn" icon="target" />
          <View style={{ gap: 10, marginTop: 14 }}>
            {data.what_you_learn.map((w) => (
              <View key={w} style={{ flexDirection: 'row', gap: 10 }}>
                <Icon name="check" size={18} color={c.success} />
                <Text style={[type.body, { color: c.text2, flex: 1 }]}>{w}</Text>
              </View>
            ))}
          </View>
        </Card>
      ) : null}

      <SectionHeader title="Course content" icon="layers" />
      {data.sections.map((s, si) => (
        <View key={`${s.id}-${si}`} style={[styles.section, { backgroundColor: c.surface, borderColor: c.border }]}>
          <Pressable onPress={() => setOpen((o) => ({ ...o, [si]: ! o[si] }))} style={styles.sectionHead}>
            <View style={{ flex: 1 }}>
              <Text style={{ color: c.text, fontWeight: '700', fontSize: 15 }}>{s.title}</Text>
              <Text style={{ color: c.muted, fontSize: 12, marginTop: 2 }}>{data.enrolled ? `${s.done_count}/${s.items.length} done` : `${s.items.length} steps`}</Text>
            </View>
            <Icon name={open[si] ? 'chevron-up' : 'chevron-down'} size={20} color={c.muted} />
          </Pressable>
          {open[si] ? s.items.map((item) => (
            <Pressable key={`${item.kind}-${item.id}`} onPress={() => openItem(item)} style={({ pressed }) => [styles.item, { borderTopColor: c.border, opacity: pressed ? 0.7 : 1 }]}>
              <View style={[styles.itemIcon, { backgroundColor: item.done ? c.successSoft : c.surface2 }]}>
                <Icon name={item.done ? 'check' : ITEM_ICONS[item.kind === 'lesson' ? item.type : item.kind] ?? 'file-text'} size={15} color={item.done ? c.success : c.text2} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ color: item.locked ? c.muted : c.text, fontSize: 14 }} numberOfLines={2}>{item.title}</Text>
                {item.locked && item.reason ? <Text style={{ color: c.muted, fontSize: 11, marginTop: 2 }}>{item.reason}</Text> : null}
              </View>
              {item.is_preview && ! data.enrolled ? <Text style={{ color: c.brand, fontSize: 11, fontWeight: '700' }}>PREVIEW</Text> : null}
              <Icon name={item.locked ? 'lock' : 'chevron-right'} size={16} color={c.muted} />
            </Pressable>
          )) : null}
        </View>
      ))}

      {data.description ? (
        <Card>
          <SectionHeader title="About this course" icon="info" />
          <View style={{ marginTop: 12 }}><Html html={data.description} serif={false} /></View>
        </Card>
      ) : null}

      {data.author_name ? (
        <Card>
          <SectionHeader title="Your guide" icon="user" />
          <Text style={[type.heading, { color: c.text, marginTop: 12 }]}>{data.author_name}</Text>
          {data.author_bio ? <Text style={[type.body, { color: c.text2, marginTop: 6 }]}>{data.author_bio}</Text> : null}
        </Card>
      ) : null}
    </Screen>
  );
}

function Fact({ icon, text }: { icon: IconName; text: string }) {
  const { c } = useTheme();
  return (
    <View style={[styles.fact, { backgroundColor: c.surface, borderColor: c.border }]}>
      <Icon name={icon} size={14} color={c.brand} />
      <Text style={{ color: c.text2, fontSize: 12, fontWeight: '600' }}>{text}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  cover: { width: '100%', aspectRatio: 16 / 9, borderRadius: 16 },
  facts: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  fact: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, borderWidth: 1 },
  section: { borderWidth: 1, borderRadius: 16, overflow: 'hidden' },
  sectionHead: { flexDirection: 'row', alignItems: 'center', padding: 14, gap: 10 },
  item: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 12, paddingHorizontal: 14, borderTopWidth: StyleSheet.hairlineWidth },
  itemIcon: { width: 30, height: 30, borderRadius: 15, alignItems: 'center', justifyContent: 'center' },
});
