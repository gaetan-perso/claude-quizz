# Plan : Client Mobile React Native + Laravel Reverb
Date : 2026-04-14
Objectif : Créer le client mobile Expo (iOS/Android) connecté à l'API REST + WebSocket Reverb
Architecture : Expo Router (navigation file-based), Zustand (state), Axios (HTTP), Laravel Echo + Pusher-JS (WebSocket Reverb)
Stack : Laravel 13 / PHP 8.3+ / React Native Expo 52+ / Laravel Reverb

---

## PRÉREQUIS MANUEL (utilisateur)

### Étape 0a — Installer Node.js
Télécharger et installer Node.js LTS depuis https://nodejs.org
Vérifier : `node -v` → v20+ et `npm -v` → v10+

### Étape 0b — Trouver l'IP locale de la machine
```
ipconfig  (Windows)
# Chercher "Adresse IPv4" sur l'interface Wi-Fi, ex: 192.168.1.42
```
Cette IP remplacera `localhost` dans la config mobile.

### Étape 0c — Activer CORS sur le backend pour l'IP du téléphone
Vérifier `config/cors.php` → `allowed_origins` doit être `['*']` en dev.

---

## BLOC A — Backend : Laravel Reverb + Events multijoueur

### Tâche A1 — Installer et configurer Laravel Reverb

**Agent** : realtime-agent

**Fichiers concernés** :
- `composer.json` (modifier)
- `.env` (modifier)
- `config/broadcasting.php` (modifier)
- `config/reverb.php` (créer via publish)

**Instructions** :
```bash
cd C:\projets\IA\app_quizz_claude
composer require laravel/reverb
php artisan reverb:install
```

Modifier `.env` :
```
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=quiz-app
REVERB_APP_KEY=quiz-app-key
REVERB_APP_SECRET=quiz-app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Dans `config/reverb.php`, s'assurer que `allowed_origins` est `['*']`.

**Commande de vérification** :
```bash
php artisan reverb:start --debug
# Expected : Reverb server started on 0.0.0.0:8080
```

**Commit** : `feat(reverb): install and configure Laravel Reverb`

---

### Tâche A2 — Créer le canal Presence du lobby

**Agent** : realtime-agent

**Fichiers concernés** :
- `routes/channels.php` (modifier)
- `app/Http/Middleware/Authenticate.php` (vérifier)

**Code complet** dans `routes/channels.php` :
```php
<?php declare(strict_types=1);

use App\Models\Lobby;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Canal presence : tous les participants d'un lobby
Broadcast::channel('lobby.{lobbyId}', function (User $user, string $lobbyId): array|false {
    $lobby = Lobby::with('participants')->find($lobbyId);

    if ($lobby === null) {
        return false;
    }

    $isParticipant = $lobby->participants()->where('user_id', $user->id)->exists();

    if (! $isParticipant) {
        return false;
    }

    return [
        'id'   => $user->id,
        'name' => $user->name,
    ];
});
```

**Commande de vérification** :
```bash
php artisan route:list | grep broadcasting
# Expected : POST broadcasting/auth
```

**Commit** : `feat(reverb): define presence channel for lobby`

---

### Tâche A3 — Event : LobbyPlayerJoined

**Agent** : realtime-agent

**Fichiers concernés** :
- `app/Events/LobbyPlayerJoined.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LobbyPlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $lobbyId,
        public readonly string $userId,
        public readonly string $userName,
        public readonly array  $participants,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('lobby.' . $this->lobbyId);
    }

    public function broadcastAs(): string
    {
        return 'player.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'      => $this->userId,
            'user_name'    => $this->userName,
            'participants' => $this->participants,
        ];
    }
}
```

**Commit** : `feat(reverb): event LobbyPlayerJoined`

---

### Tâche A4 — Event : LobbyPlayerLeft

**Agent** : realtime-agent

**Fichiers concernés** :
- `app/Events/LobbyPlayerLeft.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LobbyPlayerLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $lobbyId,
        public readonly string $userId,
        public readonly array  $participants,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('lobby.' . $this->lobbyId);
    }

    public function broadcastAs(): string
    {
        return 'player.left';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'      => $this->userId,
            'participants' => $this->participants,
        ];
    }
}
```

**Commit** : `feat(reverb): event LobbyPlayerLeft`

---

### Tâche A5 — Event : LobbyStarted

**Agent** : realtime-agent

**Fichiers concernés** :
- `app/Events/LobbyStarted.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LobbyStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $lobbyId,
        /** Map user_id => session_id pour chaque participant */
        public readonly array  $sessionMap,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('lobby.' . $this->lobbyId);
    }

    public function broadcastAs(): string
    {
        return 'lobby.started';
    }

    public function broadcastWith(): array
    {
        return [
            'session_map' => $this->sessionMap,
        ];
    }
}
```

**Commit** : `feat(reverb): event LobbyStarted`

---

### Tâche A6 — Event : LobbyGameCompleted

**Agent** : realtime-agent

**Fichiers concernés** :
- `app/Events/LobbyGameCompleted.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LobbyGameCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $lobbyId,
        /** Classement final : [{user_id, name, score}] trié par score desc */
        public readonly array  $leaderboard,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('lobby.' . $this->lobbyId);
    }

    public function broadcastAs(): string
    {
        return 'game.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'leaderboard' => $this->leaderboard,
        ];
    }
}
```

**Commit** : `feat(reverb): event LobbyGameCompleted`

---

### Tâche A7 — Dispatcher les events depuis LobbyController

**Agent** : realtime-agent

**Fichiers concernés** :
- `app/Http/Controllers/Api/V1/LobbyController.php` (modifier)

**Modifications** — ajouter les dispatches dans `join()`, `leave()`, et `start()` :

Dans `join()`, après `$lobby->load(...)` :
```php
LobbyPlayerJoined::dispatch(
    lobbyId:      $lobby->id,
    userId:       $request->user()->id,
    userName:     $request->user()->name,
    participants: $this->formatParticipants($lobby),
);
```

Dans `leave()`, après `$participant->delete()` :
```php
$lobby->load('participants.user');
LobbyPlayerLeft::dispatch(
    lobbyId:      $lobby->id,
    userId:       $request->user()->id,
    participants: $this->formatParticipants($lobby),
);
```

Dans `start()`, après `$lobby->load(...)`, construire le sessionMap et dispatcher :
```php
// Construire sessionMap : user_id => session_id
$sessions = QuizSession::where('category_id', $lobby->category_id)
    ->whereIn('user_id', $lobby->participants->pluck('user_id'))
    ->where('status', 'active')
    ->latest()
    ->get()
    ->keyBy('user_id');

$sessionMap = $sessions->map(fn ($s) => $s->id)->toArray();

