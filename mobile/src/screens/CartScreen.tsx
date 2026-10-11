import React from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { api, money } from '../api/client';
import { Cart } from '../api/types';
import Icon from '../components/Icon';
import { Button, Card, EmptyState, ErrorState, Loading, Screen } from '../components/ui';

export default function CartScreen({ navigation }: any) {
  const { c } = useTheme();
  const insets = useSafeAreaInsets();
  const { data, error, loading, refresh, setData } = useApi<Cart>('/cart');

  async function setQty(itemId: number, quantity: number) {
    const updated = await api.post<Cart>(`/cart/items/${itemId}`, { quantity }).catch(() => null);
    if (updated) setData(updated);
  }

  if (loading) return <Screen title="Your cart" back><Loading /></Screen>;
  if (error || ! data) return <Screen title="Your cart" back><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  const footer = data.items.length ? (
    <View style={[styles.footer, { backgroundColor: c.surface, borderTopColor: c.border, paddingBottom: insets.bottom + 12 }]}>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
        <Text style={{ color: c.text2 }}>Subtotal</Text>
        <Text style={{ color: c.text, fontWeight: '800', fontSize: 18 }}>{money(data.subtotal_cents, data.currency)}</Text>
      </View>
      <Text style={{ color: c.muted, fontSize: 12 }}>Shipping, discounts and tax are calculated at checkout.</Text>
      <Button title="Secure checkout" icon="lock" variant="accent" onPress={() => WebBrowser.openBrowserAsync(data.checkout_url).then(refresh)} />
    </View>
  ) : undefined;

  return (
    <Screen title="Your cart" subtitle={data.count ? `${data.count} item${data.count === 1 ? '' : 's'}` : undefined} back footer={footer}>
      {! data.items.length ? (
        <EmptyState icon="shopping-cart" title="Your cart is empty" body="Journals, books and gifts for your walk with God." action="Browse the shop" onAction={() => navigation.navigate('Tabs', { screen: 'Shop' })} />
      ) : data.items.map((i) => (
        <Card key={i.id} style={{ flexDirection: 'row', gap: 12, alignItems: 'center' }}>
          <Pressable onPress={() => navigation.navigate('Product', { slug: i.slug })}>
            {i.image_url ? <Image source={{ uri: i.image_url }} style={styles.thumb} /> : <View style={[styles.thumb, { backgroundColor: c.surface2, alignItems: 'center', justifyContent: 'center' }]}><Icon name="package" size={22} color={c.muted} /></View>}
          </Pressable>
          <View style={{ flex: 1, gap: 4 }}>
            <Text style={{ color: c.text, fontWeight: '700' }} numberOfLines={2}>{i.title}</Text>
            {i.variant ? <Text style={{ color: c.muted, fontSize: 12 }}>{i.variant}</Text> : null}
            <Text style={{ color: c.text, fontWeight: '800' }}>{money(i.unit_price_cents * i.quantity, data.currency)}</Text>
          </View>
          <View style={{ alignItems: 'center', gap: 8 }}>
            <View style={[styles.qty, { borderColor: c.border }]}>
              <Pressable onPress={() => setQty(i.id, i.quantity - 1)} hitSlop={8} accessibilityLabel="Decrease"><Icon name={i.quantity === 1 ? 'trash-2' : 'minus'} size={15} color={i.quantity === 1 ? c.danger : c.text} /></Pressable>
              <Text style={{ color: c.text, fontWeight: '800' }}>{i.quantity}</Text>
              <Pressable onPress={() => setQty(i.id, i.quantity + 1)} hitSlop={8} accessibilityLabel="Increase"><Icon name="plus" size={15} color={c.text} /></Pressable>
            </View>
          </View>
        </Card>
      ))}
      {data.items.length ? (
        <View style={{ flexDirection: 'row', gap: 10, alignItems: 'center' }}>
          <Icon name="info" size={15} color={c.muted} />
          <Text style={{ color: c.muted, fontSize: 12, flex: 1 }}>Checkout opens securely on the website. Sign in with the same account and your cart will be waiting.</Text>
        </View>
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  thumb: { width: 64, height: 80, borderRadius: 10 },
  qty: { flexDirection: 'row', alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 10, paddingHorizontal: 10, height: 36 },
  footer: { padding: 16, gap: 8, borderTopWidth: StyleSheet.hairlineWidth },
});
