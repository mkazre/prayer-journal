import React, { useEffect, useRef, useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import * as Haptics from 'expo-haptics';
import * as WebBrowser from 'expo-web-browser';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { api } from '../api/client';
import { Lesson, Prompt } from '../api/types';
import { type } from '../theme/colors';
import Icon, { IconName } from '../components/Icon';
import Html from '../components/Html';
import { Badge, Button, Card, ErrorState, HeaderAction, Loading, Screen } from '../components/ui';

const KIND_ICON: Record<string, IconName> = { prayer: 'heart', meditation: 'feather', gratitude: 'sunrise', note: 'edit-3' };

export default function LessonScreen({ route, navigation }: any) {
  const { slug, lessonId } = route.params;
  const { c, readScale } = useTheme();
  const insets = useSafeAreaInsets();
  const { data, error, loading, refresh, setData } = useApi<Lesson>(`/study/${slug}/lesson/${lessonId}`, { refetchOnFocus: false });
  const [busy, setBusy] = useState(false);

  if (loading) return <Screen title="Lesson" back><Loading /></Screen>;
  if (error || ! data) return <Screen title="Lesson" back><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  function go(item: { kind: string; id: number } | null) {
    if (! item) return navigation.navigate('CourseDetail', { slug });
    if (item.kind === 'lesson') navigation.replace('Lesson', { slug, lessonId: item.id });
    else if (item.kind === 'quiz') navigation.replace('Quiz', { slug, quizId: item.id });
    else navigation.navigate('CourseDetail', { slug });
  }

  async function complete() {
    if (! data) return;
    setBusy(true);
    try {
      const res = await api.post<{ done: boolean; progress: number; complete: boolean }>(`/study/${slug}/lesson/${lessonId}/complete`, data.done ? { undo: true } : {});
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success).catch(() => undefined);
      setData({ ...data, done: res.done });
      if (res.done && ! res.complete && data.next && ! data.next.locked) {
        go(data.next);
      } else if (res.complete) {
        navigation.navigate('CourseDetail', { slug });
      }
    } finally {
      setBusy(false);
    }
  }

  const footer = (
    <View style={[styles.footer, { backgroundColor: c.surface, borderTopColor: c.border, paddingBottom: insets.bottom + 10 }]}>
      <Pressable disabled={! data.prev} onPress={() => go(data.prev)} style={[styles.navBtn, { borderColor: c.border, opacity: data.prev ? 1 : 0.35 }]} accessibilityLabel="Previous">
        <Icon name="arrow-left" size={20} color={c.text} />
      </Pressable>
      <Button title={data.done ? 'Completed' : data.next ? 'Complete & continue' : 'Complete lesson'} icon={data.done ? 'check-circle' : 'check'} variant={data.done ? 'soft' : 'primary'} onPress={complete} loading={busy} style={{ flex: 1 }} />
      <Pressable disabled={! data.next} onPress={() => go(data.next)} style={[styles.navBtn, { borderColor: c.border, opacity: data.next && ! data.next.locked ? 1 : 0.35 }]} accessibilityLabel="Next">
        <Icon name="arrow-right" size={20} color={c.text} />
      </Pressable>
    </View>
  );

  return (
    <Screen
      title={data.title}
      subtitle={data.section_title ?? undefined}
      back
      footer={footer}
      right={<HeaderAction icon="feather" label="Open journal" onPress={() => navigation.navigate('Journal')} />}
    >
      <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }}>
        {data.duration_mins ? <Badge label={`${data.duration_mins} min`} icon="clock" tone="muted" /> : null}
        {data.done ? <Badge label="Completed" icon="check" tone="success" /> : null}
      </View>

      {data.video_url ? (
        <Card onPress={() => WebBrowser.openBrowserAsync(data.video_url!)} style={{ backgroundColor: c.text, borderColor: c.text, alignItems: 'center', paddingVertical: 36 }}>
          <Icon name="play-circle" size={52} color="#fff" />
          <Text style={{ color: '#fff', fontWeight: '700', marginTop: 8 }}>Watch the teaching</Text>
        </Card>
      ) : null}

      {data.scripture ? (
        <View style={[styles.scripture, { backgroundColor: c.brandSoft, borderLeftColor: c.brand }]}>
          <Pressable onPress={() => navigation.navigate('BibleSearch', { q: data.scripture!.label })} style={{ flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 8 }}>
            <Icon name="book-open" size={15} color={c.brand} />
            <Text style={{ color: c.brand, fontWeight: '800', fontSize: 13 }}>{data.scripture.label}</Text>
          </Pressable>
          <Text style={{ color: c.text, fontFamily: type.serif, fontSize: 16 * readScale, lineHeight: 26 * readScale }}>
            {data.scripture.verses.map((v) => (
              <Text key={v.verse}><Text style={{ color: c.brand, fontSize: 11 * readScale, fontWeight: '800' }}>{v.verse} </Text>{v.text} </Text>
            ))}
          </Text>
        </View>
      ) : null}

      {data.content_html ? <Html html={data.content_html} /> : null}

      {data.prompts.length ? (
        <View style={{ gap: 12 }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 8 }}>
            <Icon name="feather" size={18} color={c.accent} />
            <Text style={[type.heading, { color: c.text }]}>Your journal</Text>
          </View>
          <Text style={[type.small, { color: c.muted, marginTop: -6 }]}>Private to you. Saves automatically to your Prayer Journal.</Text>
          {data.prompts.map((p) => <PromptBox key={p.key} prompt={p} slug={slug} lessonId={lessonId} />)}
        </View>
      ) : null}
    </Screen>
  );
}