LobbyStarted::dispatch(
    lobbyId:    $lobby->id,
    sessionMap: $sessionMap,
);
```

Ajouter une méthode privée `formatParticipants()` :
```php
private function formatParticipants(Lobby $lobby): array
{
    return $lobby->participants->map(fn (LobbyParticipant $p) => [
        'user_id' => $p->user_id,
        'name'    => $p->user->name,
        'score'   => $p->score,
    ])->values()->toArray();
}
```

Ajouter les imports en tête du fichier :
```php
use App\Events\LobbyGameCompleted;
use App\Events\LobbyPlayerJoined;
use App\Events\LobbyPlayerLeft;
use App\Events\LobbyStarted;
```

**Commit** : `feat(reverb): dispatch broadcast events from LobbyController`

---

### Tâche A8 — Endpoint POST /lobbies/{lobby}/complete (fin de partie multijoueur)

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Http/Controllers/Api/V1/LobbyController.php` (modifier)
- `routes/api.php` (modifier)

**Nouvelle méthode** `complete()` dans LobbyController :
```php
public function complete(Request $request, Lobby $lobby): JsonResponse
{
    abort_if($lobby->host_user_id !== $request->user()->id, 403);
    abort_if($lobby->status->value !== 'in_progress', 422, 'Le lobby n\'est pas en cours.');

    $lobby->update(['status' => 'completed', 'completed_at' => now()]);

    // Compléter toutes les sessions actives des participants
    QuizSession::whereIn('user_id', $lobby->participants->pluck('user_id'))
        ->where('status', 'active')
        ->update(['status' => 'completed', 'completed_at' => now()]);

    // Construire le classement final
    $leaderboard = $lobby->participants->load('user')
        ->map(fn (LobbyParticipant $p) => [
            'user_id' => $p->user_id,
            'name'    => $p->user->name,
            'score'   => QuizSession::where('user_id', $p->user_id)
                ->where('category_id', $lobby->category_id)
                ->latest()
                ->value('score') ?? 0,
        ])
        ->sortByDesc('score')
        ->values()
        ->toArray();

    LobbyGameCompleted::dispatch(
        lobbyId:     $lobby->id,
        leaderboard: $leaderboard,
    );

    return response()->json(['data' => ['leaderboard' => $leaderboard]]);
}
```

Dans `routes/api.php`, ajouter dans le groupe authentifié :
```php
Route::post('lobbies/{lobby}/complete', [LobbyController::class, 'complete']);
```

**Commit** : `feat(lobby): add complete endpoint + broadcast GameCompleted`

---

## BLOC B — Mobile : Setup du projet Expo

### Tâche B1 — Créer le projet Expo

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/` (créer — dossier Expo)

**Commandes** :
```bash
cd C:\projets\IA\app_quizz_claude
npx create-expo-app@latest mobile --template blank-typescript
cd mobile
```

**Commit** : `feat(mobile): init Expo project with TypeScript template`

---

### Tâche B2 — Installer les dépendances

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/package.json` (modifier)

**Commandes** :
```bash
cd mobile
npx expo install expo-router expo-secure-store expo-constants expo-status-bar
npm install axios zustand @react-native-async-storage/async-storage
npm install laravel-echo pusher-js
npm install --save-dev @types/pusher-js
```

Modifier `mobile/package.json` → section `main` :
```json
"main": "expo-router/entry"
```

Modifier `mobile/app.json` :
```json
{
  "expo": {
    "name": "Quiz Claude",
    "slug": "quiz-claude",
    "scheme": "quiz-claude",
    "version": "1.0.0",
    "platforms": ["ios", "android"],
    "assetBundlePatterns": ["**/*"]
  }
}
```

**Commit** : `feat(mobile): install dependencies (axios, zustand, expo-router, laravel-echo)`

---

### Tâche B3 — Configuration API (base URL + intercepteurs Axios)

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/src/lib/api.ts` (créer)
- `mobile/src/lib/constants.ts` (créer)

**`mobile/src/lib/constants.ts`** :
```typescript
import Constants from 'expo-constants';

// Remplacer 192.168.1.42 par l'IP LAN de la machine de développement
// ou passer via variable d'environnement EXPO_PUBLIC_API_HOST
export const API_BASE_URL =
  process.env.EXPO_PUBLIC_API_HOST
    ? `http://${process.env.EXPO_PUBLIC_API_HOST}:8000/api`
    : 'http://192.168.1.42:8000/api';

export const REVERB_HOST =
  process.env.EXPO_PUBLIC_API_HOST ?? '192.168.1.42';

export const REVERB_PORT = 8080;
export const REVERB_APP_KEY = 'quiz-app-key';
```

**`mobile/src/lib/api.ts`** :
```typescript
import axios from 'axios';
import * as SecureStore from 'expo-secure-store';
import { API_BASE_URL } from './constants';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
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

// Intercepteur de réponse : renvoyer data.data directement
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const message: string =
      error.response?.data?.message ?? error.message ?? 'Erreur réseau';
    return Promise.reject(new Error(message));
  }
);
```

**Commit** : `feat(mobile): configure axios client with auth interceptor`

---

### Tâche B4 — Store Zustand : authentification

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/src/store/authStore.ts` (créer)

**Code complet** :
```typescript
import { create } from 'zustand';
import * as SecureStore from 'expo-secure-store';
import { apiClient } from '../lib/api';

interface User {
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
    const token = await SecureStore.getItemAsync('auth_token');
    const userJson = await SecureStore.getItemAsync('auth_user');
    if (token && userJson) {
      set({ token, user: JSON.parse(userJson), isLoading: false });
    } else {
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
    } catch {}
    await SecureStore.deleteItemAsync('auth_token');
    await SecureStore.deleteItemAsync('auth_user');
    set({ user: null, token: null });
  },
}));
```

**Commit** : `feat(mobile): auth store with Zustand + SecureStore`

---

### Tâche B5 — Hook Laravel Echo (WebSocket Reverb)

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/src/lib/echo.ts` (créer)

**Code complet** :
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import * as SecureStore from 'expo-secure-store';
import { REVERB_APP_KEY, REVERB_HOST, REVERB_PORT } from './constants';
import { API_BASE_URL } from './constants';

(globalThis as any).Pusher = Pusher;

let echoInstance: Echo | null = null;

export async function getEcho(): Promise<Echo> {
  if (echoInstance) return echoInstance;

  const token = await SecureStore.getItemAsync('auth_token');

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: REVERB_APP_KEY,
    wsHost: REVERB_HOST,
    wsPort: REVERB_PORT,
    wssPort: REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws'],
    authEndpoint: `${API_BASE_URL.replace('/api', '')}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  });

  return echoInstance;
}

export function destroyEcho(): void {
  echoInstance?.disconnect();
  echoInstance = null;
}
```

**Commit** : `feat(mobile): configure Laravel Echo for Reverb WebSocket`

---

### Tâche B6 — Structure de navigation (Expo Router)

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/_layout.tsx` (créer)
- `mobile/app/(auth)/_layout.tsx` (créer)
- `mobile/app/(auth)/login.tsx` (créer — placeholder)
- `mobile/app/(auth)/register.tsx` (créer — placeholder)
- `mobile/app/(app)/_layout.tsx` (créer)
- `mobile/app/(app)/index.tsx` (créer — placeholder)

