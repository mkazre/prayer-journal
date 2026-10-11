import { useCallback, useEffect, useRef, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { api } from '../api/client';

/**
 * Loads a GET endpoint, reloads when the screen regains focus, and exposes
 * pull-to-refresh state.
 */
export function useApi<T>(path: string | null, { refetchOnFocus = true } = {}) {
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const first = useRef(true);

  const load = useCallback(async (mode: 'initial' | 'refresh' | 'silent' = 'initial') => {
    if (! path) return;
    if (mode === 'refresh') setRefreshing(true);
    if (mode === 'initial') setLoading(true);
    try {
      setData(await api.get<T>(path));
      setError(null);
    } catch (e: any) {
      setError(e.message || 'Something went wrong.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [path]);

  useEffect(() => {
    first.current = true;
    load('initial');
  }, [load]);

  useFocusEffect(useCallback(() => {
    if (first.current) {
      first.current = false;
      return;
    }
    if (refetchOnFocus) load('silent');
  }, [load, refetchOnFocus]));

  return { data, setData, error, loading, refreshing, refresh: () => load('refresh'), reload: () => load('silent') };
}
