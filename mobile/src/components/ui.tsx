import React from 'react';
import {
  ActivityIndicator, Image, Pressable, ScrollView, StyleProp, StyleSheet, Text, TextInput, TextInputProps, View, ViewStyle, RefreshControl,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../context/ThemeContext';
import { radius, space, type } from '../theme/colors';
import Icon, { IconName } from './Icon';

export const LOGO = require('../../assets/logo-mark.png');

/* ------------------------------------------------------------------ */
/* Brand                                                               */
/* ------------------------------------------------------------------ */

export function Logo({ size = 34 }: { size?: number }) {
  return <Image source={LOGO} style={{ width: size, height: size, borderRadius: size * 0.24 }} accessibilityLabel="Prayer Journal logo" />;
}

/**
 * The header shown on every screen: logo, title, optional back button and
 * actions. Screens render it themselves (navigator headers are hidden) so
 * the logo is guaranteed everywhere.
 */
export function AppHeader({ title, subtitle, back, right }: { title: string; subtitle?: string; back?: boolean; right?: React.ReactNode }) {
  const { c } = useTheme();
  const nav = useNavigation();
  const insets = useSafeAreaInsets();

  return (
    <View style={[styles.header, { paddingTop: insets.top + 8, backgroundColor: c.surface, borderBottomColor: c.border }]}>
      {back && nav.canGoBack() ? (
        <Pressable onPress={() => nav.goBack()} hitSlop={10} style={styles.headerBtn} accessibilityRole="button" accessibilityLabel="Back">
          <Icon name="chevron-left" size={26} color={c.text} />
        </Pressable>
      ) : null}
      <Logo size={32} />
      <View style={{ flex: 1, marginLeft: 10 }}>
        <Text style={[styles.headerTitle, { color: c.text }]} numberOfLines={1}>{title}</Text>
        {subtitle ? <Text style={[type.small, { color: c.muted }]} numberOfLines={1}>{subtitle}</Text> : null}
      </View>
      {right}
    </View>
  );
}

export function HeaderAction({ icon, onPress, label, badge }: { icon: IconName; onPress: () => void; label: string; badge?: number }) {
  const { c } = useTheme();
  return (
    <Pressable onPress={onPress} hitSlop={8} style={[styles.headerAction, { backgroundColor: c.surface2 }]} accessibilityRole="button" accessibilityLabel={label}>
      <Icon name={icon} size={19} color={c.text} />
      {badge ? <View style={[styles.dot, { backgroundColor: c.accent, borderColor: c.surface }]}><Text style={styles.dotText}>{badge > 9 ? '9+' : badge}</Text></View> : null}
    </Pressable>
  );
}

/* ------------------------------------------------------------------ */
/* Layout                                                              */
/* ------------------------------------------------------------------ */

type ScreenProps = {
  title: string;
  subtitle?: string;
  back?: boolean;
  right?: React.ReactNode;
  children: React.ReactNode;
  scroll?: boolean;
  refreshing?: boolean;
  onRefresh?: () => void;
  footer?: React.ReactNode;
  padded?: boolean;
};

export function Screen({ title, subtitle, back, right, children, scroll = true, refreshing, onRefresh, footer, padded = true }: ScreenProps) {
  const { c } = useTheme();
  return (
    <View style={{ flex: 1, backgroundColor: c.bg }}>
      <AppHeader title={title} subtitle={subtitle} back={back} right={right} />
      {scroll ? (
        <ScrollView
          contentContainerStyle={padded ? { padding: space.lg, paddingBottom: space.xxl * 2, gap: space.lg } : undefined}
          keyboardShouldPersistTaps="handled"
          refreshControl={onRefresh ? <RefreshControl refreshing={!! refreshing} onRefresh={onRefresh} tintColor={c.brand} /> : undefined}
        >
          {children}
        </ScrollView>
      ) : (
        <View style={{ flex: 1 }}>{children}</View>
      )}
      {footer}
    </View>
  );
}

export function Card({ children, style, onPress }: { children: React.ReactNode; style?: StyleProp<ViewStyle>; onPress?: () => void }) {
  const { c } = useTheme();
  const base = [styles.card, { backgroundColor: c.surface, borderColor: c.border }, style];
  if (onPress) {
    return <Pressable onPress={onPress} style={({ pressed }) => [...base, pressed && { opacity: 0.85, transform: [{ scale: 0.99 }] }]}>{children}</Pressable>;
  }
  return <View style={base}>{children}</View>;
}

export function SectionHeader({ title, icon, action, onAction }: { title: string; icon?: IconName; action?: string; onAction?: () => void }) {
  const { c } = useTheme();
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
        {icon ? <Icon name={icon} size={17} color={c.brand} /> : null}
        <Text style={[type.heading, { color: c.text }]}>{title}</Text>
      </View>
      {action && onAction ? <Pressable onPress={onAction} hitSlop={8}><Text style={[type.small, { color: c.brand, fontWeight: '700' }]}>{action}</Text></Pressable> : null}
    </View>
  );
}