**`mobile/app/_layout.tsx`** :
```typescript
import { useEffect } from 'react';
import { Stack, useRouter, useSegments } from 'expo-router';
import { useAuthStore } from '../src/store/authStore';

export default function RootLayout() {
  const { user, isLoading, loadFromStorage } = useAuthStore();
  const segments = useSegments();
  const router = useRouter();

  useEffect(() => {
    loadFromStorage();
  }, []);

  useEffect(() => {
    if (isLoading) return;
    const inAuthGroup = segments[0] === '(auth)';
    if (!user && !inAuthGroup) {
      router.replace('/(auth)/login');
    } else if (user && inAuthGroup) {
      router.replace('/(app)');
    }
  }, [user, isLoading, segments]);

  return <Stack screenOptions={{ headerShown: false }} />;
}
```

**`mobile/app/(auth)/_layout.tsx`** :
```typescript
import { Stack } from 'expo-router';
export default function AuthLayout() {
  return <Stack screenOptions={{ headerShown: false }} />;
}
```

**`mobile/app/(app)/_layout.tsx`** :
```typescript
import { Stack } from 'expo-router';
export default function AppLayout() {
  return <Stack screenOptions={{ headerTintColor: '#6366f1', headerStyle: { backgroundColor: '#1e1b4b' } }} />;
}
```

**Commit** : `feat(mobile): navigation structure with Expo Router auth guard`

---

## BLOC C — Écrans Auth

### Tâche C1 — Thème et composants UI communs

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/src/theme.ts` (créer)
- `mobile/src/components/Button.tsx` (créer)
- `mobile/src/components/Input.tsx` (créer)

**`mobile/src/theme.ts`** :
```typescript
export const colors = {
  primary:    '#6366f1',   // indigo-500
  primaryDark:'#4f46e5',   // indigo-600
  background: '#0f172a',   // slate-900
  surface:    '#1e293b',   // slate-800
  border:     '#334155',   // slate-700
  text:       '#f1f5f9',   // slate-100
  textMuted:  '#94a3b8',   // slate-400
  success:    '#22c55e',   // green-500
  error:      '#ef4444',   // red-500
  warning:    '#f59e0b',   // amber-500
};

export const spacing = {
  xs: 4, sm: 8, md: 16, lg: 24, xl: 32,
};

export const radius = {
  sm: 6, md: 12, lg: 20, full: 999,
};
```

**`mobile/src/components/Button.tsx`** :
```typescript
import { TouchableOpacity, Text, ActivityIndicator, StyleSheet } from 'react-native';
import { colors, radius, spacing } from '../theme';

interface Props {
  label: string;
  onPress: () => void;
  loading?: boolean;
  variant?: 'primary' | 'outline' | 'ghost';
  disabled?: boolean;
}

export function Button({ label, onPress, loading = false, variant = 'primary', disabled = false }: Props) {
  const bg = variant === 'primary' ? colors.primary
           : variant === 'outline' ? 'transparent'
           : 'transparent';
  const borderColor = variant === 'outline' ? colors.primary : 'transparent';
  const textColor   = variant === 'primary' ? '#fff' : colors.primary;

  return (
    <TouchableOpacity
      onPress={onPress}
      disabled={disabled || loading}
      style={[styles.btn, { backgroundColor: bg, borderColor, opacity: disabled ? 0.6 : 1 }]}
    >
      {loading
        ? <ActivityIndicator color={textColor} />
        : <Text style={[styles.label, { color: textColor }]}>{label}</Text>
      }
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  btn:   { padding: spacing.md, borderRadius: radius.md, alignItems: 'center', borderWidth: 1 },
  label: { fontSize: 16, fontWeight: '600' },
});
```

**`mobile/src/components/Input.tsx`** :
```typescript
import { TextInput, View, Text, StyleSheet, TextInputProps } from 'react-native';
import { colors, radius, spacing } from '../theme';

interface Props extends TextInputProps {
  label?: string;
  error?: string;
}

export function Input({ label, error, ...props }: Props) {
  return (
    <View style={styles.wrapper}>
      {label && <Text style={styles.label}>{label}</Text>}
      <TextInput
        {...props}
        style={[styles.input, error ? styles.inputError : null]}
        placeholderTextColor={colors.textMuted}
      />
      {error && <Text style={styles.error}>{error}</Text>}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper:    { marginBottom: spacing.md },
  label:      { color: colors.textMuted, marginBottom: spacing.xs, fontSize: 14 },
  input:      { backgroundColor: colors.surface, color: colors.text, padding: spacing.md, borderRadius: radius.md, borderWidth: 1, borderColor: colors.border, fontSize: 16 },
  inputError: { borderColor: colors.error },
  error:      { color: colors.error, fontSize: 12, marginTop: spacing.xs },
});
```

**Commit** : `feat(mobile): add theme + shared Button and Input components`

---

### Tâche C2 — Écran Login

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(auth)/login.tsx` (modifier)

**Code complet** :
```typescript
import { useState } from 'react';
import { View, Text, StyleSheet, KeyboardAvoidingView, Platform, ScrollView } from 'react-native';
import { Link } from 'expo-router';
import { useAuthStore } from '../../src/store/authStore';
import { Input } from '../../src/components/Input';
import { Button } from '../../src/components/Button';
import { colors, spacing } from '../../src/theme';

export default function LoginScreen() {
  const { login } = useAuthStore();
  const [email, setEmail]       = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading]   = useState(false);
  const [error, setError]       = useState('');

  async function handleLogin() {
    setError('');
    setLoading(true);
    try {
      await login(email.trim(), password);
    } catch (e: any) {
      setError(e.message ?? 'Erreur de connexion');
    } finally {
      setLoading(false);
    }
  }

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
        <Text style={styles.title}>Quiz Claude</Text>
        <Text style={styles.subtitle}>Connexion</Text>

        {error ? <Text style={styles.errorBox}>{error}</Text> : null}

        <Input label="Email" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" autoCorrect={false} />
        <Input label="Mot de passe" value={password} onChangeText={setPassword} secureTextEntry />

        <Button label="Se connecter" onPress={handleLogin} loading={loading} />

        <Link href="/(auth)/register" style={styles.link}>
          Pas encore de compte ? S'inscrire
        </Link>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex:      { flex: 1, backgroundColor: colors.background },
  container: { flexGrow: 1, justifyContent: 'center', padding: spacing.xl },
  title:     { fontSize: 36, fontWeight: '800', color: colors.primary, textAlign: 'center', marginBottom: spacing.xs },
  subtitle:  { fontSize: 20, color: colors.textMuted, textAlign: 'center', marginBottom: spacing.xl },
  errorBox:  { backgroundColor: '#450a0a', borderColor: colors.error, borderWidth: 1, borderRadius: 8, padding: spacing.md, color: colors.error, marginBottom: spacing.md, textAlign: 'center' },
  link:      { color: colors.primary, textAlign: 'center', marginTop: spacing.lg, fontSize: 15 },
});
```

