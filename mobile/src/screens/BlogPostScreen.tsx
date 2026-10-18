import React from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { useAuth } from '../context/AuthContext';
import { useTheme } from '../context/ThemeContext';
import { useApi } from '../hooks/useApi';
import { BlogPost, BlogResource } from '../api/types';
import { type } from '../theme/colors';
import Icon, { IconName } from '../components/Icon';
import Html from '../components/Html';
import { Badge, Button, Card, ErrorState, IconTile, Loading, Screen, SectionHeader } from '../components/ui';

const KIND: Record<BlogResource['kind'], [IconName, string]> = {
  pdf: ['file-text', 'PDF'], image: ['image', 'Image'], video: ['film', 'Video'], audio: ['headphones', 'Audio'], file: ['paperclip', 'File'],
};

const fileSize = (b: number) => (b >= 1048576 ? `${(b / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(b / 1024))} KB`);

/** A single blog post with its free resources (gated by the post's access level). */
export default function BlogPostScreen({ route }: any) {
  const { archive, slug } = route.params ?? {};
  const { c } = useTheme();
  const { config } = useAuth();
  const { data, error, loading, refresh } = useApi<BlogPost>(`/blog/${archive}/${slug}`);

  if (loading) return <Screen title="Loading…" back><Loading /></Screen>;
  if (error || ! data) return <Screen title="Post" back><ErrorState message={error ?? ''} onRetry={refresh} /></Screen>;

  // Signed links expire after two hours; refresh first if the screen has been open a long time.
  const openResource = (r: BlogResource) => r.url && WebBrowser.openBrowserAsync(r.url);

  return (
    <Screen title={data.archive.title} subtitle={data.title} back>
      {data.image_url ? <Image source={{ uri: data.image_url }} style={styles.feature} /> : null}
      <View style={{ gap: 6 }}>
        <Text style={[type.title, { color: c.text, fontFamily: type.serif }]}>{data.title}</Text>
        <Text style={[type.small, { color: c.muted }]}>{new Date(data.published_at.replace(' ', 'T')).toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' })}</Text>
      </View>
      {data.excerpt ? <Text style={[type.body, { color: c.text2, fontSize: 17, lineHeight: 26 }]}>{data.excerpt}</Text> : null}

      {data.can_read ? (
        <>
          {data.body ? <Html html={data.body} /> : null}
          {data.resources.length ? (
            <>
              <SectionHeader title="Free resources" icon="gift" />
              {data.resources.map((r) => (
                <Card key={r.id} onPress={() => openResource(r)} style={{ flexDirection: 'row', alignItems: 'center', gap: 14 }}>
                  <IconTile icon={KIND[r.kind][0]} tone={r.kind === 'pdf' || r.kind === 'audio' ? 'accent' : 'brand'} />
                  <View style={{ flex: 1, gap: 2 }}>
                    <Text style={{ color: c.text, fontWeight: '700' }}>{r.title}</Text>
                    <Text style={[type.small, { color: c.muted }]}>{KIND[r.kind][1]} · {fileSize(r.size_bytes)}</Text>
                  </View>
                  <Icon name={r.kind === 'video' || r.kind === 'audio' ? 'play-circle' : 'download'} size={22} color={c.brand} />
                </Card>
              ))}
            </>
          ) : null}
        </>
      ) : (
        <Card style={{ alignItems: 'center', gap: 10, paddingVertical: 28 }}>
          <IconTile icon="lock" tone="accent" size={56} />
          <Text style={[type.heading, { color: c.text, textAlign: 'center' }]}>This post is free for our customers</Text>
          <Text style={[type.small, { color: c.text2, textAlign: 'center' }]}>
            Buy any product, a course or a membership and you'll unlock it{data.resources.length ? ` — with all ${data.resources.length} resource${data.resources.length === 1 ? '' : 's'}` : ''}.
          </Text>
          <Button title="See membership" icon="star" onPress={() => WebBrowser.openBrowserAsync(`${config?.web_url ?? ''}pricing`).then(refresh)} />
          {data.resources.map((r) => <Badge key={r.id} label={r.title} icon={KIND[r.kind][0]} tone="muted" />)}
        </Card>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  feature: { width: '100%', aspectRatio: 16 / 9, borderRadius: 18 },
});
