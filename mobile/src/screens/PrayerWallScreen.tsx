import React, { useState } from 'react';
import { Alert, Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import * as Haptics from 'expo-haptics';
import { useTheme } from '../context/ThemeContext';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../hooks/useApi';
import { api } from '../api/client';
import { PrayerRequest } from '../api/types';
import { type } from '../theme/colors';
import Icon from '../components/Icon';
import { Badge, Button, Card, Chip, Chips, EmptyState, ErrorState, Field, Loading, Screen } from '../components/ui';

function ago(d: string): string {
  const s = (Date.now() - new Date(d.replace(' ', 'T')).getTime()) / 1000;
  if (s < 3600) return `${Math.max(1, Math.round(s / 60))}m ago`;
  if (s < 86400) return `${Math.round(s / 3600)}h ago`;
  return `${Math.round(s / 86400)}d ago`;
}

export default function PrayerWallScreen() {
  const { c } = useTheme();
  const { config } = useAuth();
  const categories = config?.prayer_categories ?? { general: 'General' };
  const [category, setCategory] = useState('');
  const [answered, setAnswered] = useState(false);
  const { data, error, loading, refreshing, refresh, setData, reload } = useApi<PrayerRequest[]>(`/prayer-requests?category=${category}&status=${answered ? 'answered' : ''}`);
  const [composing, setComposing] = useState(false);
  const [body, setBody] = useState('');
  const [newCat, setNewCat] = useState('general');
  const [anon, setAnon] = useState(false);
  const [busy, setBusy] = useState(false);

  async function pray(r: PrayerRequest) {
    if (r.i_prayed || ! data) return;
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light).catch(() => undefined);
    setData(data.map((x) => (x.id === r.id ? { ...x, i_prayed: true, prayer_count: x.prayer_count + 1 } : x)));
    await api.post(`/prayer-requests/${r.id}/pray`).catch(() => undefined);
  }

  async function share() {
    setBusy(true);
    try {
      await api.post('/prayer-requests', { body, category: newCat, is_anonymous: anon });
      setBody('');
      setComposing(false);
      reload();
    } catch (e: any) {
      Alert.alert('Could not share', e.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <Screen title="Prayer wall" subtitle="Carry each other’s burdens — Gal 6:2" back refreshing={refreshing} onRefresh={refresh}>
      {composing ? (
        <Card style={{ gap: 12 }}>
          <Field multiline placeholder="What would you like the community to pray about?" value={body} onChangeText={setBody} maxLength={2000} />
          <Chips>
            {Object.entries(categories).map(([k, v]) => <Chip key={k} label={v} active={newCat === k} onPress={() => setNewCat(k)} />)}
          </Chips>
          <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
            <Text style={{ color: c.text2 }}>Post anonymously</Text>
            <Switch value={anon} onValueChange={setAnon} trackColor={{ true: c.brand, false: c.border }} />
          </View>
          <View style={{ flexDirection: 'row', gap: 10 }}>
            <Button title="Cancel" variant="ghost" onPress={() => setComposing(false)} style={{ flex: 1 }} />
            <Button title="Share" icon="send" onPress={share} loading={busy} disabled={body.trim().length < 5} style={{ flex: 1 }} />
          </View>
        </Card>
      ) : (
        <Button title="Share a prayer request" icon="plus" onPress={() => setComposing(true)} />
      )}

      <Chips>
        <Chip label="All" active={! category && ! answered} onPress={() => { setCategory(''); setAnswered(false); }} />
        <Chip label="Answered" icon="check-circle" active={answered} onPress={() => setAnswered(! answered)} />
        {Object.entries(categories).map(([k, v]) => <Chip key={k} label={v} active={category === k} onPress={() => setCategory(category === k ? '' : k)} />)}
      </Chips>

      {loading ? <Loading /> : error ? <ErrorState message={error} onRetry={refresh} /> : ! data?.length ? (
        <EmptyState icon="users" title="No requests here yet" body="Be the first to share — the community will pray with you." />
      ) : data.map((r) => (
        <View key={r.id} style={[styles.card, { backgroundColor: c.surface, borderColor: r.is_answered ? c.success : c.border }]}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
            <View style={[styles.avatar, { backgroundColor: c.brandSoft }]}>
              {r.author === 'Anonymous' ? <Icon name="user" size={16} color={c.brand} /> : <Text style={{ color: c.brand, fontWeight: '800' }}>{r.author.slice(0, 1).toUpperCase()}</Text>}
            </View>
            <View style={{ flex: 1 }}>
              <Text style={{ color: c.text, fontWeight: '700' }}>{r.author}{r.is_mine ? ' (you)' : ''}</Text>
              <Text style={{ color: c.muted, fontSize: 12 }}>{ago(r.created_at)} · {categories[r.category] ?? 'General'}</Text>
            </View>
            {r.is_answered ? <Badge label="Answered" icon="check" tone="success" /> : null}
          </View>
          <Text style={{ color: c.text, fontFamily: type.serif, fontSize: 16, lineHeight: 25 }}>{r.body}</Text>
          {r.answer_note ? (
            <View style={{ backgroundColor: c.successSoft, borderRadius: 12, padding: 12, flexDirection: 'row', gap: 8 }}>
              <Icon name="message-circle" size={16} color={c.success} />
              <Text style={{ color: c.text, fontStyle: 'italic', flex: 1 }}>{r.answer_note}</Text>
            </View>
          ) : null}
          <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
            <Pressable onPress={() => pray(r)} disabled={r.i_prayed} style={[styles.pray, { backgroundColor: r.i_prayed ? c.brandSoft : c.brand }]}>
              <Icon name={r.i_prayed ? 'check' : 'heart'} size={15} color={r.i_prayed ? c.brandInk : '#fff'} />
              <Text style={{ color: r.i_prayed ? c.brandInk : '#fff', fontWeight: '700', fontSize: 13 }}>{r.i_prayed ? 'You prayed' : 'I prayed'}</Text>
            </Pressable>
            <Text style={{ color: c.muted, fontSize: 13 }}>{r.prayer_count} {r.prayer_count === 1 ? 'prayer' : 'prayers'}</Text>
          </View>
        </View>
      ))}
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { borderWidth: 1, borderRadius: 18, padding: 16, gap: 12 },
  avatar: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  pray: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 14, height: 36, borderRadius: 999 },
});