/* ------------------------------------------------------------------ */
/* Controls                                                            */
/* ------------------------------------------------------------------ */

type ButtonProps = {
  title: string;
  onPress: () => void;
  icon?: IconName;
  variant?: 'primary' | 'accent' | 'soft' | 'ghost' | 'danger';
  loading?: boolean;
  disabled?: boolean;
  small?: boolean;
  style?: StyleProp<ViewStyle>;
};

export function Button({ title, onPress, icon, variant = 'primary', loading, disabled, small, style }: ButtonProps) {
  const { c } = useTheme();
  const palette = {
    primary: { bg: c.brand, fg: c.onBrand, border: c.brand },
    accent: { bg: c.accent, fg: '#12151C', border: c.accent },
    soft: { bg: c.brandSoft, fg: c.brandInk, border: c.brandSoft },
    ghost: { bg: 'transparent', fg: c.text, border: c.border },
    danger: { bg: c.dangerSoft, fg: c.danger, border: c.dangerSoft },
  }[variant];
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled || loading}
      accessibilityRole="button"
      style={({ pressed }) => [
        styles.btn, small && styles.btnSmall,
        { backgroundColor: palette.bg, borderColor: palette.border, opacity: disabled ? 0.5 : pressed ? 0.85 : 1 },
        style,
      ]}
    >
      {loading ? <ActivityIndicator color={palette.fg} /> : (
        <>
          {icon ? <Icon name={icon} size={small ? 15 : 18} color={palette.fg} /> : null}
          <Text style={[styles.btnText, small && { fontSize: 13 }, { color: palette.fg }]}>{title}</Text>
        </>
      )}
    </Pressable>
  );
}

export function Field({ label, icon, style, ...props }: TextInputProps & { label?: string; icon?: IconName }) {
  const { c } = useTheme();
  return (
    <View style={{ gap: 6 }}>
      {label ? <Text style={[type.small, { color: c.text2, fontWeight: '600' }]}>{label}</Text> : null}
      <View style={[styles.field, { backgroundColor: c.surface, borderColor: c.border }, props.multiline && { alignItems: 'flex-start', paddingVertical: 10 }]}>
        {icon ? <Icon name={icon} size={18} color={c.muted} /> : null}
        <TextInput placeholderTextColor={c.muted} style={[styles.input, { color: c.text }, props.multiline && { minHeight: 90, textAlignVertical: 'top' }, style]} {...props} />
      </View>
    </View>
  );
}

export function Chip({ label, active, onPress, icon }: { label: string; active?: boolean; onPress: () => void; icon?: IconName }) {
  const { c } = useTheme();
  return (
    <Pressable onPress={onPress} style={[styles.chip, { backgroundColor: active ? c.brand : c.surface, borderColor: active ? c.brand : c.border }]}>
      {icon ? <Icon name={icon} size={14} color={active ? c.onBrand : c.text2} /> : null}
      <Text style={{ color: active ? c.onBrand : c.text2, fontWeight: '600', fontSize: 13 }}>{label}</Text>
    </Pressable>
  );
}

export function Chips({ children }: { children: React.ReactNode }) {
  return <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8, paddingRight: 8 }}>{children}</ScrollView>;
}

