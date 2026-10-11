import React, { useMemo, useState } from 'react';
import { Alert, Dimensions, Image, Pressable, ScrollView, Share, StyleSheet, Text, View } from 'react-native';
import * as Haptics from 'expo-haptics';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../context/ThemeContext';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../hooks/useApi';
import { api, money } from '../api/client';
import { Product } from '../api/types';
import Icon from '../components/Icon';
import Html from '../components/Html';
import { Button, Card, ErrorState, HeaderAction, Loading, Screen, SectionHeader } from '../components/ui';

const W = Dimensions.get('window').width;

export default function ProductScreen({ route, navigation }: any) {
  const { slug } = route.params;
  const { c } = useTheme();
  const { config } = useAuth();
  const insets = useSafeAreaInsets();
  const { data, error, loading, refresh, setData } = useApi<Product>(`/shop/products/${slug}`, { refetchOnFocus: false });
  const [picked, setPicked] = useState<Record<number, string>>({});
  const [qty, setQty] = useState(1);
  const [image, setImage] = useState(0);
  const [busy, setBusy] = useState(false);
  const currency = config?.currency ?? 'ZAR';

  const variant = useMemo(() => {
    if (! data) return null;
    if (data.variants.length === 1 || ! data.options.length) return data.variants[0] ?? null;
    return data.variants.find((v) => data.options.every((_, i) => ! picked[i] || v.options[i] === picked[i])) ?? null;
  }, [data, picked]);

  if (loading) return <Screen title="Product" back><Loading /></Screen>;
  if (error || ! data) return <Screen title="Product" back><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  const needsChoice = data.options.length > 0 && data.options.some((_, i) => ! picked[i]);

  async function add() {
    if (! variant) return;
    setBusy(true);
    try {
      await api.post(`/cart/add/${variant.id}`, { quantity: qty });
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success).catch(() => undefined);
      Alert.alert('Added to cart', `${data!.title}${qty > 1 ? ` × ${qty}` : ''}`, [
        { text: 'Keep shopping', style: 'cancel' },
        { text: 'View cart', onPress: () => navigation.navigate('Cart') },
      ]);
    } catch (e: any) {
      Alert.alert('Could not add', e.message);
    } finally {
      setBusy(false);
    }
  }

  async function wishlist() {
    if (! data) return;
    const res = await api.post<{ wishlisted: boolean }>(`/shop/products/${data.id}/wishlist`).catch(() => null);
    if (res) setData({ ...data, wishlisted: res.wishlisted });
  }

  const sale = variant?.compare_at_cents && variant.compare_at_cents > variant.price_cents;
  const avg = data.reviews.length ? data.reviews.reduce((a, r) => a + r.rating, 0) / data.reviews.length : 0;

  const right = (
    <>
      <HeaderAction icon="share-2" label="Share" onPress={() => Share.share({ message: `${data.title} — ${data.web_url}` })} />
      <HeaderAction icon="heart" label={data.wishlisted ? 'Remove from wishlist' : 'Add to wishlist'} onPress={wishlist} badge={data.wishlisted ? 1 : undefined} />
    </>
  );

  const footer = (
    <View style={[styles.footer, { backgroundColor: c.surface, borderTopColor: c.border, paddingBottom: insets.bottom + 10 }]}>
      <View style={[styles.qty, { borderColor: c.border }]}>
        <Pressable onPress={() => setQty(Math.max(1, qty - 1))} hitSlop={8} accessibilityLabel="Decrease"><Icon name="minus" size={18} color={c.text} /></Pressable>
        <Text style={{ color: c.text, fontWeight: '800', minWidth: 22, textAlign: 'center' }}>{qty}</Text>
        <Pressable onPress={() => setQty(Math.min(99, qty + 1))} hitSlop={8} accessibilityLabel="Increase"><Icon name="plus" size={18} color={c.text} /></Pressable>
      </View>
      <Button
        title={needsChoice ? 'Choose options' : ! variant?.in_stock ? 'Sold out' : 'Add to cart'}
        icon="shopping-cart" onPress={add} loading={busy} disabled={needsChoice || ! variant?.in_stock} style={{ flex: 1 }}
      />
    </View>
  );

  return (
    <Screen title={data.title} back right={right} footer={footer} padded={false}>
      <ScrollView horizontal pagingEnabled showsHorizontalScrollIndicator={false} onMomentumScrollEnd={(e) => setImage(Math.round(e.nativeEvent.contentOffset.x / W))}>
        {(data.images.length ? data.images : [{ url: '', alt: '' }]).map((img, i) => (
          <View key={i} style={{ width: W, aspectRatio: 1, backgroundColor: c.surface2, alignItems: 'center', justifyContent: 'center' }}>
            {img.url ? <Image source={{ uri: img.url }} style={StyleSheet.absoluteFill} resizeMode="contain" accessibilityLabel={img.alt} /> : <Icon name="package" size={48} color={c.muted} />}
          </View>
        ))}
      </ScrollView>
      {data.images.length > 1 ? (
        <View style={styles.dots}>{data.images.map((_, i) => <View key={i} style={[styles.dot, { backgroundColor: i === image ? c.brand : c.border }]} />)}</View>
      ) : null}

      <View style={{ padding: 16, gap: 16 }}>
        <View style={{ gap: 6 }}>
          {data.brand ? <Text style={{ color: c.brand, fontWeight: '700', fontSize: 12, letterSpacing: 0.8 }}>{data.brand.toUpperCase()}</Text> : null}
          <Text style={{ color: c.text, fontSize: 24, fontWeight: '800' }}>{data.title}</Text>
          {data.reviews.length ? (
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}>
              {[1, 2, 3, 4, 5].map((n) => <Icon key={n} name="star" size={14} color={n <= Math.round(avg) ? c.accent : c.border} />)}
              <Text style={{ color: c.muted, fontSize: 12, marginLeft: 4 }}>{avg.toFixed(1)} · {data.reviews.length} reviews</Text>
            </View>
          ) : null}
          <View style={{ flexDirection: 'row', alignItems: 'baseline', gap: 10, marginTop: 4 }}>
            <Text style={{ color: c.text, fontSize: 26, fontWeight: '800' }}>{variant ? money(variant.price_cents, currency) : '—'}</Text>
            {sale ? <Text style={{ color: c.muted, fontSize: 16, textDecorationLine: 'line-through' }}>{money(variant!.compare_at_cents!, currency)}</Text> : null}
          </View>
          {data.short_description ? <Text style={{ color: c.text2, fontSize: 15, lineHeight: 22 }}>{data.short_description}</Text> : null}
        </View>

        {data.options.map((o, i) => (
          <View key={o.name} style={{ gap: 8 }}>
            <Text style={{ color: c.text, fontWeight: '700' }}>{o.name}{picked[i] ? `: ${picked[i]}` : ''}</Text>
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
              {o.values.map((v) => (
                <Pressable key={v} onPress={() => setPicked({ ...picked, [i]: v })} style={[styles.opt, { borderColor: picked[i] === v ? c.brand : c.border, backgroundColor: picked[i] === v ? c.brandSoft : c.surface }]}>
                  <Text style={{ color: c.text, fontWeight: '600' }}>{v}</Text>
                </Pressable>
              ))}
            </View>
          </View>
        ))}

        <View style={{ flexDirection: 'row', gap: 10 }}>
          <Assurance icon={data.product_type === 'digital' ? 'download' : 'truck'} text={data.product_type === 'digital' ? 'Instant download' : 'Tracked delivery'} />
          <Assurance icon="shield" text="Secure checkout" />
        </View>

        {data.description_html ? (
          <Card>
            <SectionHeader title="Details" icon="info" />
            <View style={{ marginTop: 12 }}><Html html={data.description_html} serif={false} /></View>
          </Card>
        ) : null}

        {data.reviews.length ? (
          <Card style={{ gap: 14 }}>
            <SectionHeader title="Reviews" icon="message-square" />
            {data.reviews.slice(0, 5).map((r, i) => (
              <View key={i} style={{ gap: 4 }}>
                <View style={{ flexDirection: 'row', gap: 2 }}>{[1, 2, 3, 4, 5].map((n) => <Icon key={n} name="star" size={12} color={n <= r.rating ? c.accent : c.border} />)}</View>
                {r.title ? <Text style={{ color: c.text, fontWeight: '700' }}>{r.title}</Text> : null}
                <Text style={{ color: c.text2 }}>{r.body}</Text>
                <Text style={{ color: c.muted, fontSize: 12 }}>{r.author}</Text>
              </View>
            ))}
          </Card>
        ) : null}
      </View>
    </Screen>
  );
}

function Assurance({ icon, text }: { icon: any; text: string }) {
  const { c } = useTheme();
  return (
    <View style={{ flex: 1, flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: c.surface, borderColor: c.border, borderWidth: 1, borderRadius: 12, padding: 12 }}>
      <Icon name={icon} size={16} color={c.brand} />
      <Text style={{ color: c.text2, fontSize: 12, fontWeight: '600', flex: 1 }}>{text}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  dots: { flexDirection: 'row', justifyContent: 'center', gap: 6, marginTop: 10 },
  dot: { width: 7, height: 7, borderRadius: 4 },
  opt: { borderWidth: 1.5, borderRadius: 10, paddingHorizontal: 14, paddingVertical: 9 },
  footer: { flexDirection: 'row', gap: 10, alignItems: 'center', paddingHorizontal: 16, paddingTop: 10, borderTopWidth: StyleSheet.hairlineWidth },
  qty: { flexDirection: 'row', alignItems: 'center', gap: 14, borderWidth: 1, borderRadius: 12, height: 50, paddingHorizontal: 14 },
});
