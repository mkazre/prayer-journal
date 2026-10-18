import React, { useRef, useState } from 'react';
import { Dimensions, NativeScrollEvent, NativeSyntheticEvent, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { brand } from '../theme/colors';
import Icon, { IconName } from '../components/Icon';
import { Button, Logo } from '../components/ui';

const { width } = Dimensions.get('window');

const SLIDES: { icon: IconName; title: string; body: string }[] = [
  { icon: 'feather', title: 'A prayer journal that travels with you', body: 'Guided devotions with space for your prayer points, meditations and gratitude — saved privately to your journal.' },
  { icon: 'book-open', title: 'The whole Bible, beautifully', body: 'Read, search, highlight and bookmark. Follow a reading plan and keep your daily streak alive.' },
  { icon: 'award', title: 'Grow with courses', body: 'Lessons, quizzes and certificates — starting with My Prayer Journal: The Believer’s Prayer Companion.' },
  { icon: 'users', title: 'Never pray alone', body: 'Share requests on the prayer wall, let others know you prayed, and celebrate answered prayer together.' },
];

export default function OnboardingScreen({ navigation }: any) {
  const [index, setIndex] = useState(0);
  const scrollRef = useRef<ScrollView>(null);
  const insets = useSafeAreaInsets();

  function onScroll(e: NativeSyntheticEvent<NativeScrollEvent>) {
    setIndex(Math.round(e.nativeEvent.contentOffset.x / width));
  }

  async function finish(to: 'Login' | 'Register') {
    await AsyncStorage.setItem('pj_onboarded', 'true');
    navigation.replace(to);
  }

  const last = index === SLIDES.length - 1;

  return (
    <LinearGradient colors={[brand.navyDark, '#131827']} style={{ flex: 1, paddingTop: insets.top + 16, paddingBottom: insets.bottom + 20 }}>
      <View style={styles.top}>
        <Logo size={44} />
        <Pressable onPress={() => finish('Login')} hitSlop={10}><Text style={styles.skip}>Skip</Text></Pressable>
      </View>

      <ScrollView ref={scrollRef} horizontal pagingEnabled showsHorizontalScrollIndicator={false} onMomentumScrollEnd={onScroll}>
        {SLIDES.map((s) => (
          <View key={s.title} style={[styles.slide, { width }]}>
            <View style={styles.ring}>
              <View style={styles.iconWrap}><Icon name={s.icon} size={44} color={brand.goldLight} /></View>
            </View>
            <Text style={styles.title}>{s.title}</Text>
            <Text style={styles.body}>{s.body}</Text>
          </View>
        ))}
      </ScrollView>

      <View style={styles.dots}>
        {SLIDES.map((s, i) => <View key={s.title} style={[styles.dot, i === index && styles.dotActive]} />)}
      </View>

      <View style={{ paddingHorizontal: 24, gap: 10 }}>
        {last ? (
          <>
            <Button title="Create free account" variant="accent" icon="user-plus" onPress={() => finish('Register')} />
            <Button title="I already have an account" variant="ghost" onPress={() => finish('Login')} style={{ borderColor: 'rgba(255,255,255,0.25)' }} />
          </>
        ) : (
          <Button title="Continue" variant="accent" icon="arrow-right" onPress={() => scrollRef.current?.scrollTo({ x: (index + 1) * width, animated: true })} />
        )}
      </View>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  top: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 24 },
  skip: { color: 'rgba(255,255,255,0.75)', fontSize: 15, fontWeight: '600' },
  slide: { alignItems: 'center', justifyContent: 'center', paddingHorizontal: 36 },
  ring: { width: 150, height: 150, borderRadius: 75, borderWidth: 1, borderColor: 'rgba(240,216,120,0.25)', alignItems: 'center', justifyContent: 'center', marginBottom: 36 },
  iconWrap: { width: 108, height: 108, borderRadius: 54, backgroundColor: 'rgba(255,255,255,0.08)', alignItems: 'center', justifyContent: 'center' },
  title: { fontSize: 26, fontWeight: '800', color: '#fff', textAlign: 'center', letterSpacing: -0.3, marginBottom: 12 },
  body: { fontSize: 16, color: 'rgba(255,255,255,0.75)', textAlign: 'center', lineHeight: 24 },
  dots: { flexDirection: 'row', justifyContent: 'center', marginBottom: 24, gap: 6 },
  dot: { width: 7, height: 7, borderRadius: 4, backgroundColor: 'rgba(255,255,255,0.25)' },
  dotActive: { backgroundColor: brand.gold, width: 22 },
});
