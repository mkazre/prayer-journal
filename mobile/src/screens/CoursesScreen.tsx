import React, { useMemo, useState } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { Course, MyCourse } from '../api/types';
import { money } from '../api/client';
import { type } from '../theme/colors';
import Icon from '../components/Icon';
import { Badge, Card, Chip, Chips, EmptyState, ErrorState, Field, HeaderAction, Loading, Progress, Screen, SectionHeader } from '../components/ui';

export function hours(mins: number): string {
  if (mins < 60) return `${mins} min`;
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  return m ? `${h}h ${m}m` : `${h}h`;
}

export default function CoursesScreen({ navigation }: any) {
  const { c } = useTheme();
  const [tab, setTab] = useState<'mine' | 'all'>('mine');
  const [q, setQ] = useState('');
  const all = useApi<Course[]>('/courses');
  const mine = useApi<MyCourse[]>('/my-courses');

  const filtered = useMemo(() => (all.data ?? []).filter((x) => ! q || `${x.title} ${x.subtitle ?? ''}`.toLowerCase().includes(q.toLowerCase())), [all.data, q]);
  const right = <HeaderAction icon="user" label="Profile" onPress={() => navigation.navigate('Profile')} />;

  const renderCourse = (x: Course) => (
      <Card key={x.id} style={{ padding: 0, overflow: 'hidden' }} onPress={() => navigation.navigate('CourseDetail', { slug: x.slug })}>
        {x.thumbnail_url ? <Image source={{ uri: x.thumbnail_url }} style={styles.cover} /> : <View style={[styles.cover, { backgroundColor: c.brandSoft, alignItems: 'center', justifyContent: 'center' }]}><Icon name="book-open" size={36} color={c.brand} /></View>}
        <View style={{ padding: 16, gap: 6 }}>
          {x.category ? <Text style={[type.tiny, { color: c.brand }]}>{x.category}</Text> : null}
          <Text style={[type.heading, { color: c.text }]}>{x.title}</Text>
          {x.subtitle ? <Text style={[type.small, { color: c.text2 }]} numberOfLines={2}>{x.subtitle}</Text> : null}
          <View style={styles.meta}>
            <Meta icon="list" text={`${x.lesson_count} lessons`} />
            {x.total_minutes ? <Meta icon="clock" text={hours(x.total_minutes)} /> : null}
            {x.student_count ? <Meta icon="users" text={`${x.student_count}`} /> : null}
            {x.rating ? <Meta icon="star" text={x.rating.toFixed(1)} /> : null}
            <Text style={{ marginLeft: 'auto', fontWeight: '800', color: c.text }}>{x.access_mode === 'subscription' ? 'Members' : x.price_cents > 0 ? money(x.price_cents, x.currency) : 'Free'}</Text>
          </View>
        </View>
      </Card>
  );

  if (all.loading || mine.loading) return <Screen title="Learn" right={right}><Loading /></Screen>;
  if (all.error) return <Screen title="Learn" right={right}><ErrorState message={all.error} onRetry={all.refresh} /></Screen>;

  const myCourses = mine.data ?? [];
  const showMine = tab === 'mine' && myCourses.length > 0;
  const others = (all.data ?? []).filter((x) => ! myCourses.some((m) => m.course_id === x.id));

  return (
    <Screen title="Learn" subtitle="Courses, devotions and certificates" right={right} refreshing={all.refreshing} onRefresh={() => { all.refresh(); mine.refresh(); }}>
      <Chips>
        <Chip label={`My learning${myCourses.length ? ` (${myCourses.length})` : ''}`} icon="bookmark" active={tab === 'mine'} onPress={() => setTab('mine')} />
        <Chip label="All courses" icon="grid" active={tab === 'all' || ! myCourses.length} onPress={() => setTab('all')} />
      </Chips>

      {showMine ? (
        myCourses.map((m) => (
          <Card key={m.course_id} onPress={() => navigation.navigate('CourseDetail', { slug: m.slug })}>
            <View style={{ flexDirection: 'row', gap: 14 }}>
              <Thumb uri={m.thumbnail_url} />
              <View style={{ flex: 1, gap: 6 }}>
                <Text style={[type.heading, { color: c.text }]} numberOfLines={2}>{m.title}</Text>
                {m.status === 'completed' ? <Badge label="Completed" tone="success" icon="award" /> : <Text style={[type.small, { color: c.muted }]} numberOfLines={1}>{m.last_lesson_title ?? 'Not started yet'}</Text>}
                <Progress value={m.progress} />
                <Text style={[type.small, { color: c.muted }]}>{m.progress}% complete</Text>
              </View>
            </View>
          </Card>
        ))
      ) : (
        <>
          <Field icon="search" placeholder="Search courses" value={q} onChangeText={setQ} returnKeyType="search" />
          {filtered.length === 0 ? <EmptyState icon="search" title="No courses found" body="Try a different word." /> : null}
          {filtered.map(renderCourse)}
        </>
      )}
      {showMine && others.length ? (
        <>
          <SectionHeader title="Discover more" icon="compass" action="Browse all" onAction={() => setTab('all')} />
          {others.map(renderCourse)}
        </>
      ) : null}
    </Screen>
  );
}

function Thumb({ uri }: { uri: string | null }) {
  const { c } = useTheme();
  return uri ? <Image source={{ uri }} style={styles.thumb} /> : <View style={[styles.thumb, { backgroundColor: c.brandSoft, alignItems: 'center', justifyContent: 'center' }]}><Icon name="book" size={24} color={c.brand} /></View>;
}

function Meta({ icon, text }: { icon: any; text: string }) {
  const { c } = useTheme();
  return <View style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}><Icon name={icon} size={13} color={c.muted} /><Text style={{ color: c.muted, fontSize: 12 }}>{text}</Text></View>;
}

const styles = StyleSheet.create({
  cover: { width: '100%', aspectRatio: 16 / 9 },
  thumb: { width: 72, height: 72, borderRadius: 12 },
  meta: { flexDirection: 'row', alignItems: 'center', gap: 12, marginTop: 6, flexWrap: 'wrap' },
});