export function Badge({ label, tone = 'brand', icon }: { label: string; tone?: 'brand' | 'accent' | 'success' | 'danger' | 'muted'; icon?: IconName }) {
  const { c } = useTheme();
  const t = {
    brand: [c.brandSoft, c.brandInk], accent: [c.accentSoft, c.accentInk], success: [c.successSoft, c.success],
    danger: [c.dangerSoft, c.danger], muted: [c.surface2, c.muted],
  }[tone] as [string, string];
  return (
    <View style={[styles.badge, { backgroundColor: t[0] }]}>
      {icon ? <Icon name={icon} size={12} color={t[1]} /> : null}
      <Text style={{ color: t[1], fontSize: 11, fontWeight: '700' }}>{label}</Text>
    </View>
  );
}

export function Progress({ value, height = 6 }: { value: number; height?: number }) {
  const { c } = useTheme();
  return (
    <View style={{ height, borderRadius: height, backgroundColor: c.surface2, overflow: 'hidden' }}>
      <View style={{ width: `${Math.max(0, Math.min(100, value))}%`, height, borderRadius: height, backgroundColor: c.accent }} />
    </View>
  );
}

export function IconTile({ icon, tone = 'brand', size = 44 }: { icon: IconName; tone?: 'brand' | 'accent' | 'success'; size?: number }) {
  const { c } = useTheme();
  const bg = tone === 'accent' ? c.accentSoft : tone === 'success' ? c.successSoft : c.brandSoft;
  const fg = tone === 'accent' ? c.accent : tone === 'success' ? c.success : c.brand;
  return <View style={{ width: size, height: size, borderRadius: size * 0.3, backgroundColor: bg, alignItems: 'center', justifyContent: 'center' }}><Icon name={icon} size={size * 0.45} color={fg} /></View>;
}

/* ------------------------------------------------------------------ */
/* States                                                              */
/* ------------------------------------------------------------------ */

export function Loading() {
  const { c } = useTheme();
  return <View style={styles.center}><ActivityIndicator size="large" color={c.brand} /></View>;
}

export function EmptyState({ icon, title, body, action, onAction }: { icon: IconName; title: string; body?: string; action?: string; onAction?: () => void }) {
  const { c } = useTheme();
  return (
    <View style={[styles.empty, { borderColor: c.border, backgroundColor: c.surface }]}>
      <IconTile icon={icon} size={56} />
      <Text style={[type.heading, { color: c.text, textAlign: 'center' }]}>{title}</Text>
      {body ? <Text style={[type.small, { color: c.muted, textAlign: 'center' }]}>{body}</Text> : null}
      {action && onAction ? <Button title={action} onPress={onAction} small /> : null}
    </View>
  );
}

export function ErrorState({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return <EmptyState icon="wifi-off" title="Couldn’t load this" body={message} action={onRetry ? 'Try again' : undefined} onAction={onRetry} />;
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: space.lg, paddingBottom: 10, borderBottomWidth: StyleSheet.hairlineWidth, gap: 4 },
  headerBtn: { marginLeft: -8, marginRight: 4, padding: 2 },
  headerTitle: { fontSize: 18, fontWeight: '800', letterSpacing: -0.2 },
  headerAction: { width: 38, height: 38, borderRadius: 19, alignItems: 'center', justifyContent: 'center', marginLeft: 8 },
  dot: { position: 'absolute', top: -3, right: -3, minWidth: 18, height: 18, borderRadius: 9, borderWidth: 2, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 3 },
  dotText: { fontSize: 9, fontWeight: '800', color: '#12151C' },
  card: { borderRadius: radius.lg, borderWidth: 1, padding: space.lg },
  sectionHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: -4 },
  btn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, minHeight: 50, borderRadius: radius.md, paddingHorizontal: 18, borderWidth: 1 },
  btnSmall: { minHeight: 38, paddingHorizontal: 14, borderRadius: radius.sm },
  btnText: { fontSize: 15, fontWeight: '700' },
  field: { flexDirection: 'row', alignItems: 'center', gap: 10, borderWidth: 1, borderRadius: radius.md, paddingHorizontal: 14, minHeight: 50 },
  input: { flex: 1, fontSize: 15, paddingVertical: 10 },
  chip: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 14, height: 36, borderRadius: radius.pill, borderWidth: 1 },
  badge: { flexDirection: 'row', alignItems: 'center', gap: 4, alignSelf: 'flex-start', paddingHorizontal: 8, paddingVertical: 3, borderRadius: radius.pill },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: space.xl },
  empty: { alignItems: 'center', gap: 10, padding: space.xl, borderRadius: radius.lg, borderWidth: 1, borderStyle: 'dashed' },
});
