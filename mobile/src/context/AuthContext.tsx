import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { api, getToken, setToken } from '../api/client';
import { AppConfig, User } from '../api/types';

type AuthContextValue = {
  user: User | null;
  loading: boolean;
  config: AppConfig | null;
  login: (email: string, password: string) => Promise<void>;
  register: (username: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshConfig: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [config, setConfig] = useState<AppConfig | null>(null);
  const [loading, setLoading] = useState(true);

  const refreshConfig = useCallback(async () => {
    try {
      setConfig(await api.get<AppConfig>('/app'));
    } catch {
      // The app still works offline-first for cached screens; config is optional.
    }
  }, []);

  useEffect(() => {
    (async () => {
      refreshConfig();
      if (await getToken()) {
        try {
          setUser(await api.get<User>('/auth/me'));
        } catch {
          await setToken(null);
        }
      }
      setLoading(false);
    })();
  }, [refreshConfig]);

  async function login(email: string, password: string) {
    const res = await api.post<{ token: string; user: User }>('/auth/login', { email, password });
    await setToken(res.token);
    setUser(res.user);
  }

  async function register(username: string, email: string, password: string) {
    const res = await api.post<{ token: string; user: User }>('/auth/register', { username, email, password });
    await setToken(res.token);
    setUser(res.user);
  }

  async function logout() {
    try {
      await api.post('/auth/logout');
    } catch {
      // ignore - the local token is cleared regardless
    }
    await setToken(null);
    setUser(null);
  }

  return (
    <AuthContext.Provider value={{ user, loading, config, login, register, logout, refreshConfig }}>
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