**Commit** : `feat(mobile): login screen`

---

### Tâche C3 — Écran Register

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(auth)/register.tsx` (modifier)

**Code complet** :
```typescript
import { useState } from 'react';
import { View, Text, StyleSheet, KeyboardAvoidingView, Platform, ScrollView } from 'react-native';
import { Link } from 'expo-router';
import { useAuthStore } from '../../src/store/authStore';
import { Input } from '../../src/components/Input';
import { Button } from '../../src/components/Button';
import { colors, spacing } from '../../src/theme';

export default function RegisterScreen() {
  const { register } = useAuthStore();
  const [name, setName]         = useState('');
  const [email, setEmail]       = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading]   = useState(false);
  const [error, setError]       = useState('');

  async function handleRegister() {
    setError('');
    if (!name.trim()) { setError('Le nom est requis'); return; }
    if (password.length < 8) { setError('Le mot de passe doit faire au moins 8 caractères'); return; }
    setLoading(true);
    try {
      await register(name.trim(), email.trim(), password);
    } catch (e: any) {
      setError(e.message ?? 'Erreur d\'inscription');
    } finally {
      setLoading(false);
    }
  }

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
        <Text style={styles.title}>Quiz Claude</Text>
        <Text style={styles.subtitle}>Créer un compte</Text>

        {error ? <Text style={styles.errorBox}>{error}</Text> : null}

        <Input label="Nom d'affichage" value={name} onChangeText={setName} autoCapitalize="words" />
        <Input label="Email" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" autoCorrect={false} />
        <Input label="Mot de passe (8 car. min.)" value={password} onChangeText={setPassword} secureTextEntry />

        <Button label="S'inscrire" onPress={handleRegister} loading={loading} />

        <Link href="/(auth)/login" style={styles.link}>
          Déjà un compte ? Se connecter
        </Link>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex:      { flex: 1, backgroundColor: colors.background },
  container: { flexGrow: 1, justifyContent: 'center', padding: spacing.xl },
  title:     { fontSize: 36, fontWeight: '800', color: colors.primary, textAlign: 'center', marginBottom: spacing.xs },
  subtitle:  { fontSize: 20, color: colors.textMuted, textAlign: 'center', marginBottom: spacing.xl },
  errorBox:  { backgroundColor: '#450a0a', borderColor: colors.error, borderWidth: 1, borderRadius: 8, padding: 12, color: colors.error, marginBottom: 12, textAlign: 'center' },
  link:      { color: colors.primary, textAlign: 'center', marginTop: spacing.lg, fontSize: 15 },
});
```

**Commit** : `feat(mobile): register screen`

---

## BLOC D — Écrans Solo

### Tâche D1 — Écran Home

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/index.tsx` (modifier)

**Code complet** :
```typescript
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../../src/store/authStore';
import { colors, spacing, radius } from '../../src/theme';

const MENU_ITEMS = [
  { label: 'Jouer en solo',       icon: '🎯', route: '/(app)/solo/categories' },
  { label: 'Multijoueur',         icon: '👥', route: '/(app)/multi/lobby'     },
  { label: 'Classement',          icon: '🏆', route: '/(app)/leaderboard'     },
];

export default function HomeScreen() {
  const router  = useRouter();
  const { user, logout } = useAuthStore();

  return (
    <View style={styles.container}>
      <Text style={styles.greeting}>Bonjour, {user?.name} 👋</Text>
      <Text style={styles.title}>Quiz Claude</Text>

      <View style={styles.menu}>
        {MENU_ITEMS.map((item) => (
          <TouchableOpacity
            key={item.route}
            style={styles.card}
            onPress={() => router.push(item.route as any)}
          >
            <Text style={styles.icon}>{item.icon}</Text>
            <Text style={styles.cardLabel}>{item.label}</Text>
          </TouchableOpacity>
        ))}
      </View>

      <TouchableOpacity onPress={logout} style={styles.logoutBtn}>
        <Text style={styles.logoutText}>Se déconnecter</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container:   { flex: 1, backgroundColor: colors.background, padding: spacing.xl, paddingTop: 60 },
  greeting:    { color: colors.textMuted, fontSize: 16, marginBottom: spacing.xs },
  title:       { fontSize: 32, fontWeight: '800', color: colors.primary, marginBottom: spacing.xl * 1.5 },
  menu:        { gap: spacing.md },
  card:        { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.xl, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderWidth: 1, borderColor: colors.border },
  icon:        { fontSize: 28 },
  cardLabel:   { fontSize: 18, fontWeight: '600', color: colors.text },
  logoutBtn:   { marginTop: 'auto', paddingVertical: spacing.md, alignItems: 'center' },
  logoutText:  { color: colors.textMuted, fontSize: 15 },
});
```

**Commit** : `feat(mobile): home screen with menu`

---

