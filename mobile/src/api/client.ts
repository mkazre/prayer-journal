import AsyncStorage from '@react-native-async-storage/async-storage';

// Android emulator reaches the host machine's localhost via 10.0.2.2; a
// physical device needs your machine's LAN IP instead (e.g. 192.168.1.20).
// iOS simulator can use localhost directly. Update this for your setup.
export const API_BASE_URL = 'http://10.0.2.2:8080/api/v1';

const TOKEN_KEY = 'pj_auth_token';

export async function getToken(): Promise<string | null> {
  return AsyncStorage.getItem(TOKEN_KEY);
}

export async function setToken(token: string | null): Promise<void> {
  if (token) {
    await AsyncStorage.setItem(TOKEN_KEY, token);
  } else {
    await AsyncStorage.removeItem(TOKEN_KEY);
  }
}

export class ApiError extends Error {
  status: number;
  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

async function request<T>(path: string, options: RequestInit = {}, auth = true): Promise<T> {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string>),
  };

  if (auth) {
    const token = await getToken();
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  const response = await fetch(`${API_BASE_URL}${path}`, { ...options, headers });
  const text = await response.text();
  const data = text ? JSON.parse(text) : null;

  if (! response.ok) {
    throw new ApiError(data?.error || data?.message || 'Request failed', response.status);
  }

  return data as T;
}

export const api = {
  get: <T>(path: string, auth = true) => request<T>(path, { method: 'GET' }, auth),
  post: <T>(path: string, body?: unknown, auth = true) =>
    request<T>(path, { method: 'POST', body: body ? JSON.stringify(body) : undefined }, auth),
};
