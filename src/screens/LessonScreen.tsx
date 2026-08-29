import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { api } from '../api/client';
import { colors } from '../theme/colors';

type Lesson = { id: number; title: string; content: string; bible_reference: string | null; video_url: string | null };

export default function LessonScreen({ route, navigation }: any) {
  const { slug, lessonId } = route.params;
  const [lesson, setLesson] = useState<Lesson | null>(null);
  const [loading, setLoading] = useState(true);
  const [completing, setCompleting] = useState(false);

  useEffect(() => {
    api.get<Lesson>(`/study/${slug}/lesson/${lessonId}`).then(setLesson).finally(() => setLoading(false));
  }, [slug, lessonId]);

  async function complete() {
    setCompleting(true);
    try {
      await api.post(`/study/${slug}/lesson/${lessonId}/complete`);
      Alert.alert('Nice work!', 'Lesson marked complete.');
      navigation.goBack();
    } catch (e: any) {
      Alert.alert('Error', e.message);
    } finally {
      setCompleting(false);
    }
  }

  if (loading || ! lesson) {
    return <ActivityIndicator style={styles.loader} size="large" color={colors.purple} />;
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 20 }}>
      <Text style={styles.title}>{lesson.title}</Text>
      {lesson.bible_reference && <Text style={styles.reference}>{lesson.bible_reference}</Text>}
      <Text style={styles.content}>{lesson.content}</Text>

      <TouchableOpacity style={styles.button} onPress={complete} disabled={completing}>
        {completing ? <ActivityIndicator color={colors.black} /> : <Text style={styles.buttonText}>Mark Complete</Text>}
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg },
  loader: { flex: 1, backgroundColor: colors.bg },
  title: { fontSize: 22, fontWeight: '700', color: colors.purpleDark },
  reference: { color: colors.purple, fontStyle: 'italic', marginTop: 6 },
  content: { color: colors.text, marginTop: 16, lineHeight: 22 },
  button: { backgroundColor: colors.gold, borderRadius: 10, padding: 14, alignItems: 'center', marginTop: 28 },
  buttonText: { color: colors.black, fontWeight: '700' },
});
