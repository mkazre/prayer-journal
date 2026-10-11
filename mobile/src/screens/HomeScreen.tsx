import React from 'react';
import { Image, Pressable, Share, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { Dashboard } from '../api/types';
import { brand, type } from '../theme/colors';
import Icon, { IconName } from '../components/Icon';
import { Card, EmptyState, ErrorState, HeaderAction, IconTile, Loading, Progress, Screen, SectionHeader } from '../components/ui';

function greeting(): string {
  const h = new Date().getHours();
  return h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
}

export default function HomeScreen({ navigation }: any) {
  const { c } = useTheme();
  const { data, error, loading, refreshing, refresh } = useApi<Dashboard>('/dashboard');

  const right = (
    <>
      <HeaderAction icon="search" label="Search the Bible" onPress={() => navigation.navigate('BibleSearch')} />
      <HeaderAction icon="user" label="Profile" badge={data?.unread_notifications} onPress={() => navigation.navigate('Profile')} />
    </>
  );

  if (loading) return <Screen title="Prayer Journal" right={right}><Loading /></Screen>;
  if (error || ! data) return <Screen title="Prayer Journal" right={right}><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  const continueCourse = data.courses.find((x) => x.status !== 'completed') ?? data.courses[0];
  const verse = data.verse_of_the_day;
  const quick: { icon: IconName; label: string; onPress: () => void }[] = [
    { icon: 'edit-3', label: 'New entry', onPress: () => navigation.navigate('JournalEntry', { kind: 'prayer' }) },
    { icon: 'book-open', label: 'Read', onPress: () => navigation.navigate('Bible') },
    { icon: 'users', label: 'Prayer wall', onPress: () => navigation.navigate('PrayerWall') },
    { icon: 'shopping-bag', label: 'Shop', onPress: () => navigation.navigate('Shop') },
  ];

  return (
    <Screen title="Prayer Journal" subtitle={`${greeting()}, ${data.user.name}`} right={right} refreshing={refreshing} onRefresh={refresh}>
      {verse ? (
        <Pressable onPress={() => navigation.navigate('BibleChapter', { book: verse.book, chapter: verse.chapter, verse: verse.verse })}>
          <LinearGradient colors={[brand.purpleDark, brand.purple]} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.verse}>
            <View style={styles.verseTop}>
              <Text style={styles.verseEyebrow}>VERSE OF THE DAY</Text>
              <Pressable hitSlop={10} onPress={() => Share.share({ message: `“${verse.text}” — ${verse.reference}` })} accessibilityLabel="Share verse">
                <Icon name="share-2" size={18} color="rgba(255,255,255,0.85)" />
              </Pressable>
            </View>
            <Text style={styles.verseText}>“{verse.text}”</Text>
            <Text style={styles.verseRef}>{verse.reference}</Text>
          </LinearGradient>
        </Pressable>
      ) : null}

      <View style={styles.stats}>
        <Stat icon="zap" value={data.streak} label="day streak" tone="accent" />
        <Stat icon="feather" value={data.journal.total} label="entries" />
        <Stat icon="check-circle" value={data.journal.answered} label="answered" tone="success" />
      </View>

      <SectionHeader title="Continue learning" icon="award" action={data.courses.length ? 'All courses' : undefined} onAction={() => navigation.navigate('Learn')} />
      {continueCourse ? (
        <Card onPress={() => continueCourse.last_lesson_id
          ? navigation.navigate('Lesson', { slug: continueCourse.slug, lessonId: continueCourse.last_lesson_id })
          : navigation.navigate('CourseDetail', { slug: continueCourse.slug })}
        >
          <View style={{ flexDirection: 'row', gap: 14, alignItems: 'center' }}>
            {continueCourse.thumbnail_url
              ? <Image source={{ uri: continueCourse.thumbnail_url }} style={styles.thumb} />
              : <View style={[styles.thumb, { backgroundColor: c.brandSoft, alignItems: 'center', justifyContent: 'center' }]}><Icon name="book" size={26} color={c.brand} /></View>}
            <View style={{ flex: 1, gap: 4 }}>
              <Text style={[type.heading, { color: c.text }]} numberOfLines={2}>{continueCourse.title}</Text>
              <Text style={[type.small, { color: c.muted }]} numberOfLines={1}>{continueCourse.last_lesson_title ? `Up next: ${continueCourse.last_lesson_title}` : 'Start your first lesson'}</Text>
            </View>
            <View style={[styles.play, { backgroundColor: c.brand }]}><Icon name="play" size={16} color="#fff" /></View>
          </View>
          <View style={{ marginTop: 14, gap: 6 }}>
            <Progress value={continueCourse.progress} />
            <Text style={[type.small, { color: c.muted }]}>{continueCourse.progress}% complete</Text>
          </View>
        </Card>
      ) : (
        <EmptyState icon="award" title="Start your first course" body="My Prayer Journal walks you through six prayer secrets and 121 guided devotions." action="Browse courses" onAction={() => navigation.navigate('Learn')} />
      )}

      <View style={styles.quick}>
        {quick.map((q) => (
          <Pressable key={q.label} onPress={q.onPress} style={({ pressed }) => [styles.quickItem, { backgroundColor: c.surface, borderColor: c.border, opacity: pressed ? 0.8 : 1 }]}>
            <IconTile icon={q.icon} size={42} />
            <Text style={{ color: c.text, fontSize: 12, fontWeight: '700', marginTop: 8 }}>{q.label}</Text>
          </Pressable>
        ))}
      </View>

      {data.plans.length ? (
        <>
          <SectionHeader title="Today’s reading" icon="calendar" />
          {data.plans.map((p) => (
            <Card key={p.id} onPress={() => navigation.navigate('Bible')}>
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                <View style={{ flex: 1 }}>
                  <Text style={[type.heading, { color: c.text }]}>{p.title}</Text>
                  <Text style={[type.small, { color: c.muted, marginTop: 2 }]}>Day {p.today?.day ?? p.next_day} · {p.today?.readings ?? p.today?.label}</Text>
                </View>
                <Text style={{ color: c.brand, fontWeight: '800' }}>{p.percent}%</Text>
              </View>
              <View style={{ marginTop: 12 }}><Progress value={p.percent} /></View>
            </Card>
          ))}
        </>
      ) : null}

      <Card style={{ backgroundColor: c.accentSoft, borderColor: c.accentSoft }} onPress={() => navigation.navigate('Journal')}>
        <View style={{ flexDirection: 'row', gap: 14, alignItems: 'center' }}>
          <IconTile icon="heart" tone="accent" />
          <View style={{ flex: 1 }}>
            <Text style={[type.heading, { color: c.text }]}>{(() => { const n = data.journal.prayers - data.journal.answered; return n === 1 ? '1 prayer waiting' : `${n} prayers waiting`; })()}</Text>
            <Text style={[type.small, { color: c.text2 }]}>Look back over your journal and mark what God has answered.</Text>
          </View>
          <Icon name="chevron-right" size={20} color={c.text2} />
        </View>
      </Card>
    </Screen>
  );
}