### Tâche D2 — Écran Sélection de catégorie (Solo)

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/solo/categories.tsx` (créer)

**Code complet** :
```typescript
import { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { colors, spacing, radius } from '../../../src/theme';

interface Category {
  id: string;
  name: string;
  icon: string | null;
  color: string | null;
}

export default function CategoriesScreen() {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading]       = useState(true);
  const [error, setError]           = useState('');

  useEffect(() => {
    apiClient.get('/v1/categories')
      .then(({ data }) => setCategories(data.data))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  async function startSession(categoryId: string) {
    try {
      const { data } = await apiClient.post('/v1/sessions', { category_id: categoryId });
      router.push({ pathname: '/(app)/solo/quiz', params: { sessionId: data.data.id } });
    } catch (e: any) {
      setError(e.message);
    }
  }

  if (loading) return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Choisir une catégorie</Text>
      {error ? <Text style={styles.error}>{error}</Text> : null}
      <FlatList
        data={categories}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        renderItem={({ item }) => (
          <TouchableOpacity style={[styles.card, { borderLeftColor: item.color ?? colors.primary }]} onPress={() => startSession(item.id)}>
            <Text style={styles.icon}>{item.icon ?? '📚'}</Text>
            <Text style={styles.name}>{item.name}</Text>
          </TouchableOpacity>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  center:    { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background },
  title:     { fontSize: 22, fontWeight: '700', color: colors.text, marginBottom: spacing.lg },
  list:      { gap: spacing.sm },
  card:      { backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.lg, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderLeftWidth: 4 },
  icon:      { fontSize: 24 },
  name:      { fontSize: 17, fontWeight: '600', color: colors.text },
  error:     { color: colors.error, textAlign: 'center', marginBottom: spacing.md },
});
```

**Commit** : `feat(mobile): category selection screen`

---

### Tâche D3 — Écran Quiz Solo

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/solo/quiz.tsx` (créer)

**Code complet** :
```typescript
import { useEffect, useState, useCallback } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { colors, spacing, radius } from '../../../src/theme';

interface Choice { id: string; text: string; }
interface Question { id: string; text: string; estimated_time_seconds: number; choices: Choice[]; }

type AnswerState = { selectedId: string; correctId: string; isCorrect: boolean; explanation: string | null; score: number; } | null;

export default function SoloQuizScreen() {
  const { sessionId } = useLocalSearchParams<{ sessionId: string }>();
  const router = useRouter();

  const [question, setQuestion]     = useState<Question | null>(null);
  const [loading, setLoading]       = useState(true);
  const [answerState, setAnswer]    = useState<AnswerState>(null);
  const [score, setScore]           = useState(0);
  const [questionCount, setCount]   = useState(0);

  const fetchNext = useCallback(async () => {
    setLoading(true);
    setAnswer(null);
    try {
      const { data } = await apiClient.get(`/v1/sessions/${sessionId}/next-question`);
      if (data.data === null) {
        // Terminer la session
        await apiClient.post(`/v1/sessions/${sessionId}/complete`);
        router.replace({ pathname: '/(app)/solo/results', params: { sessionId, score: score.toString() } });
        return;
      }
      setQuestion(data.data.question);
      setCount((c) => c + 1);
    } catch (e: any) {
      // En cas d'erreur, on termine
      router.replace({ pathname: '/(app)/solo/results', params: { sessionId, score: score.toString() } });
    } finally {
      setLoading(false);
    }
  }, [sessionId, score]);

  useEffect(() => { fetchNext(); }, []);

  async function submitAnswer(choiceId: string) {
    if (answerState) return;
    try {
      const { data } = await apiClient.post(`/v1/sessions/${sessionId}/answers`, {
        question_id: question!.id,
        choice_id: choiceId,
      });
      const d = data.data;
      setAnswer({ selectedId: choiceId, correctId: d.correct_choice_id, isCorrect: d.is_correct, explanation: d.explanation, score: d.score });
      setScore(d.score);
    } catch {}
  }

  if (loading) return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;
  if (!question) return null;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.counter}>Question {questionCount}</Text>
        <Text style={styles.scoreText}>Score : {score}</Text>
      </View>

      <Text style={styles.questionText}>{question.text}</Text>

      <View style={styles.choices}>
        {question.choices.map((choice) => {
          let bg = colors.surface;
          if (answerState) {
            if (choice.id === answerState.correctId)     bg = '#14532d';
            else if (choice.id === answerState.selectedId) bg = '#450a0a';
          }
          return (
            <TouchableOpacity
              key={choice.id}
              style={[styles.choice, { backgroundColor: bg }]}
              onPress={() => submitAnswer(choice.id)}
              disabled={!!answerState}
            >
              <Text style={styles.choiceText}>{choice.text}</Text>
            </TouchableOpacity>
          );
        })}
      </View>

      {answerState && (
        <View style={styles.feedback}>
          <Text style={[styles.feedbackTitle, { color: answerState.isCorrect ? colors.success : colors.error }]}>
            {answerState.isCorrect ? '✓ Correct !' : '✗ Incorrect'}
          </Text>
          {answerState.explanation && <Text style={styles.explanation}>{answerState.explanation}</Text>}
          <TouchableOpacity style={styles.nextBtn} onPress={fetchNext}>
            <Text style={styles.nextBtnText}>Question suivante →</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container:     { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  center:        { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background },
  header:        { flexDirection: 'row', justifyContent: 'space-between', marginBottom: spacing.xl },
  counter:       { color: colors.textMuted, fontSize: 15 },
  scoreText:     { color: colors.primary, fontSize: 15, fontWeight: '700' },
  questionText:  { fontSize: 20, fontWeight: '700', color: colors.text, marginBottom: spacing.xl, lineHeight: 28 },
  choices:       { gap: spacing.sm },
  choice:        { padding: spacing.lg, borderRadius: radius.md, borderWidth: 1, borderColor: colors.border },
  choiceText:    { color: colors.text, fontSize: 16 },
  feedback:      { marginTop: spacing.xl, backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.lg },
  feedbackTitle: { fontSize: 18, fontWeight: '700', marginBottom: spacing.sm },
  explanation:   { color: colors.textMuted, fontSize: 14, lineHeight: 20, marginBottom: spacing.md },
  nextBtn:       { backgroundColor: colors.primary, padding: spacing.md, borderRadius: radius.md, alignItems: 'center' },
  nextBtnText:   { color: '#fff', fontWeight: '700', fontSize: 16 },
});
```

**Commit** : `feat(mobile): solo quiz screen with adaptive difficulty`

---

### Tâche D4 — Écran Résultats Solo

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/solo/results.tsx` (créer)

**Code complet** :
```typescript
import { View, Text, StyleSheet } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

export default function SoloResultsScreen() {
  const router = useRouter();
  const { score } = useLocalSearchParams<{ score: string }>();
  const finalScore = parseInt(score ?? '0', 10);

  const medal = finalScore >= 8 ? '🥇' : finalScore >= 5 ? '🥈' : '🥉';

  return (
    <View style={styles.container}>
      <Text style={styles.medal}>{medal}</Text>
      <Text style={styles.title}>Partie terminée !</Text>
      <View style={styles.scoreBox}>
        <Text style={styles.scoreLabel}>Score final</Text>
        <Text style={styles.scoreValue}>{finalScore}</Text>
      </View>

      <View style={styles.actions}>
        <Button label="Rejouer" onPress={() => router.push('/(app)/solo/categories')} />
        <Button label="Accueil" onPress={() => router.replace('/(app)')} variant="outline" />
        <Button label="Classement" onPress={() => router.push('/(app)/leaderboard')} variant="ghost" />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container:  { flex: 1, backgroundColor: colors.background, padding: spacing.xl, justifyContent: 'center', alignItems: 'center' },
  medal:      { fontSize: 80, marginBottom: spacing.md },
  title:      { fontSize: 28, fontWeight: '800', color: colors.text, marginBottom: spacing.xl },
  scoreBox:   { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.xl, alignItems: 'center', marginBottom: spacing.xl, width: '100%', borderWidth: 1, borderColor: colors.border },
  scoreLabel: { color: colors.textMuted, fontSize: 16, marginBottom: spacing.xs },
  scoreValue: { fontSize: 64, fontWeight: '900', color: colors.primary },
  actions:    { width: '100%', gap: spacing.sm },
});
```

**Commit** : `feat(mobile): solo results screen`

---

## BLOC E — Écrans Multijoueur

### Tâche E1 — Écran Lobby (créer / rejoindre)

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/multi/lobby.tsx` (créer)

**Code complet** :
```typescript
import { useState } from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import { useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { Input } from '../../../src/components/Input';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

export default function LobbyScreen() {
  const router = useRouter();
  const [code, setCode]           = useState('');
  const [joinLoading, setJoinL]   = useState(false);
  const [createLoading, setCreateL] = useState(false);
  const [error, setError]         = useState('');

  async function createLobby() {
    setError('');
    setCreateL(true);
    try {
      // Récupérer les catégories et prendre la première pour la démo
      const { data: cats } = await apiClient.get('/v1/categories');
      const categoryId = cats.data[0]?.id;
      if (!categoryId) { setError('Aucune catégorie disponible'); return; }

      const { data } = await apiClient.post('/v1/lobbies', { category_id: categoryId, max_players: 4 });
      router.push({ pathname: '/(app)/multi/waiting', params: { lobbyId: data.data.id, isHost: '1' } });
    } catch (e: any) {
      setError(e.message);
    } finally {
      setCreateL(false);
    }
  }

  async function joinLobby() {
    setError('');
    if (code.trim().length !== 6) { setError('Le code doit faire 6 caractères'); return; }
    setJoinL(true);
    try {
      const { data } = await apiClient.post('/v1/lobbies/join', { code: code.trim().toUpperCase() });
      router.push({ pathname: '/(app)/multi/waiting', params: { lobbyId: data.data.id, isHost: '0' } });
    } catch (e: any) {
      setError(e.message);
    } finally {
      setJoinL(false);
    }
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <Text style={styles.title}>Multijoueur</Text>

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Créer un lobby</Text>
        <Text style={styles.sectionDesc}>Tu seras l'hôte de la partie. Un code sera généré pour inviter des amis.</Text>
        <Button label="Créer une partie" onPress={createLobby} loading={createLoading} />
      </View>

      <View style={styles.divider}><Text style={styles.dividerText}>— ou —</Text></View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Rejoindre un lobby</Text>
        <Input
          label="Code d'invitation (6 lettres)"
          value={code}
          onChangeText={(t) => setCode(t.toUpperCase())}
          autoCapitalize="characters"
          maxLength={6}
          placeholder="Ex: ABC123"
        />
        <Button label="Rejoindre" onPress={joinLobby} loading={joinLoading} variant="outline" />
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container:   { flex: 1, backgroundColor: colors.background },
  content:     { padding: spacing.xl },
  title:       { fontSize: 26, fontWeight: '800', color: colors.text, marginBottom: spacing.xl },
  section:     { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.lg, borderWidth: 1, borderColor: colors.border, gap: spacing.md },
  sectionTitle: { fontSize: 18, fontWeight: '700', color: colors.text },
  sectionDesc: { color: colors.textMuted, fontSize: 14, lineHeight: 20 },
  divider:     { alignItems: 'center', marginVertical: spacing.lg },
  dividerText: { color: colors.textMuted, fontSize: 14 },
  error:       { color: colors.error, backgroundColor: '#450a0a', padding: spacing.md, borderRadius: radius.md, marginBottom: spacing.md, textAlign: 'center' },
});
```

**Commit** : `feat(mobile): lobby create/join screen`

---

### Tâche E2 — Écran Salle d'attente (WebSocket)

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/multi/waiting.tsx` (créer)

**Code complet** :
```typescript
import { useEffect, useState, useRef } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { getEcho, destroyEcho } from '../../../src/lib/echo';
import { useAuthStore } from '../../../src/store/authStore';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

interface Participant { user_id: string; name: string; score: number; }
interface LobbyData { id: string; code: string; status: string; host_user_id: string; max_players: number; participants: Participant[]; }

export default function WaitingRoomScreen() {
  const router = useRouter();
  const { lobbyId, isHost } = useLocalSearchParams<{ lobbyId: string; isHost: string }>();
  const { user } = useAuthStore();
  const isHostBool = isHost === '1';

  const [lobby, setLobby]       = useState<LobbyData | null>(null);
  const [loading, setLoading]   = useState(true);
  const [starting, setStarting] = useState(false);
  const [error, setError]       = useState('');
  const channelRef = useRef<any>(null);

  useEffect(() => {
    // Charger les infos du lobby
    apiClient.get(`/v1/lobbies/${lobbyId}`)
      .then(({ data }) => setLobby(data.data))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));

    // S'abonner au canal presence
    let mounted = true;
    (async () => {
      const echo = await getEcho();
      const channel = echo.join(`lobby.${lobbyId}`);

      channel
        .listen('.player.joined', (e: { participants: Participant[] }) => {
          if (mounted) setLobby((prev) => prev ? { ...prev, participants: e.participants } : prev);
        })
        .listen('.player.left', (e: { participants: Participant[] }) => {
          if (mounted) setLobby((prev) => prev ? { ...prev, participants: e.participants } : prev);
        })
        .listen('.lobby.started', (e: { session_map: Record<string, string> }) => {
          if (!mounted) return;
          const mySessionId = e.session_map[user!.id];
          if (mySessionId) {
            router.replace({ pathname: '/(app)/multi/quiz', params: { sessionId: mySessionId, lobbyId, isHost } });
          }
        });

      channelRef.current = channel;
    })();

    return () => {
      mounted = false;
      channelRef.current?.stopListening('.player.joined');
      channelRef.current?.stopListening('.player.left');
      channelRef.current?.stopListening('.lobby.started');
    };
  }, [lobbyId]);

  async function startGame() {
    setStarting(true);
    setError('');
    try {
      await apiClient.post(`/v1/lobbies/${lobbyId}/start`);
      // L'event LobbyStarted sera reçu via WebSocket
    } catch (e: any) {
      setError(e.message);
      setStarting(false);
    }
  }

  if (loading) return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Salle d'attente</Text>

      <View style={styles.codeBox}>
        <Text style={styles.codeLabel}>Code d'invitation</Text>
        <Text style={styles.code}>{lobby?.code}</Text>
      </View>

      <Text style={styles.sectionTitle}>
        Joueurs ({lobby?.participants.length ?? 0}/{lobby?.max_players})
      </Text>

      <FlatList
        data={lobby?.participants ?? []}
        keyExtractor={(item) => item.user_id}
        style={styles.list}
        renderItem={({ item }) => (
          <View style={styles.playerRow}>
            <Text style={styles.playerName}>{item.name}</Text>
            {item.user_id === lobby?.host_user_id && <Text style={styles.hostBadge}>Hôte</Text>}
          </View>
        )}
      />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      {isHostBool && (
        <Button
          label="Démarrer la partie"
          onPress={startGame}
          loading={starting}
          disabled={(lobby?.participants.length ?? 0) < 2}
        />
      )}
      {!isHostBool && (
        <Text style={styles.waitingText}>En attente du démarrage par l'hôte…</Text>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  center:       { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background },
  title:        { fontSize: 24, fontWeight: '800', color: colors.text, marginBottom: spacing.lg },
  codeBox:      { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.lg, alignItems: 'center', marginBottom: spacing.lg, borderWidth: 1, borderColor: colors.primary },
  codeLabel:    { color: colors.textMuted, fontSize: 13, marginBottom: spacing.xs },
  code:         { fontSize: 36, fontWeight: '900', color: colors.primary, letterSpacing: 8 },
  sectionTitle: { color: colors.textMuted, fontSize: 14, marginBottom: spacing.sm, textTransform: 'uppercase', letterSpacing: 1 },
  list:         { flex: 1, marginBottom: spacing.lg },
  playerRow:    { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.md, marginBottom: spacing.xs },
  playerName:   { color: colors.text, fontSize: 16, fontWeight: '500' },
  hostBadge:    { backgroundColor: colors.primary, paddingHorizontal: spacing.sm, paddingVertical: 2, borderRadius: radius.full, color: '#fff', fontSize: 11, fontWeight: '700' },
  error:        { color: colors.error, textAlign: 'center', marginBottom: spacing.md },
  waitingText:  { color: colors.textMuted, textAlign: 'center', fontSize: 14, marginTop: spacing.md },
});
```

**Commit** : `feat(mobile): waiting room screen with WebSocket presence`

---

### Tâche E3 — Écran Quiz Multijoueur

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/multi/quiz.tsx` (créer)

**Code complet** :
```typescript
import { useEffect, useState, useCallback } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { colors, spacing, radius } from '../../../src/theme';

// Le quiz multijoueur réutilise la même logique que le solo
// mais écoute aussi l'event game.completed pour naviguer vers les résultats
interface Choice { id: string; text: string; }
interface Question { id: string; text: string; estimated_time_seconds: number; choices: Choice[]; }
type AnswerState = { selectedId: string; correctId: string; isCorrect: boolean; explanation: string | null; score: number; } | null;

export default function MultiQuizScreen() {
  const { sessionId, lobbyId, isHost } = useLocalSearchParams<{ sessionId: string; lobbyId: string; isHost: string }>();
  const router = useRouter();

  const [question, setQuestion]   = useState<Question | null>(null);
  const [loading, setLoading]     = useState(true);
  const [answerState, setAnswer]  = useState<AnswerState>(null);
  const [score, setScore]         = useState(0);
  const [questionCount, setCount] = useState(0);

  const fetchNext = useCallback(async () => {
    setLoading(true);
    setAnswer(null);
    try {
      const { data } = await apiClient.get(`/v1/sessions/${sessionId}/next-question`);
      if (data.data === null) {
        // Compléter la session
        await apiClient.post(`/v1/sessions/${sessionId}/complete`);
        // Si hôte, déclencher la fin du lobby
        if (isHost === '1') {
          await apiClient.post(`/v1/lobbies/${lobbyId}/complete`);
        }
        router.replace({ pathname: '/(app)/multi/results', params: { lobbyId, score: score.toString() } });
        return;
      }
      setQuestion(data.data.question);
      setCount((c) => c + 1);
    } catch {
      router.replace({ pathname: '/(app)/multi/results', params: { lobbyId, score: score.toString() } });
    } finally {
      setLoading(false);
    }
  }, [sessionId, lobbyId, isHost, score]);

  useEffect(() => { fetchNext(); }, []);

  async function submitAnswer(choiceId: string) {
    if (answerState) return;
    try {
      const { data } = await apiClient.post(`/v1/sessions/${sessionId}/answers`, {
        question_id: question!.id,
        choice_id: choiceId,
      });
      const d = data.data;
      setAnswer({ selectedId: choiceId, correctId: d.correct_choice_id, isCorrect: d.is_correct, explanation: d.explanation, score: d.score });
      setScore(d.score);
    } catch {}
  }

  if (loading) return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;
  if (!question) return null;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.counter}>Q{questionCount} — Multijoueur</Text>
        <Text style={styles.scoreText}>Score : {score}</Text>
      </View>

      <Text style={styles.questionText}>{question.text}</Text>

      <View style={styles.choices}>
        {question.choices.map((choice) => {
          let bg = colors.surface;
          if (answerState) {
            if (choice.id === answerState.correctId)      bg = '#14532d';
            else if (choice.id === answerState.selectedId) bg = '#450a0a';
          }
          return (
            <TouchableOpacity
              key={choice.id}
              style={[styles.choice, { backgroundColor: bg }]}
              onPress={() => submitAnswer(choice.id)}
              disabled={!!answerState}
            >
              <Text style={styles.choiceText}>{choice.text}</Text>
            </TouchableOpacity>
          );
        })}
      </View>

      {answerState && (
        <View style={styles.feedback}>
          <Text style={[styles.feedbackTitle, { color: answerState.isCorrect ? colors.success : colors.error }]}>
            {answerState.isCorrect ? '✓ Correct !' : '✗ Incorrect'}
          </Text>
          {answerState.explanation && <Text style={styles.explanation}>{answerState.explanation}</Text>}
          <TouchableOpacity style={styles.nextBtn} onPress={fetchNext}>
            <Text style={styles.nextBtnText}>Question suivante →</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  center:       { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background },
  header:       { flexDirection: 'row', justifyContent: 'space-between', marginBottom: spacing.xl },
  counter:      { color: colors.textMuted, fontSize: 15 },
  scoreText:    { color: colors.primary, fontSize: 15, fontWeight: '700' },
  questionText: { fontSize: 20, fontWeight: '700', color: colors.text, marginBottom: spacing.xl, lineHeight: 28 },
  choices:      { gap: spacing.sm },
  choice:       { padding: spacing.lg, borderRadius: radius.md, borderWidth: 1, borderColor: colors.border },
  choiceText:   { color: colors.text, fontSize: 16 },
  feedback:     { marginTop: spacing.xl, backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.lg },
  feedbackTitle: { fontSize: 18, fontWeight: '700', marginBottom: spacing.sm },
  explanation:  { color: colors.textMuted, fontSize: 14, lineHeight: 20, marginBottom: spacing.md },
  nextBtn:      { backgroundColor: colors.primary, padding: spacing.md, borderRadius: radius.md, alignItems: 'center' },
  nextBtnText:  { color: '#fff', fontWeight: '700', fontSize: 16 },
});
```

**Commit** : `feat(mobile): multiplayer quiz screen`

---

### Tâche E4 — Écran Résultats Multijoueur

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/multi/results.tsx` (créer)

**Code complet** :
```typescript
import { useEffect, useState } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

interface LeaderboardEntry { user_id: string; name: string; score: number; }

export default function MultiResultsScreen() {
  const router = useRouter();
  const { lobbyId } = useLocalSearchParams<{ lobbyId: string }>();
  const [leaderboard, setLeaderboard] = useState<LeaderboardEntry[]>([]);
  const [loading, setLoading]         = useState(true);

  useEffect(() => {
    // Récupérer les scores des participants via le lobby
    apiClient.get(`/v1/lobbies/${lobbyId}`)
      .then(({ data }) => {
        const sorted = [...data.data.participants].sort((a: any, b: any) => b.score - a.score);
        setLeaderboard(sorted.map((p: any) => ({ user_id: p.user_id, name: p.name, score: p.score })));
      })
      .finally(() => setLoading(false));
  }, [lobbyId]);

  if (loading) return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;

  const medals = ['🥇', '🥈', '🥉'];

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Résultats finaux</Text>

      <FlatList
        data={leaderboard}
        keyExtractor={(item) => item.user_id}
        style={styles.list}
        renderItem={({ item, index }) => (
          <View style={[styles.row, index === 0 && styles.first]}>
            <Text style={styles.rank}>{medals[index] ?? `#${index + 1}`}</Text>
            <Text style={styles.name}>{item.name}</Text>
            <Text style={styles.score}>{item.score} pts</Text>
          </View>
        )}
      />

      <View style={styles.actions}>
        <Button label="Rejouer" onPress={() => router.push('/(app)/multi/lobby')} />
        <Button label="Accueil" onPress={() => router.replace('/(app)')} variant="outline" />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background, padding: spacing.xl },
  center:    { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background },
  title:     { fontSize: 26, fontWeight: '800', color: colors.text, marginBottom: spacing.xl },
  list:      { flex: 1 },
  row:       { flexDirection: 'row', alignItems: 'center', backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.lg, marginBottom: spacing.sm, borderWidth: 1, borderColor: colors.border },
  first:     { borderColor: '#fbbf24', borderWidth: 2 },
  rank:      { fontSize: 24, marginRight: spacing.md },
  name:      { flex: 1, color: colors.text, fontSize: 16, fontWeight: '600' },
  score:     { color: colors.primary, fontWeight: '700', fontSize: 16 },
  actions:   { gap: spacing.sm, marginTop: spacing.lg },
});
```

**Commit** : `feat(mobile): multiplayer results screen`

---

## BLOC F — Leaderboard

### Tâche F1 — Écran Leaderboard

**Agent** : mobile-agent

**Fichiers concernés** :
- `mobile/app/(app)/leaderboard.tsx` (créer)

**Code complet** :
```typescript
import { useEffect, useState } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator, RefreshControl } from 'react-native';
import { apiClient } from '../../src/lib/api';
import { colors, spacing, radius } from '../../src/theme';

