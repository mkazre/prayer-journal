import React, { useRef, useState } from 'react';
import { View, Text, StyleSheet, Dimensions, ScrollView, NativeSyntheticEvent, NativeScrollEvent, Image, TouchableOpacity } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { colors } from '../theme/colors';

const { width } = Dimensions.get('window');

const SLIDES = [
  {
    emoji: '📚',
    title: 'Study the Word',
    body: 'Take courses, watch lessons, complete quizzes and assignments, and grow at your own pace.',
  },
  {
    emoji: '📖',
    title: 'Read Scripture',
    body: 'The full Bible, always with you. Bookmark verses, highlight, and keep personal notes.',
  },
  {
    emoji: '🙏',
    title: 'Grow in Community',
    body: 'Share prayer requests, track your reading streak, and shop resources for your journey.',
  },
];

export default function OnboardingScreen({ onDone }: { onDone: () => void }) {
  const [index, setIndex] = useState(0);
  const scrollRef = useRef<ScrollView>(null);

  function handleScroll(e: NativeSyntheticEvent<NativeScrollEvent>) {
    setIndex(Math.round(e.nativeEvent.contentOffset.x / width));
  }

  async function finish() {
    await AsyncStorage.setItem('pj_onboarded', 'true');
    onDone();
  }

  function next() {
    if (index < SLIDES.length - 1) {
      scrollRef.current?.scrollTo({ x: (index + 1) * width, animated: true });
    } else {
      finish();
    }
  }

  return (
    <View style={styles.container}>
      <ScrollView
        ref={scrollRef}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onMomentumScrollEnd={handleScroll}
      >
        {SLIDES.map((slide, i) => (
          <View key={i} style={[styles.slide, { width }]}>
            <Text style={styles.emoji}>{slide.emoji}</Text>
            <Text style={styles.title}>{slide.title}</Text>
            <Text style={styles.body}>{slide.body}</Text>
          </View>
        ))}
      </ScrollView>

      <View style={styles.dots}>
        {SLIDES.map((_, i) => (
          <View key={i} style={[styles.dot, i === index && styles.dotActive]} />
        ))}
      </View>

      <View style={styles.footer}>
        <TouchableOpacity onPress={finish}>
          <Text style={styles.skip}>Skip</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.nextBtn} onPress={next}>
          <Text style={styles.nextText}>{index === SLIDES.length - 1 ? 'Get Started' : 'Next'}</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.black },
  slide: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 32 },
  emoji: { fontSize: 72, marginBottom: 24 },
  title: { fontSize: 26, fontWeight: '700', color: colors.white, marginBottom: 12, textAlign: 'center' },
  body: { fontSize: 16, color: '#cfc6df', textAlign: 'center', lineHeight: 22 },
  dots: { flexDirection: 'row', justifyContent: 'center', marginBottom: 24 },
  dot: { width: 8, height: 8, borderRadius: 4, backgroundColor: '#4a4058', marginHorizontal: 4 },
  dotActive: { backgroundColor: colors.gold, width: 20 },
  footer: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 32, paddingBottom: 40 },
  skip: { color: '#cfc6df', fontSize: 16 },
  nextBtn: { backgroundColor: colors.gold, paddingHorizontal: 28, paddingVertical: 12, borderRadius: 24 },
  nextText: { color: colors.black, fontWeight: '700', fontSize: 16 },
});
