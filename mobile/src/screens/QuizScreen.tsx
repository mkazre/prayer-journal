import React, { useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import * as Haptics from 'expo-haptics';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { api } from '../api/client';
import { Quiz, QuizResult } from '../api/types';
import { type } from '../theme/colors';
import Icon from '../components/Icon';
import { Badge, Button, Card, ErrorState, IconTile, Loading, Screen } from '../components/ui';

type Answers = Record<number, number | number[] | string>;

export default function QuizScreen({ route, navigation }: any) {
  const { slug, quizId } = route.params;
  const { c } = useTheme();
  const insets = useSafeAreaInsets();
  const { data, error, loading, refresh } = useApi<Quiz>(`/study/${slug}/quiz/${quizId}`, { refetchOnFocus: false });
  const [answers, setAnswers] = useState<Answers>({});
  const [result, setResult] = useState<QuizResult | null>(null);
  const [busy, setBusy] = useState(false);

  if (loading) return <Screen title="Quiz" back><Loading /></Screen>;
  if (error || ! data) return <Screen title="Quiz" back><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  const answered = data.questions.filter((q) => {
    const a = answers[q.id];
    return Array.isArray(a) ? a.length > 0 : a !== undefined && a !== '';
  }).length;

  function pick(qid: number, optionId: number, multiple: boolean) {
    Haptics.selectionAsync().catch(() => undefined);
    setAnswers((prev) => {
      if (! multiple) return { ...prev, [qid]: optionId };
      const cur = Array.isArray(prev[qid]) ? (prev[qid] as number[]) : [];
      return { ...prev, [qid]: cur.includes(optionId) ? cur.filter((x) => x !== optionId) : [...cur, optionId] };
    });
  }

  async function submit() {
    if (! data) return;
    if (answered < data.questions.length) {
      const go = await new Promise<boolean>((resolve) => Alert.alert('Submit now?', `You have answered ${answered} of ${data.questions.length} questions.`, [
        { text: 'Keep going', style: 'cancel', onPress: () => resolve(false) },
        { text: 'Submit', onPress: () => resolve(true) },
      ]));
      if (! go) return;
    }
    setBusy(true);
    try {
      const res = await api.post<QuizResult>(`/study/${slug}/quiz/${quizId}/submit`, { answers });
      Haptics.notificationAsync(res.passed ? Haptics.NotificationFeedbackType.Success : Haptics.NotificationFeedbackType.Warning).catch(() => undefined);
      setResult(res);
    } catch (e: any) {
      Alert.alert('Could not submit', e.message);
    } finally {
      setBusy(false);
    }
  }

  if (result) {
    const passed = !! Number(result.passed);
    return (
      <Screen title={data.title} back>
        <Card style={{ alignItems: 'center', gap: 10, paddingVertical: 28 }}>
          <IconTile icon={passed ? 'award' : 'refresh-cw'} tone={passed ? 'success' : 'accent'} size={72} />
          <Text style={{ fontSize: 44, fontWeight: '800', color: c.text }}>{result.score_percent}%</Text>
          <Text style={[type.heading, { color: passed ? c.success : c.text }]}>{passed ? 'Well done — you passed!' : `You need ${data.pass_percent}% to pass`}</Text>
          <Text style={[type.small, { color: c.muted }]}>Course progress: {result.progress}%</Text>
        </Card>
        {data.questions.map((q, i) => {
          const r = result.review[String(q.id)];
          return (
            <Card key={q.id}>
              <View style={{ flexDirection: 'row', gap: 10 }}>
                <Icon name={r?.correct ? 'check-circle' : 'x-circle'} size={20} color={r?.correct ? c.success : c.danger} />
                <Text style={{ color: c.text, fontWeight: '600', flex: 1 }}>{i + 1}. {q.prompt}</Text>
              </View>
              {! r?.correct && r?.correct_ids?.length ? (
                <Text style={{ color: c.text2, marginTop: 8, fontSize: 13 }}>Answer: {q.options.filter((o) => r.correct_ids!.includes(o.id)).map((o) => o.text).join(', ')}</Text>
              ) : null}
              {r?.explanation ? <Text style={{ color: c.muted, marginTop: 6, fontSize: 13 }}>{r.explanation}</Text> : null}
            </Card>
          );
        })}
        <Button title="Back to course" icon="layers" onPress={() => navigation.navigate('CourseDetail', { slug })} />
        {! passed && (data.attempts_left === null || data.attempts_left > 1) ? <Button title="Try again" variant="ghost" icon="refresh-cw" onPress={() => { setResult(null); setAnswers({}); }} /> : null}
      </Screen>
    );
  }

  return (
    <Screen
      title={data.title}
      subtitle={`${answered}/${data.questions.length} answered · pass mark ${data.pass_percent}%`}
      back
      footer={<View style={{ padding: 16, paddingBottom: insets.bottom + 12, backgroundColor: c.surface, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: c.border }}><Button title="Submit answers" icon="send" onPress={submit} loading={busy} disabled={data.attempts_left === 0} /></View>}
    >
      <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }}>
        {data.attempts_left !== null ? <Badge label={`${data.attempts_left} attempts left`} icon="repeat" tone={data.attempts_left ? 'brand' : 'danger'} /> : null}
        {data.best ? <Badge label={`Best ${data.best.score_percent}%`} icon="award" tone={Number(data.best.passed) ? 'success' : 'muted'} /> : null}
      </View>
      {data.questions.map((q, i) => (
        <Card key={q.id}>
          <Text style={[type.tiny, { color: c.brand }]}>Question {i + 1}{q.type === 'multiple' ? ' · choose all that apply' : ''}</Text>
          <Text style={{ color: c.text, fontSize: 16, fontWeight: '600', marginTop: 6, marginBottom: 12, lineHeight: 23 }}>{q.prompt}</Text>
          {q.type === 'short_text' ? (
            <TextInput
              value={String(answers[q.id] ?? '')}
              onChangeText={(v) => setAnswers((p) => ({ ...p, [q.id]: v }))}
              placeholder="Type your answer"
              placeholderTextColor={c.muted}
              style={[styles.short, { color: c.text, borderColor: c.border, backgroundColor: c.bg }]}
            />
          ) : q.options.map((o) => {
            const a = answers[q.id];
            const selected = Array.isArray(a) ? a.includes(o.id) : a === o.id;
            return (
              <Pressable key={o.id} onPress={() => pick(q.id, o.id, q.type === 'multiple')} style={[styles.option, { borderColor: selected ? c.brand : c.border, backgroundColor: selected ? c.brandSoft : 'transparent' }]}>
                <View style={[q.type === 'multiple' ? styles.box : styles.radio, { borderColor: selected ? c.brand : c.muted, backgroundColor: selected ? c.brand : 'transparent' }]}>
                  {selected ? <Icon name="check" size={12} color="#fff" /> : null}
                </View>
                <Text style={{ color: c.text, flex: 1 }}>{o.text}</Text>
              </Pressable>
            );
          })}
        </Card>
      ))}
    </Screen>
  );
}

const styles = StyleSheet.create({
  option: { flexDirection: 'row', alignItems: 'center', gap: 12, borderWidth: 1.5, borderRadius: 12, padding: 13, marginBottom: 8 },
  radio: { width: 22, height: 22, borderRadius: 11, borderWidth: 2, alignItems: 'center', justifyContent: 'center' },
  box: { width: 22, height: 22, borderRadius: 6, borderWidth: 2, alignItems: 'center', justifyContent: 'center' },
  short: { borderWidth: 1, borderRadius: 12, padding: 12, fontSize: 15 },
});
