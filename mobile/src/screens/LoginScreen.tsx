import React, { useState } from 'react';
import { Pressable, Text } from 'react-native';
import { useAuth } from '../context/AuthContext';
import { useTheme } from '../context/ThemeContext';
import { Button, Field } from '../components/ui';
import AuthLayout from './AuthLayout';

export default function LoginScreen({ navigation }: any) {
  const { login } = useAuth();
  const { c } = useTheme();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [show, setShow] = useState(false);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit() {
    if (! email || ! password) {
      setError('Enter your email and password.');
      return;
    }
    setBusy(true);
    setError('');
    try {
      await login(email.trim(), password);
    } catch (e: any) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthLayout title="Welcome back" subtitle="Sign in to continue your journey.">
      <Field label="Email" icon="mail" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" autoComplete="email" placeholder="you@example.com" />
      <Field label="Password" icon="lock" value={password} onChangeText={setPassword} secureTextEntry={! show} autoComplete="password" placeholder="Your password" onSubmitEditing={submit} />
      <Pressable onPress={() => setShow((s) => ! s)}><Text style={{ color: c.brand, fontWeight: '600', fontSize: 13 }}>{show ? 'Hide password' : 'Show password'}</Text></Pressable>
      {error ? <Text style={{ color: c.danger, fontSize: 14 }}>{error}</Text> : null}
      <Button title="Sign in" icon="log-in" onPress={submit} loading={busy} />
      <Pressable onPress={() => navigation.navigate('Register')} style={{ alignItems: 'center', paddingVertical: 6 }}>
        <Text style={{ color: c.muted }}>New here? <Text style={{ color: c.brand, fontWeight: '700' }}>Create a free account</Text></Text>
      </Pressable>
    </AuthLayout>
  );
}
