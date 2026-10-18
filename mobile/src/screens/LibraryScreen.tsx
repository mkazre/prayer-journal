import React from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { LibraryItem } from '../api/types';
import { type } from '../theme/colors';
import Icon from '../components/Icon';
import { Badge, Button, Card, EmptyState, ErrorState, Loading, Screen } from '../components/ui';

/**
 * Digital products the customer has bought. Flipbook books open in the
 * in-app browser already signed in (the link is signed and short-lived).
 */
export default function LibraryScreen({ navigation }: any) {
  const { c } = useTheme();
  const { data, error, loading, refreshing, refresh, reload } = useApi<LibraryItem[]>('/library');

  if (loading) return <Screen title="My library" back><Loading /></Screen>;
  if (error || ! data) return <Screen title="My library" back><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  // Links expire after two hours, so fetch fresh ones on every open.
  const open = async (pick: (i: LibraryItem) => string | null, item: LibraryItem) => {
    reload();
    const url = pick(item);
    if (url) await WebBrowser.openBrowserAsync(url);
  };

  return (
    <Screen title="My library" subtitle="Books & downloads you own" back refreshing={refreshing} onRefresh={refresh}>
      {data.length === 0 ? (
        <EmptyState icon="book" title="Nothing here yet" body="E-books and downloads you buy in the shop appear here." action="Browse the shop" onAction={() => navigation.navigate('Tabs', { screen: 'Shop' })} />
      ) : data.map((i) => (
        <Card key={i.item_id} style={{ flexDirection: 'row', gap: 14 }}>
          {i.image_url
            ? <Image source={{ uri: i.image_url }} style={styles.cover} />
            : <View style={[styles.cover, { backgroundColor: c.brandSoft, alignItems: 'center', justifyContent: 'center' }]}><Icon name="book" size={26} color={c.brand} /></View>}
          <View style={{ flex: 1, gap: 8 }}>
            <Text style={[type.heading, { color: c.text }]} numberOfLines={2}>{i.title}</Text>
            <View style={{ flexDirection: 'row', gap: 6, flexWrap: 'wrap' }}>
              {i.read_url ? <Badge label="Flipbook" icon="book-open" /> : null}
              {i.download_url ? <Badge label="Download" icon="download" tone="muted" /> : null}
            </View>
            <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }}>
              {i.read_url ? <Button small title="Read" icon="book-open" onPress={() => open((x) => x.read_url, i)} /> : null}
              {i.download_url ? <Button small variant="soft" title="Download" icon="download" onPress={() => open((x) => x.download_url, i)} /> : null}
            </View>
            <Text style={[type.small, { color: c.muted }]}>Order {i.order_number}</Text>
          </View>
        </Card>
      ))}
    </Screen>
  );
}

const styles = StyleSheet.create({
  cover: { width: 74, height: 100, borderRadius: 10 },
});
