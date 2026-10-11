import React, { useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, ScrollView, Share, Text, TextInput, View } from 'react-native';
import { useTheme } from '../context/ThemeContext';
import { api } from '../api/client';
import { JournalEntry } from '../api/types';
import { type } from '../theme/colors';
import Icon from '../components/Icon';
import { AppHeader, Button, Chip, Chips, Field } from '../components/ui';
import { KINDS } from './JournalScreen';

const PLACEHOLDERS: Record<string, string> = {
  prayer: 'Lord, I bring before you…',
  meditation: 'What is this Scripture saying to me today?',
  gratitude: 'Today I am thankful for…',
  note: 'Write freely…',
};

export default function JournalEntryScreen({ route, navigation }: any) {
  const existing: JournalEntry | undefined = route.params?.entry;
  const { c, readScale } = useTheme();
  const [kind, setKind] = useState<string>(existing?.kind ?? route.params?.kind ?? 'prayer');
  const [title, setTitle] = useState(existing?.title ?? '');
  const [body, setBody] = useState(existing?.body ?? route.params?.body ?? '');
  const [scripture, setScripture] = useState(existing?.scripture ?? route.params?.scripture ?? '');
  const [busy, setBusy] = useState(false);

  async function save() {
    if (! body.trim()) {
      Alert.alert('Write something first');
      return;
    }
    setBusy(true);
    try {
      const payload = { kind, title, body, scripture };
      if (existing) await api.post(`/journal/${existing.id}`, payload);
      else await api.post('/journal', payload);
      navigation.goBack();
    } catch (e: any) {
      Alert.alert('Could not save', e.message);
    } finally {
      setBusy(false);
    }
  }

  function remove() {
    if (! existing) return;
    Alert.alert('Delete this entry?', 'This cannot be undone.', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: async () => { await api.del(`/journal/${existing.id}`).catch(() => undefined); navigation.goBack(); } },
    ]);
  }

  const right = existing ? (
    <View style={{ flexDirection: 'row', gap: 14 }}>
      <Pressable hitSlop={8} onPress={() => Share.share({ message: `${title ? title + '\n\n' : ''}${body}${scripture ? `\n\n${scripture}` : ''}` })} accessibilityLabel="Share"><Icon name="share-2" size={20} color={c.text} /></Pressable>
      <Pressable hitSlop={8} onPress={remove} accessibilityLabel="Delete"><Icon name="trash-2" size={20} color={c.danger} /></Pressable>
    </View>
  ) : undefined;

  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: c.bg }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <AppHeader title={existing ? 'Edit entry' : 'New entry'} subtitle="Private to you" back right={right} />
      <ScrollView contentContainerStyle={{ padding: 16, gap: 16, paddingBottom: 40 }} keyboardShouldPersistTaps="handled">
        <Chips>
          {Object.entries(KINDS).map(([k, v]) => <Chip key={k} label={v.label} icon={v.icon} active={kind === k} onPress={() => setKind(k)} />)}
        </Chips>
        <Field placeholder="Title (optional)" value={title} onChangeText={setTitle} />
        <View style={{ backgroundColor: c.surface, borderColor: c.border, borderWidth: 1, borderRadius: 16, padding: 16, minHeight: 260 }}>
          <TextInput
            value={body}
            onChangeText={setBody}
            placeholder={PLACEHOLDERS[kind]}
            placeholderTextColor={c.muted}
            multiline
            autoFocus={! existing}
            style={{ color: c.text, fontFamily: type.serif, fontSize: 17 * readScale, lineHeight: 27 * readScale, minHeight: 220, textAlignVertical: 'top' }}
          />
        </View>
        <Field icon="book-open" placeholder="Scripture reference, e.g. James 5:16" value={scripture} onChangeText={setScripture} />
        {existing?.is_answered ? (
          <View style={{ flexDirection: 'row', gap: 10, backgroundColor: c.successSoft, padding: 14, borderRadius: 14 }}>
            <Icon name="check-circle" size={18} color={c.success} />
            <Text style={{ color: c.text, flex: 1 }}>Answered{existing.answered_at ? ` on ${existing.answered_at.slice(0, 10)}` : ''}{existing.answer_note ? ` — ${existing.answer_note}` : ''}</Text>
          </View>
        ) : null}
        <Button title={existing ? 'Save changes' : 'Save to journal'} icon="save" onPress={save} loading={busy} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
