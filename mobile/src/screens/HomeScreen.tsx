import React from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function HomeScreen({ navigation }: any) {
  const { user } = useAuth();

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 20 }}>
      <Text style={styles.greeting}>Welcome, {user?.username} 👋</Text>
      <Text style={styles.sub}>What would you like to do today?</Text>

      <View style={styles.grid}>
        <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('CoursesTab')}>
          <Text style={styles.cardEmoji}>📚</Text>
          <Text style={styles.cardTitle}>Study</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('BibleTab')}>
          <Text style={styles.cardEmoji}>📖</Text>
          <Text style={styles.cardTitle}>Read the Bible</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('ShopTab')}>
          <Text style={styles.cardEmoji}>🛍️</Text>
          <Text style={styles.cardTitle}>Shop</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('ProfileTab')}>
          <Text style={styles.cardEmoji}>👤</Text>
          <Text style={styles.cardTitle}>My Profile</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg },
  greeting: { fontSize: 24, fontWeight: '700', color: colors.purpleDark },
  sub: { fontSize: 15, color: colors.gray, marginTop: 4, marginBottom: 20 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  card: {
    width: '47%', backgroundColor: colors.white, borderRadius: 14, padding: 20,
    borderWidth: 1, borderColor: colors.border, alignItems: 'center', marginBottom: 4,
  },
  cardEmoji: { fontSize: 32, marginBottom: 8 },
  cardTitle: { fontWeight: '600', color: colors.purpleDark },
});
