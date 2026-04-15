import { create } from 'zustand';
import * as SecureStore from 'expo-secure-store';
import { apiClient } from '../lib/api';

export interface User {
  id: string;
  name: string;
  email: string;
  role: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  loadFromStorage: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  token: null,
  isLoading: true,

  loadFromStorage: async () => {
    try {
      const token    = await SecureStore.getItemAsync('auth_token');
      const userJson = await SecureStore.getItemAsync('auth_user');
      if (token && userJson) {
        set({ token, user: JSON.parse(userJson) });
      }
    } finally {
      set({ isLoading: false });
    }
  },

  login: async (email, password) => {
    const { data } = await apiClient.post('/v1/auth/login', { email, password });
    await SecureStore.setItemAsync('auth_token', data.data.token);
    await SecureStore.setItemAsync('auth_user', JSON.stringify(data.data.user));
    set({ user: data.data.user, token: data.data.token });
  },

  register: async (name, email, password) => {
    const { data } = await apiClient.post('/v1/auth/register', {
      name,
      email,
      password,
      password_confirmation: password,
    });
    await SecureStore.setItemAsync('auth_token', data.data.token);
    await SecureStore.setItemAsync('auth_user', JSON.stringify(data.data.user));
    set({ user: data.data.user, token: data.data.token });
  },

  logout: async () => {
    try {
      await apiClient.post('/v1/auth/logout');
    } catch {
      // Ignorer les erreurs réseau au logout
    } finally {
      await SecureStore.deleteItemAsync('auth_token');
      await SecureStore.deleteItemAsync('auth_user');
      set({ user: null, token: null });
    }
  },
}));
