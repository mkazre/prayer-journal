import React, { createContext, useContext, useEffect, useState } from 'react';
import { api, getToken, setToken } from '../api/client';

type User = {
  id: number;
  username: string;
  email: string;
  groups: string[];
};

type AuthContextValue = {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (username: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      const token = await getToken();
      if (token) {
        try {
          const me = await api.get<User>('/auth/me');
          setUser(me);
        } catch {
          await setToken(null);
        }
      }
      setLoading(false);
    })();
  }, []);

  async function login(email: string, password: string) {
    const res = await api.post<{ token: string; user: User }>('/auth/login', { email, password }, false);
    await setToken(res.token);
    setUser(res.user);
  }

  async function register(username: string, email: string, password: string) {
    const res = await api.post<{ token: string; user: User }>('/auth/register', { username, email, password }, false);
    await setToken(res.token);
    setUser(res.user);
  }

  async function logout() {
    try {
      await api.post('/auth/logout');
    } catch {
      // ignore - we're clearing the local token regardless
    }
    await setToken(null);
    setUser(null);
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (! ctx) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return ctx;
}
