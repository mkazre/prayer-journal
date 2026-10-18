import React from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { useAuth } from '../context/AuthContext';
import { TextScale, ThemeMode, useTheme } from '../context/ThemeContext';
import { type } from '../theme/colors';
import Icon, { IconName } from '../components/Icon';
import { Card, Logo, Screen, SectionHeader } from '../components/ui';

export default function ProfileScreen({ navigation }: any) {
  const { user, logout, config } = useAuth();
  const { c, mode, setMode, textScale, setTextScale } = useTheme();
  const web = config?.web_url ?? '';

  const rows: { icon: IconName; label: string; onPress: () => void }[] = [
    ...(config?.blog_menu ?? []).map((a) => ({ icon: 'map' as IconName, label: a.title, onPress: () => navigation.navigate('BlogArchive', { slug: a.slug, title: a.title }) })),
    { icon: 'users', label: 'Prayer wall', onPress: () => navigation.navigate('PrayerWall') },
    { icon: 'book', label: 'My library', onPress: () => navigation.navigate('Library') },
    { icon: 'award', label: 'Student status & pricing', onPress: () => WebBrowser.openBrowserAsync(`${web}account/student`) },
    { icon: 'shopping-cart', label: 'Cart', onPress: () => navigation.navigate('Cart') },
    { icon: 'package', label: 'My orders', onPress: () => WebBrowser.openBrowserAsync(`${web}my-orders`) },
    { icon: 'award', label: 'My certificates', onPress: () => WebBrowser.openBrowserAsync(`${web}my-certificates`) },
    { icon: 'bell', label: 'Notifications', onPress: () => WebBrowser.openBrowserAsync(`${web}notifications`) },
    { icon: 'settings', label: 'Account settings', onPress: () => WebBrowser.openBrowserAsync(`${web}account`) },
  ];

  return (
    <Screen title="Profile" back>
      <Card style={{ flexDirection: 'row', alignItems: 'center', gap: 14 }}>
        <View style={[styles.avatar, { backgroundColor: c.brand }]}>
          <Text style={{ color: '#fff', fontSize: 22, fontWeight: '800' }}>{(user?.username ?? '?').slice(0, 1).toUpperCase()}</Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={[type.title, { color: c.text }]}>{user?.username}</Text>
          <Text style={{ color: c.muted }}>{user?.email}</Text>
        </View>
      </Card>

      <SectionHeader title="Appearance" icon="sun" />
      <Card style={{ gap: 16 }}>
        <Segment<ThemeMode>
          label="Theme"
          value={mode}
          onChange={setMode}
          options={[['system', 'Auto', 'smartphone'], ['light', 'Light', 'sun'], ['dark', 'Dark', 'moon']]}
        />
        <Segment<TextScale>
          label="Reading size"
          value={textScale}
          onChange={setTextScale}
          options={[['sm', 'Small', 'type'], ['md', 'Default', 'type'], ['lg', 'Large', 'type'], ['xl', 'X-Large', 'type']]}
        />
        <Text style={{ color: c.text, fontFamily: type.serif, fontSize: 16 * ({ sm: 0.9, md: 1, lg: 1.15, xl: 1.3 }[textScale]), lineHeight: 26 * ({ sm: 0.9, md: 1, lg: 1.15, xl: 1.3 }[textScale]) }}>
          “Thy word is a lamp unto my feet, and a light unto my path.” — Psalm 119:105
        </Text>
      </Card>

      <SectionHeader title="More" icon="grid" />
      <View style={[styles.list, { backgroundColor: c.surface, borderColor: c.border }]}>
        {rows.map((r, i) => (
          <Pressable key={r.label} onPress={r.onPress} style={({ pressed }) => [styles.row, i > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: c.border }, pressed && { backgroundColor: c.surface2 }]}>
            <Icon name={r.icon} size={19} color={c.brand} />
            <Text style={{ color: c.text, fontSize: 15, flex: 1 }}>{r.label}</Text>
            <Icon name="chevron-right" size={18} color={c.muted} />
          </Pressable>
        ))}
      </View>

      <Pressable
        onPress={() => Alert.alert('Sign out?', undefined, [{ text: 'Cancel', style: 'cancel' }, { text: 'Sign out', style: 'destructive', onPress: logout }])}
        style={[styles.row, styles.list, { backgroundColor: c.surface, borderColor: c.border }]}
      >
        <Icon name="log-out" size={19} color={c.danger} />
        <Text style={{ color: c.danger, fontSize: 15, fontWeight: '700' }}>Sign out</Text>
      </Pressable>

      <View style={{ alignItems: 'center', gap: 6, marginTop: 8 }}>
        <Logo size={40} />
        <Text style={{ color: c.muted, fontSize: 12 }}>{config?.name ?? 'Prayer Journal'} · {config?.tagline ?? 'Pray. Reflect. Grow.'}</Text>
      </View>
    </Screen>
  );
}

function Segment<T extends string>({ label, value, onChange, options }: { label: string; value: T; onChange: (v: T) => void; options: [T, string, IconName][] }) {
  const { c } = useTheme();
  return (
    <View style={{ gap: 8 }}>
      <Text style={{ color: c.text2, fontWeight: '600', fontSize: 13 }}>{label}</Text>
      <View style={[styles.segment, { backgroundColor: c.surface2 }]}>
        {options.map(([v, l, icon]) => (
          <Pressable key={v} onPress={() => onChange(v)} style={[styles.segBtn, value === v && { backgroundColor: c.surface, shadowColor: '#000', shadowOpacity: 0.08, shadowRadius: 4, elevation: 1 }]}>
            <Icon name={icon} size={14} color={value === v ? c.brand : c.muted} />
            <Text style={{ color: value === v ? c.text : c.muted, fontWeight: '700', fontSize: 12 }}>{l}</Text>
          </Pressable>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  avatar: { width: 56, height: 56, borderRadius: 28, alignItems: 'center', justifyContent: 'center' },
  list: { borderWidth: 1, borderRadius: 16, overflow: 'hidden' },
  row: { flexDirection: 'row', alignItems: 'center', gap: 14, paddingHorizontal: 16, paddingVertical: 15 },
  segment: { flexDirection: 'row', borderRadius: 12, padding: 4, gap: 4 },
  segBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 5, paddingVertical: 9, borderRadius: 9 },
});
