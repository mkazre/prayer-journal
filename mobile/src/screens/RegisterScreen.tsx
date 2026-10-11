import React, { useState } from 'react';
import { Pressable, Text } from 'react-native';
import { useAuth } from '../context/AuthContext';
import { useTheme } from '../context/ThemeContext';
import { Button, Field } from '../components/ui';
import AuthLayout from './AuthLayout';

export default function RegisterScreen({ navigation }: any) {
  const { register } = useAuth();
  const { c } = useTheme();
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit() {
    if (username.trim().length < 3) return setError('Choose a username of at least 3 characters.');
    if (! /\S+@\S+\.\S+/.test(email)) return setError('Enter a valid email address.');
    if (password.length < 8) return setError('Use at least 8 characters for your password.');
    setBusy(true);
    setError('');
    try {
      await register(username.trim(), email.trim(), password);
    } catch (e: any) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthLayout title="Create your account" subtitle="Free forever. Your journal stays private.">
      <Field label="Username" icon="user" value={username} onChangeText={setUsername} autoCapitalize="none" placeholder="e.g. grace" />
      <Field label="Email" icon="mail" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" placeholder="you@example.com" />
      <Field label="Password" icon="lock" value={password} onChangeText={setPassword} secureTextEntry placeholder="At least 8 characters" />
      {error ? <Text style={{ color: c.danger, fontSize: 14 }}>{error}</Text> : null}
      <Button title="Create account" icon="user-plus" onPress={submit} loading={busy} />
      <Pressable onPress={() => navigation.navigate('Login')} style={{ alignItems: 'center', paddingVertical: 6 }}>
        <Text style={{ color: c.muted }}>Already a member? <Text style={{ color: c.brand, fontWeight: '700' }}>Sign in</Text></Text>
      </Pressable>
    </AuthLayout>
  );
}