function Stat({ icon, value, label, tone = 'brand' }: { icon: IconName; value: number; label: string; tone?: 'brand' | 'accent' | 'success' }) {
  const { c } = useTheme();
  const color = tone === 'accent' ? c.accent : tone === 'success' ? c.success : c.brand;
  return (
    <View style={[styles.stat, { backgroundColor: c.surface, borderColor: c.border }]}>
      <Icon name={icon} size={18} color={color} />
      <Text style={{ fontSize: 22, fontWeight: '800', color: c.text, marginTop: 6 }}>{value}</Text>
      <Text style={{ fontSize: 12, color: c.muted }}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  verse: { borderRadius: 20, padding: 20, gap: 10 },
  verseTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  verseEyebrow: { color: brand.goldLight, fontSize: 11, fontWeight: '800', letterSpacing: 1.2 },
  verseText: { color: '#fff', fontSize: 18, lineHeight: 28, fontFamily: type.serif },
  verseRef: { color: 'rgba(255,255,255,0.8)', fontWeight: '700' },
  stats: { flexDirection: 'row', gap: 10 },
  stat: { flex: 1, borderWidth: 1, borderRadius: 16, padding: 14 },
  thumb: { width: 64, height: 64, borderRadius: 12 },
  play: { width: 38, height: 38, borderRadius: 19, alignItems: 'center', justifyContent: 'center' },
  quick: { flexDirection: 'row', gap: 10 },
  quickItem: { flex: 1, alignItems: 'center', borderWidth: 1, borderRadius: 16, paddingVertical: 14 },
});
