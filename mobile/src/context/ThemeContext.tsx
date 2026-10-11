import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { useColorScheme } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { dark, light, Palette } from '../theme/colors';

export type ThemeMode = 'system' | 'light' | 'dark';
export type TextScale = 'sm' | 'md' | 'lg' | 'xl';

const SCALE: Record<TextScale, number> = { sm: 0.9, md: 1, lg: 1.15, xl: 1.3 };
const KEY = 'pj_prefs';

type ThemeValue = {
  c: Palette;
  isDark: boolean;
  mode: ThemeMode;
  setMode: (m: ThemeMode) => void;
  textScale: TextScale;
  setTextScale: (s: TextScale) => void;
  /** Multiplier for reading text (Bible, lessons, journal). */
  readScale: number;
};

const ThemeContext = createContext<ThemeValue | undefined>(undefined);

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const system = useColorScheme();
  const [mode, setModeState] = useState<ThemeMode>('system');
  const [textScale, setScaleState] = useState<TextScale>('md');

  useEffect(() => {
    AsyncStorage.getItem(KEY).then((raw) => {
      if (! raw) return;
      try {
        const p = JSON.parse(raw);
        if (p.mode) setModeState(p.mode);
        if (p.textScale) setScaleState(p.textScale);
      } catch {
        // ignore corrupt preferences
      }
    });
  }, []);

  function persist(next: { mode: ThemeMode; textScale: TextScale }) {
    AsyncStorage.setItem(KEY, JSON.stringify(next)).catch(() => undefined);
  }

  const value = useMemo<ThemeValue>(() => {
    const isDark = mode === 'dark' || (mode === 'system' && system === 'dark');
    return {
      c: isDark ? dark : light,
      isDark,
      mode,
      textScale,
      readScale: SCALE[textScale],
      setMode: (m) => { setModeState(m); persist({ mode: m, textScale }); },
      setTextScale: (s) => { setScaleState(s); persist({ mode, textScale: s }); },
    };
  }, [mode, textScale, system]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeValue {
  const ctx = useContext(ThemeContext);
  if (! ctx) {
    throw new Error('useTheme must be used within ThemeProvider');
  }
  return ctx;
}
