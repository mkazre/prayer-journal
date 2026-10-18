import React from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { BlogArchive, BlogPostSummary } from '../api/types';
import { brand, type } from '../theme/colors';
import Icon from '../components/Icon';
import { Card, EmptyState, ErrorState, Loading, Screen } from '../components/ui';

/** A blog series such as Prayerthon: its intro and every published post. */
export default function BlogArchiveScreen({ route, navigation }: any) {
  const slug: string = route.params?.slug ?? 'prayerthon';
  const { c } = useTheme();
  const { data, error, loading, refreshing, refresh } = useApi<BlogArchive & { posts: BlogPostSummary[] }>(`/blog/${slug}`);
  const title = data?.title ?? route.params?.title ?? 'Blog';

  if (loading) return <Screen title={title} back><Loading /></Screen>;
  if (error || ! data) return <Screen title={title} back><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  return (
    <Screen title={title} subtitle="Series" back refreshing={refreshing} onRefresh={refresh}>
      <LinearGradient colors={['#0F1321', brand.navy]} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.hero}>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
          <Icon name="map" size={16} color={brand.gold} />
          <Text style={styles.eyebrow}>SERIES</Text>
        </View>
        <Text style={styles.heroTitle}>{data.title}</Text>
        {data.description ? <Text style={styles.heroText}>{data.description}</Text> : null}
      </LinearGradient>

      {data.posts.length === 0 ? (
        <EmptyState icon="feather" title="The first post is coming soon" body="Check back shortly." />
      ) : data.posts.map((p) => (
        <Card key={p.slug} style={{ padding: 0, overflow: 'hidden' }} onPress={() => navigation.navigate('BlogPost', { archive: slug, slug: p.slug })}>
          {p.image_url
            ? <Image source={{ uri: p.image_url }} style={styles.cover} />
            : <LinearGradient colors={[brand.navyDark, brand.navyLight]} style={[styles.cover, { alignItems: 'center', justifyContent: 'center' }]}><Icon name="map" size={30} color={brand.goldLight} /></LinearGradient>}
          <View style={{ padding: 16, gap: 6 }}>
            <Text style={[type.small, { color: c.muted }]}>
              {new Date(p.published_at.replace(' ', 'T')).toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' })}
              {p.resource_count ? `  ·  ${p.resource_count} resource${p.resource_count === 1 ? '' : 's'}` : ''}
            </Text>
            <Text style={[type.heading, { color: c.text }]}>{p.title}</Text>
            {p.excerpt ? <Text style={[type.body, { color: c.text2 }]} numberOfLines={3}>{p.excerpt}</Text> : null}
          </View>
        </Card>
      ))}
    </Screen>
  );
}

const styles = StyleSheet.create({
  hero: { borderRadius: 20, padding: 20, gap: 10 },
  eyebrow: { color: brand.gold, fontSize: 11, fontWeight: '800', letterSpacing: 1.2 },
  heroTitle: { color: '#fff', fontSize: 26, fontWeight: '800', fontFamily: type.serif },
  heroText: { color: 'rgba(255,255,255,0.85)', fontSize: 15, lineHeight: 22 },
  cover: { width: '100%', aspectRatio: 16 / 9 },
});
