import axios from 'axios';
import * as SecureStore from 'expo-secure-store';
import { Alert } from 'react-native';
import { API_BASE_URL } from './constants';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 10000,
});

// Injecter le token Bearer automatiquement
apiClient.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Normaliser les erreurs
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    // DEBUG TEMPORAIRE — à supprimer après diagnostic
    Alert.alert('🔴 Network Error Debug', JSON.stringify({
      url: error.config?.baseURL + error.config?.url,
      status: error.response?.status,
      message: error.message,
      data: error.response?.data,
    }, null, 2));

    const message: string =
      error.response?.data?.message ?? error.message ?? 'Erreur réseau';
    return Promise.reject(new Error(message));
  }
);
