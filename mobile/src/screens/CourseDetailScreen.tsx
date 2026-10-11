import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { api, ApiError } from '../api/client';
import { colors } from '../theme/colors';

type Lesson = { id: number; title: string; price_cents: number };
type Course = { id: number; title: string; description: string; price_cents: number; lessons: Lesson[] };

export default function CourseDetailScreen({ route, navigation }: any) {
  const { slug } = route.params;
  const [course, setCourse] = useState<Course | null>(null);
  const [enrolled, setEnrolled] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      try {
        const data = await api.get<Course>(`/courses/${slug}`, false);
        setCourse(data);
        // A lesson fetch succeeding tells us we're enrolled; a 403 means we're not.
        await api.get(`/study/${slug}/lesson/${data.lessons[0]?.id ?? 0}`);
        setEnrolled(true);
      } catch (e) {
        setEnrolled(false);
      } finally {
        setLoading(false);
      }
    })();
  }, [slug]);

  async function enroll() {
    try {
      await api.post(`/courses/${slug}/enroll`);
      setEnrolled(true);
    } catch (e: any) {
      if (e instanceof ApiError && e.status === 402) {
        Alert.alert('Payment required', 'This course requires payment - checkout from the web app for now.');
      } else {
        Alert.alert('Could not enroll', e.message);
      }
    }
  }

  if (loading || ! course) {
    return <ActivityIndicator style={styles.loader} size="large" color={colors.purple} />;
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 20 }}>
      <Text style={styles.title}>{course.title}</Text>
      <Text style={styles.description}>{course.description}</Text>

      {! enrolled && (
        <TouchableOpacity style={styles.enrollBtn} onPress={enroll}>
          <Text style={styles.enrollText}>{course.price_cents > 0 ? 'Buy Course' : 'Enroll Free'}</Text>
        </TouchableOpacity>
      )}

      <Text style={styles.sectionTitle}>Lessons</Text>
      {course.lessons.map((lesson) => (
        <TouchableOpacity
          key={lesson.id}
          style={styles.lessonRow}
          disabled={! enrolled}
          onPress={() => navigation.navigate('Lesson', { slug, lessonId: lesson.id, title: lesson.title })}
        >
          <Text style={[styles.lessonTitle, ! enrolled && styles.disabled]}>{lesson.title}</Text>
          {lesson.price_cents > 0 && <Text style={styles.badge}>${(lesson.price_cents / 100).toFixed(2)}</Text>}
        </TouchableOpacity>
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg },
  loader: { flex: 1, backgroundColor: colors.bg },
  title: { fontSize: 22, fontWeight: '700', color: colors.purpleDark },
  description: { color: colors.gray, marginTop: 8, lineHeight: 20 },
  enrollBtn: { backgroundColor: colors.gold, borderRadius: 10, padding: 14, alignItems: 'center', marginTop: 16 },
  enrollText: { color: colors.black, fontWeight: '700' },
  sectionTitle: { fontSize: 18, fontWeight: '700', color: colors.purpleDark, marginTop: 24, marginBottom: 8 },
  lessonRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    backgroundColor: colors.white, borderRadius: 10, padding: 14, marginBottom: 8, borderWidth: 1, borderColor: colors.border,
  },
  lessonTitle: { color: colors.text },
  disabled: { color: colors.gray },
  badge: { color: colors.purple, fontWeight: '700', fontSize: 12 },
});
