import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, TextInput } from 'react-native';
import { api } from '../api/client';
import { colors } from '../theme/colors';

type Book = { id: number; name: string; abbrev: string; testament: 'OT' | 'NT' };

export default function BibleScreen({ navigation }: any) {
  const [books, setBooks] = useState<Book[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    api.get<Book[]>('/bible/books', false).then(setBooks).finally(() => setLoading(false));
  }, []);

  const filtered = books.filter((b) => b.name.toLowerCase().includes(search.toLowerCase()));

  if (loading) {
    return <ActivityIndicator style={styles.loader} size="large" color={colors.purple} />;
  }

  return (
    <View style={styles.container}>
      <TextInput style={styles.search} placeholder="Search books..." value={search} onChangeText={setSearch} />
      <FlatList
        contentContainerStyle={{ padding: 16 }}
        data={filtered}
        numColumns={3}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={styles.bookBtn}
            onPress={() => navigation.navigate('BibleChapter', { abbrev: item.abbrev, chapter: 1, name: item.name })}
          >
            <Text style={styles.bookText}>{item.name}</Text>
          </TouchableOpacity>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg },
  loader: { flex: 1, backgroundColor: colors.bg },
  search: { margin: 16, marginBottom: 0, borderWidth: 1, borderColor: colors.border, borderRadius: 10, padding: 12, backgroundColor: colors.white },
  bookBtn: { flex: 1, margin: 4, backgroundColor: colors.white, borderRadius: 10, padding: 12, alignItems: 'center', borderWidth: 1, borderColor: colors.border },
  bookText: { color: colors.purpleDark, fontWeight: '600', fontSize: 12, textAlign: 'center' },
});
