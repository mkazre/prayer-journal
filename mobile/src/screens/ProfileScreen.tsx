import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { useAuth } from '../context/AuthContext';
import { api } from '../api/client';
import { colors } from '../theme/colors';

export default function ProfileScreen() {
  const { user, logout } = useAuth();
  const [streak, setStreak] = useState<number | null>(null);

  useEffect(() => {
    api.get<{ streak: number }>('/bible/streak').then((r) => setStreak(r.streak)).catch(() => {});
  }, []);

  return (
    <View style={styles.container}>
      <Text style={styles.username}>{user?.username}</Text>
      <Text style={styles.email}>{user?.email}</Text>
      <Text style={styles.groups}>Role: {user?.groups?.join(', ')}</Text>

      {streak !== null && streak > 0 && (
        <View style={styles.streakBadge}>
          <Text style={styles.streakText}>🔥 {streak}-day Bible reading streak</Text>
        </View>
      )}

      <TouchableOpacity style={styles.logoutBtn} onPress={logout}>
        <Text style={styles.logoutText}>Log Out</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg, padding: 24, alignItems: 'center', paddingTop: 60 },
  username: { fontSize: 22, fontWeight: '700', color: colors.purpleDark },
  email: { color: colors.gray, marginTop: 4 },
  groups: { color: colors.gray, marginTop: 4, textTransform: 'capitalize' },
  streakBadge: { backgroundColor: '#fbf3da', borderRadius: 20, paddingHorizontal: 16, paddingVertical: 8, marginTop: 20 },
  streakText: { color: '#8a6d10', fontWeight: '600' },
  logoutBtn: { marginTop: 40, backgroundColor: colors.danger, borderRadius: 10, paddingHorizontal: 24, paddingVertical: 12 },
  logoutText: { color: colors.white, fontWeight: '700' },
});
