import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Image, ActivityIndicator, Alert } from 'react-native';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function LoginScreen({ navigation }: any) {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);

  async function handleLogin() {
    setBusy(true);
    try {
      await login(email, password);
    } catch (e: any) {
      Alert.alert('Login failed', e.message ?? 'Please check your credentials.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <View style={styles.container}>
      <Image source={require('../../assets/icon.png')} style={styles.logo} />
      <Text style={styles.title}>Welcome back</Text>

      <TextInput
        style={styles.input}
        placeholder="Email"
        autoCapitalize="none"
        keyboardType="email-address"
        value={email}
        onChangeText={setEmail}
      />
      <TextInput
        style={styles.input}
        placeholder="Password"
        secureTextEntry
        value={password}
        onChangeText={setPassword}
      />

      <TouchableOpacity style={styles.button} onPress={handleLogin} disabled={busy}>
        {busy ? <ActivityIndicator color={colors.white} /> : <Text style={styles.buttonText}>Log In</Text>}
      </TouchableOpacity>

      <TouchableOpacity onPress={() => navigation.navigate('Register')}>
        <Text style={styles.link}>Need an account? Register</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.bg, padding: 24, justifyContent: 'center' },
  logo: { width: 72, height: 72, alignSelf: 'center', marginBottom: 16, borderRadius: 12 },
  title: { fontSize: 24, fontWeight: '700', color: colors.purpleDark, textAlign: 'center', marginBottom: 24 },
  input: { borderWidth: 1, borderColor: colors.border, borderRadius: 10, padding: 14, marginBottom: 12, backgroundColor: colors.white },
  button: { backgroundColor: colors.purple, borderRadius: 10, padding: 14, alignItems: 'center', marginTop: 8 },
  buttonText: { color: colors.white, fontWeight: '700', fontSize: 16 },
  link: { color: colors.purple, textAlign: 'center', marginTop: 16 },
});
