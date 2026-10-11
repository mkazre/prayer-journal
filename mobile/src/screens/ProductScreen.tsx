import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

type Variant = { id: number; option_name: string | null; price_cents: number };
type Product = { id: number; title: string; description: string; variants: Variant[] };

export default function ProductScreen({ route }: any) {
  const { slug } = route.params;
  const { user } = useAuth();
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get<Product>(`/shop/products/${slug}`, false).then(setProduct).finally(() => setLoading(false));
  }, [slug]);

  async function addToCart(variantId: number) {
    if (! user) {
      Alert.alert('Please log in', 'You need an account to add items to your cart.');
      return;
    }
    try {
      await api.post(`/cart/add/${variantId}`, { quantity: 1 });
      Alert.alert('Added', 'Item added to your cart.');
    } catch (e: any) {
      Alert.alert('Error', e.message);
    }
  }

  if (loading || ! product) {
    return <ActivityIndicator style={styles.loader} size="large" color={colors.purple} />;
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 20 }}>
      <Text style={styles.title}>{product.title}</Text>
      <Text style={styles.description}>{product.description}</Text>

      {product.variants.map((v) => (
        <TouchableOpacity key={v.id} style={styles.variantRow} onPress={() => addToCart(v.id)}>
          <Text style={styles.variantName}>{v.option_name || 'Default'}</Text>
          <Text style={styles.variantPrice}>${(v.price_cents / 100).toFixed(2)} · Add to Cart</Text>
        </TouchableOpacity>
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg },
  loader: { flex: 1, backgroundColor: colors.bg },
  title: { fontSize: 22, fontWeight: '700', color: colors.purpleDark },
  description: { color: colors.gray, marginTop: 8, marginBottom: 20, lineHeight: 20 },
  variantRow: {
    backgroundColor: colors.white, borderRadius: 10, padding: 16, marginBottom: 10,
    borderWidth: 1, borderColor: colors.border, flexDirection: 'row', justifyContent: 'space-between',
  },
  variantName: { color: colors.text, fontWeight: '600' },
  variantPrice: { color: colors.purple, fontWeight: '600' },
});
