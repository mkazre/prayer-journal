import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function RegisterScreen({ navigation }: any) {
  const { register } = useAuth();
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);

  async function handleRegister() {
    setBusy(true);
    try {
      await register(username, email, password);
    } catch (e: any) {
      Alert.alert('Registration failed', e.message ?? 'Please check your details.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Create your account</Text>

      <TextInput style={styles.input} placeholder="Username" autoCapitalize="none" value={username} onChangeText={setUsername} />
      <TextInput style={styles.input} placeholder="Email" autoCapitalize="none" keyboardType="email-address" value={email} onChangeText={setEmail} />
      <TextInput style={styles.input} placeholder="Password (min 8 characters)" secureTextEntry value={password} onChangeText={setPassword} />

      <TouchableOpacity style={styles.button} onPress={handleRegister} disabled={busy}>
        {busy ? <ActivityIndicator color={colors.white} /> : <Text style={styles.buttonText}>Register</Text>}
      </TouchableOpacity>

      <TouchableOpacity onPress={() => navigation.navigate('Login')}>
        <Text style={styles.link}>Already have an account? Log in</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg, padding: 24, justifyContent: 'center' },
  title: { fontSize: 24, fontWeight: '700', color: colors.purpleDark, textAlign: 'center', marginBottom: 24 },
  input: { borderWidth: 1, borderColor: colors.border, borderRadius: 10, padding: 14, marginBottom: 12, backgroundColor: colors.white },
  button: { backgroundColor: colors.purple, borderRadius: 10, padding: 14, alignItems: 'center', marginTop: 8 },
  buttonText: { color: colors.white, fontWeight: '700', fontSize: 16 },
  link: { color: colors.purple, textAlign: 'center', marginTop: 16 },
});
