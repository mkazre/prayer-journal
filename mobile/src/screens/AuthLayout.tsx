import React from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../context/ThemeContext';
import { brand } from '../theme/colors';
import { Logo } from '../components/ui';

/** Shared frame for sign-in and sign-up: brand panel with the logo, then the form card. */
export default function AuthLayout({ title, subtitle, children }: { title: string; subtitle: string; children: React.ReactNode }) {
  const { c } = useTheme();
  const insets = useSafeAreaInsets();
  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: c.bg }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={{ flexGrow: 1 }} keyboardShouldPersistTaps="handled">
        <LinearGradient colors={[brand.purpleDark, brand.purple]} style={[styles.hero, { paddingTop: insets.top + 36 }]}>
          <Logo size={72} />
          <Text style={styles.brand}>Prayer Journal</Text>
          <Text style={styles.tag}>Pray. Reflect. Grow.</Text>
        </LinearGradient>
        <View style={[styles.card, { backgroundColor: c.surface, borderColor: c.border }]}>
          <Text style={{ fontSize: 24, fontWeight: '800', color: c.text }}>{title}</Text>
          <Text style={{ fontSize: 14, color: c.muted, marginTop: 4, marginBottom: 20 }}>{subtitle}</Text>
          <View style={{ gap: 14 }}>{children}</View>
        </View>
        <View style={{ height: insets.bottom + 24 }} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  hero: { alignItems: 'center', paddingBottom: 64, gap: 8 },
  brand: { color: '#fff', fontSize: 22, fontWeight: '800', marginTop: 8 },
  tag: { color: brand.goldLight, fontSize: 13, fontWeight: '600', letterSpacing: 1 },
  card: { marginHorizontal: 16, marginTop: -40, borderRadius: 22, borderWidth: 1, padding: 22 },
});