interface Row { rank: number; user_id: string; name: string; total_score: number; sessions_count: number; }

export default function LeaderboardScreen() {
  const [rows, setRows]         = useState<Row[]>([]);
  const [loading, setLoading]   = useState(true);
  const [refreshing, setRefresh] = useState(false);

  async function load(refresh = false) {
    if (refresh) setRefresh(true);
    try {
      const { data } = await apiClient.get('/v1/leaderboard');
      setRows(data.data);
    } finally {
      setLoading(false);
      setRefresh(false);
    }
  }

  useEffect(() => { load(); }, []);

  const medals: Record<number, string> = { 1: '🥇', 2: '🥈', 3: '🥉' };

  if (loading) return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;

  return (
    <View style={styles.container}>
      <Text style={styles.title}>🏆 Classement</Text>
      <FlatList
        data={rows}
        keyExtractor={(item) => item.user_id}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.primary} />}
        renderItem={({ item }) => (
          <View style={[styles.row, item.rank <= 3 && styles.topRow]}>
            <Text style={styles.rank}>{medals[item.rank] ?? `#${item.rank}`}</Text>
            <View style={styles.info}>
              <Text style={styles.name}>{item.name}</Text>
              <Text style={styles.sessions}>{item.sessions_count} partie{item.sessions_count > 1 ? 's' : ''}</Text>
            </View>
            <Text style={styles.score}>{item.total_score} pts</Text>
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  center:    { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background },
  title:     { fontSize: 24, fontWeight: '800', color: colors.text, marginBottom: spacing.lg },
  row:       { flexDirection: 'row', alignItems: 'center', backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.md, marginBottom: spacing.xs, borderWidth: 1, borderColor: colors.border },
  topRow:    { borderColor: colors.primary },
  rank:      { fontSize: 22, marginRight: spacing.md, minWidth: 40 },
  info:      { flex: 1 },
  name:      { color: colors.text, fontSize: 15, fontWeight: '600' },
  sessions:  { color: colors.textMuted, fontSize: 12, marginTop: 2 },
  score:     { color: colors.primary, fontWeight: '700', fontSize: 16 },
});
```

**Commit** : `feat(mobile): leaderboard screen`

---

## BLOC G — Tests

### Tâche G1 — Tests backend : Events Reverb

**Agent** : testing-agent

**Fichiers concernés** :
- `tests/Feature/LobbyBroadcastTest.php` (créer)

**Tests à écrire avec Pest** :
- `it('broadcasts LobbyPlayerJoined when a user joins', ...)` → fake les events, POST /lobbies/join, assert LobbyPlayerJoined dispatched
- `it('broadcasts LobbyPlayerLeft when a user leaves', ...)` → idem pour leave
- `it('broadcasts LobbyStarted with session_map when host starts', ...)` → assert LobbyStarted dispatched avec session_map contenant les user_ids des participants
- `it('broadcasts LobbyGameCompleted when lobby is completed', ...)` → assert LobbyGameCompleted dispatched

**Commit** : `test(lobby): broadcast events tests`

---

### Tâche G2 — Tests mobile : Auth Store

**Agent** : testing-agent

**Fichiers concernés** :
- `mobile/__tests__/authStore.test.ts` (créer)

**Tests Jest à écrire** :
- mock `expo-secure-store` et `axios`
- `login() stores token and user in SecureStore and updates state`
- `logout() clears SecureStore and resets state`
- `register() creates account and stores credentials`
- `loadFromStorage() restores state from SecureStore on boot`

**Commit** : `test(mobile): auth store unit tests`

---

## Ordre d'exécution recommandé

```
Parallèle 1 :
  ├── [A1] Installer Reverb
  └── [B1] Créer projet Expo + [B2] Installer dépendances

Séquentiel A (backend) :
  A2 → A3 → A4 → A5 → A6 → A7 → A8

Séquentiel B (mobile infra) :
  B3 → B4 → B5 → B6

Parallèle 2 (après B6) :
  ├── [C1 → C2] Auth screens
  └── [D1 → D2 → D3 → D4] Solo screens

Séquentiel E (après A7 et Parallèle 2) :
  E1 → E2 → E3 → E4

Parallèle 3 :
  ├── [F1] Leaderboard
  └── [G1 + G2] Tests

```

---

## Variables d'environnement à créer

Créer `mobile/.env` :
```
EXPO_PUBLIC_API_HOST=192.168.1.42
```
*(Remplacer par la vraie IP LAN de la machine)*

## Commandes pour démarrer

```bash
# Terminal 1 — Backend Laravel
php artisan serve --host=0.0.0.0

# Terminal 2 — Reverb WebSocket
php artisan reverb:start --host=0.0.0.0 --port=8080

# Terminal 3 — Mobile Expo
cd mobile
npx expo start
# Scanner le QR code avec l'app Expo Go sur le téléphone
```