function PromptBox({ prompt, slug, lessonId }: { prompt: Prompt; slug: string; lessonId: number }) {
  const { c, readScale } = useTheme();
  const [text, setText] = useState(prompt.answer);
  const [state, setState] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const last = useRef(prompt.answer);

  useEffect(() => () => { if (timer.current) clearTimeout(timer.current); }, []);

  function save(value: string) {
    if (value === last.current) return;
    setState('saving');
    api.post(`/study/${slug}/lesson/${lessonId}/journal`, { prompt: prompt.key, body: value })
      .then(() => { last.current = value; setState('saved'); })
      .catch(() => setState('error'));
  }

  function onChange(v: string) {
    setText(v);
    if (timer.current) clearTimeout(timer.current);
    timer.current = setTimeout(() => save(v), 1200);
  }

  return (
    <View style={[styles.prompt, { backgroundColor: c.surface, borderColor: c.border }]}>
      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
        <Icon name={KIND_ICON[prompt.kind] ?? 'edit-3'} size={16} color={c.brand} />
        <Text style={{ color: c.text, fontWeight: '700', flex: 1 }}>{prompt.label}</Text>
        <Text style={{ color: state === 'error' ? c.danger : c.muted, fontSize: 11 }}>{{ idle: '', saving: 'Saving…', saved: 'Saved', error: 'Not saved' }[state]}</Text>
      </View>
      {prompt.hint ? <Text style={{ color: c.muted, fontSize: 12 }}>{prompt.hint}</Text> : null}
      <TextInput
        value={text}
        onChangeText={onChange}
        onBlur={() => save(text)}
        multiline
        placeholder="Write here…"
        placeholderTextColor={c.muted}
        style={{ color: c.text, minHeight: 110, textAlignVertical: 'top', fontFamily: type.serif, fontSize: 16 * readScale, lineHeight: 24 * readScale }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  scripture: { borderLeftWidth: 4, borderRadius: 12, padding: 16 },
  prompt: { borderWidth: 1, borderRadius: 16, padding: 14, gap: 8 },
  footer: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingHorizontal: 16, paddingTop: 10, borderTopWidth: StyleSheet.hairlineWidth },
  navBtn: { width: 50, height: 50, borderRadius: 12, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
});
