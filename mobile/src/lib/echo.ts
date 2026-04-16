import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import * as SecureStore from 'expo-secure-store';
import { API_BASE_URL, REVERB_APP_KEY, REVERB_HOST, REVERB_PORT } from './constants';

// Nécessaire pour que Laravel Echo trouve Pusher
(globalThis as any).Pusher = Pusher;

let echoInstance: Echo | null = null;

export async function getEcho(): Promise<Echo> {
  if (echoInstance) return echoInstance;

  const token = await SecureStore.getItemAsync('auth_token');
  // L'endpoint broadcasting/auth est sous le préfixe /api avec middleware auth:sanctum
  const authEndpoint = API_BASE_URL + '/broadcasting/auth';

  echoInstance = new Echo({
    broadcaster:       'reverb',
    key:               REVERB_APP_KEY,
    wsHost:            REVERB_HOST,
    wsPort:            REVERB_PORT,
    wssPort:           REVERB_PORT,
    forceTLS:          false,
    enabledTransports: ['ws'],
    authEndpoint,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept:        'application/json',
      },
    },
  });

  return echoInstance;
}

export function destroyEcho(): void {
  echoInstance?.disconnect();
  echoInstance = null;
}
