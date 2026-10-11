import React, { useEffect, useState } from 'react';
import { Dimensions, Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../context/ThemeContext';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../hooks/useApi';
import { money } from '../api/client';
import { Cart, ProductSummary } from '../api/types';
import Icon from '../components/Icon';
import { Badge, Chip, Chips, EmptyState, ErrorState, Field, HeaderAction, Loading, Screen } from '../components/ui';

const CARD_W = (Dimensions.get('window').width - 16 * 2 - 12) / 2;

export default function ShopScreen({ navigation }: any) {
  const { c } = useTheme();
  const { config } = useAuth();
  const [sort, setSort] = useState('featured');
  const [q, setQ] = useState('');
  const [debounced, setDebounced] = useState('');
  useEffect(() => { const t = setTimeout(() => setDebounced(q), 350); return () => clearTimeout(t); }, [q]);
  const { data, error, loading, refreshing, refresh } = useApi<ProductSummary[]>(`/shop/products?sort=${sort}&q=${encodeURIComponent(debounced)}`);
  const cart = useApi<Cart>('/cart');
  const currency = config?.currency ?? 'ZAR';

  const right = <HeaderAction icon="shopping-cart" label="Cart" badge={cart.data?.count} onPress={() => navigation.navigate('Cart')} />;

  return (
    <Screen title="Shop" subtitle="Journals, books and gifts" right={right} refreshing={refreshing} onRefresh={refresh}>
      <Field icon="search" placeholder="Search the shop" value={q} onChangeText={setQ} returnKeyType="search" />
      <Chips>
        {[['featured', 'Featured'], ['newest', 'Newest'], ['price_asc', 'Price: low to high'], ['price_desc', 'Price: high to low'], ['best', 'Best selling'], ['rating', 'Top rated']].map(([k, l]) => (
          <Chip key={k} label={l} active={sort === k} onPress={() => setSort(k)} />
        ))}
      </Chips>
      {loading ? <Loading /> : error ? <ErrorState message={error} onRetry={refresh} /> : ! data?.length ? (
        <EmptyState icon="shopping-bag" title="Nothing found" body="Try another search." />
      ) : (
        <View style={styles.grid}>
          {data.map((p) => {
            const sale = p.compare_at_cents && p.compare_at_cents > p.price_cents;
            return (
              <Pressable key={p.id} onPress={() => navigation.navigate('Product', { slug: p.slug })} style={({ pressed }) => [styles.card, { backgroundColor: c.surface, borderColor: c.border, opacity: pressed ? 0.85 : 1 }]}>
                <View style={[styles.image, { backgroundColor: c.surface2 }]}>
                  {p.image_url ? <Image source={{ uri: p.image_url }} style={StyleSheet.absoluteFill} resizeMode="cover" /> : <Icon name="package" size={30} color={c.muted} />}
                  <View style={{ position: 'absolute', top: 8, left: 8, gap: 4 }}>
                    {sale ? <Badge label="Sale" tone="danger" /> : null}
                    {p.product_type === 'digital' ? <Badge label="Download" icon="download" /> : null}
                  </View>
                </View>
                <View style={{ padding: 12, gap: 4 }}>
                  <Text style={{ color: c.text, fontWeight: '700', fontSize: 14 }} numberOfLines={2}>{p.title}</Text>
                  {p.rating ? <View style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}><Icon name="star" size={12} color={c.accent} /><Text style={{ color: c.muted, fontSize: 12 }}>{p.rating.toFixed(1)}</Text></View> : null}
                  <View style={{ flexDirection: 'row', alignItems: 'baseline', gap: 6 }}>
                    <Text style={{ color: c.text, fontWeight: '800', fontSize: 15 }}>{money(p.price_cents, currency)}</Text>
                    {sale ? <Text style={{ color: c.muted, fontSize: 12, textDecorationLine: 'line-through' }}>{money(p.compare_at_cents!, currency)}</Text> : null}
                  </View>
                  {! p.in_stock ? <Text style={{ color: c.danger, fontSize: 12, fontWeight: '600' }}>Sold out</Text> : null}
                </View>
              </Pressable>
            );
          })}
        </View>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  card: { width: CARD_W, borderWidth: 1, borderRadius: 16, overflow: 'hidden' },
  image: { width: '100%', aspectRatio: 4 / 5, alignItems: 'center', justifyContent: 'center' },
});
