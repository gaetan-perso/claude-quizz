# Plan : Client Web Multijoueur
Date : 2026-04-16
Objectif : Permettre à des joueurs web (navigateur) de jouer en multijoueur avec des joueurs mobile via le même backend Laravel + WebSocket Reverb.
Architecture : SPA React 18 dans `resources/js/web/`, compilée par Vite, servie par Laravel via une route catch-all. Même API REST Sanctum + même channel WebSocket Reverb que le mobile.
Stack : Laravel 13 / PHP 8.3+ / React 18 / React Router v6 / TailwindCSS v4 / laravel-echo + pusher-js

---

## Vue d'ensemble

Le client web partage exactement les mêmes endpoints que le mobile :
- `POST /api/v1/auth/login` et `/api/v1/auth/register`
- `POST /api/v1/lobbies`, `GET /api/v1/lobbies/{id}`, `POST /api/v1/lobbies/join`, `POST /api/v1/lobbies/{id}/start`, `POST /api/v1/lobbies/{id}/complete`
- `GET /api/v1/sessions/{id}/next-question`, `POST /api/v1/sessions/{id}/answers`, `POST /api/v1/sessions/{id}/complete`
- WebSocket Reverb port 8080, PresenceChannel `lobby.{lobbyId}`

Token Sanctum stocké dans `localStorage`. Aucun nouveau endpoint backend nécessaire.

---

## Tâche 1 — Installer les dépendances npm

**Agent** : mobile-agent (ou exécution manuelle)

**Fichiers concernés** :
- `package.json` (modifier)

**Code complet** :

Remplacer le contenu de `package.json` par :

```json
{
    "$schema": "https://www.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "dependencies": {
        "laravel-echo": "^1.16.0",
        "pusher-js": "^8.4.0",
        "react": "^18.3.1",
        "react-dom": "^18.3.1",
        "react-router-dom": "^6.28.0"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "@types/react": "^18.3.12",
        "@types/react-dom": "^18.3.1",
        "@vitejs/plugin-react": "^4.3.4",
        "axios": ">=1.11.0 <=1.14.0",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^3.0.0",
        "tailwindcss": "^4.0.0",
        "typescript": "^5.7.2",
        "vite": "^8.0.0"
    }
}
```

**Commande de vérification** :
```bash
cd /var/www && npm install && npm ls react react-dom react-router-dom laravel-echo pusher-js
# Expected : liste des packages sans erreur
```

**Commit** : `chore(web): add React 18, react-router-dom, laravel-echo, pusher-js`

---

## Tâche 2 — Mettre à jour vite.config.js

**Agent** : mobile-agent

**Fichiers concernés** :
- `vite.config.js` (modifier)

**Code complet** :

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/web/main.tsx',
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

**Commande de vérification** :
```bash
cd /var/www && npm run build 2>&1 | tail -5
# Expected : ✓ built in Xs  (pas d'erreur TypeScript ni Vite)
```

**Commit** : `chore(web): configure Vite to compile React SPA entry`

---

## Tâche 3 — Créer tsconfig.json pour le web

**Agent** : mobile-agent

**Fichiers concernés** :
- `tsconfig.json` (créer)

**Code complet** :

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": false,
    "noUnusedParameters": false,
    "noFallthroughCasesInSwitch": true
  },
  "include": ["resources/js/web"]
}
```

**Commande de vérification** :
```bash
cd /var/www && npx tsc --noEmit 2>&1
# Expected : aucune sortie (pas d'erreur)
```

**Commit** : `chore(web): add tsconfig.json for React SPA`

---

## Tâche 4 — Blade SPA shell + route catch-all

**Agent** : backend-agent

**Fichiers concernés** :
- `resources/views/spa.blade.php` (créer)
- `routes/web.php` (modifier)

**Code complet** :

`resources/views/spa.blade.php` :
```blade
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quiz Multijoueur</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/web/main.tsx'])
</head>
<body class="bg-slate-900">
    <div id="root"></div>
</body>
</html>
```

`routes/web.php` :
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SPA catch-all — toutes les routes /app/* sont gérées par React Router
Route::get('/app/{any?}', function () {
    return view('spa');
})->where('any', '.*');
```

**Commande de vérification** :
```bash
php artisan route:list | grep spa
# Expected : GET|HEAD  app/{any?}  ...  spa
```

**Commit** : `feat(web): add Blade SPA shell and Laravel catch-all route`

---

## Tâche 5 — Créer lib/api.ts (client Axios web)

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/lib/api.ts` (créer)

**Code complet** :

```typescript
import axios from 'axios';

const API_HOST = import.meta.env.VITE_API_HOST ?? window.location.hostname;
const API_BASE = `http://${API_HOST}:8000/api`;

export const apiClient = axios.create({
    baseURL: API_BASE,
    timeout: 10_000,
    headers: { 'Accept': 'application/json' },
});

// Injecter le Bearer token depuis localStorage à chaque requête
apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Normaliser les erreurs API
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const message =
            error?.response?.data?.message ??
            error?.message ??
            'Erreur réseau';
        return Promise.reject(new Error(message));
    },
);
```

**Commande de vérification** :
```bash
cd /var/www && npx tsc --noEmit 2>&1
# Expected : aucune erreur TypeScript
```

**Commit** : `feat(web): add Axios API client with localStorage Bearer token`

---

## Tâche 6 — Créer lib/echo.ts (client WebSocket web)

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/lib/echo.ts` (créer)

**Code complet** :

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window { Pusher: typeof Pusher; }
}

window.Pusher = Pusher;

const API_HOST   = import.meta.env.VITE_API_HOST ?? window.location.hostname;
const REVERB_KEY = import.meta.env.VITE_REVERB_APP_KEY ?? 'quiz-app-key';

let echoInstance: Echo<'reverb'> | null = null;

export function getEcho(): Echo<'reverb'> {
    if (echoInstance) return echoInstance;

    const token = localStorage.getItem('auth_token') ?? '';

    echoInstance = new Echo({
        broadcaster: 'reverb',
        key: REVERB_KEY,
        wsHost: API_HOST,
        wsPort: 8080,
        wssPort: 8080,
        forceTLS: false,
        enabledTransports: ['ws'],
        authEndpoint: `http://${API_HOST}:8000/broadcasting/auth`,
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

**Commande de vérification** :
```bash
cd /var/www && npx tsc --noEmit 2>&1
# Expected : aucune erreur TypeScript
```

**Commit** : `feat(web): add Laravel Echo WebSocket client for Reverb`

---

## Tâche 7 — Créer store/authStore.tsx (contexte auth React)

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/store/authStore.tsx` (créer)

**Code complet** :

```typescript
import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { apiClient } from '../lib/api';
import { destroyEcho } from '../lib/echo';

interface User { id: string; name: string; email: string; }

interface AuthState {
    user: User | null;
    token: string | null;
    loading: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (name: string, email: string, password: string) => Promise<void>;
    logout: () => void;
}

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser]     = useState<User | null>(null);
    const [token, setToken]   = useState<string | null>(() => localStorage.getItem('auth_token'));
    const [loading, setLoading] = useState(true);

    // Au démarrage : si on a un token, on charge le profil utilisateur
    useEffect(() => {
        if (!token) { setLoading(false); return; }

        apiClient.get('/v1/user')
            .then(({ data }) => setUser(data))
            .catch(() => {
                localStorage.removeItem('auth_token');
                setToken(null);
            })
            .finally(() => setLoading(false));
    }, []);

    async function login(email: string, password: string) {
        const { data } = await apiClient.post('/v1/auth/login', { email, password });
        const { token: t, user: u } = data;
        localStorage.setItem('auth_token', t);
        setToken(t);
        setUser(u);
    }

    async function register(name: string, email: string, password: string) {
        const { data } = await apiClient.post('/v1/auth/register', { name, email, password });
        const { token: t, user: u } = data;
        localStorage.setItem('auth_token', t);
        setToken(t);
        setUser(u);
    }

    function logout() {
        destroyEcho();
        localStorage.removeItem('auth_token');
        setToken(null);
        setUser(null);
    }

    return (
        <AuthContext.Provider value={{ user, token, loading, login, register, logout }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth(): AuthState {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error('useAuth must be used within AuthProvider');
    return ctx;
}
```

**Commande de vérification** :
```bash
cd /var/www && npx tsc --noEmit 2>&1
# Expected : aucune erreur TypeScript
```

**Commit** : `feat(web): add React auth context with localStorage persistence`

---

## Tâche 8 — Créer App.tsx et main.tsx (router + entry)

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/App.tsx` (créer)
- `resources/js/web/main.tsx` (créer)

**Code complet** :

`resources/js/web/App.tsx` :
```tsx
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './store/authStore';
import { LoginPage }   from './pages/LoginPage';
import { LobbyPage }   from './pages/LobbyPage';
import { WaitingPage } from './pages/WaitingPage';
import { QuizPage }    from './pages/QuizPage';
import { ResultsPage } from './pages/ResultsPage';

function RequireAuth({ children }: { children: React.ReactNode }) {
    const { user, loading } = useAuth();
    if (loading) {
        return (
            <div className="min-h-screen bg-slate-900 flex items-center justify-center">
                <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
            </div>
        );
    }
    return user ? <>{children}</> : <Navigate to="/app/login" replace />;
}

export default function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <Routes>
                    <Route path="/app/login"   element={<LoginPage />} />
                    <Route path="/app/lobby"   element={<RequireAuth><LobbyPage /></RequireAuth>} />
                    <Route path="/app/waiting" element={<RequireAuth><WaitingPage /></RequireAuth>} />
                    <Route path="/app/quiz"    element={<RequireAuth><QuizPage /></RequireAuth>} />
                    <Route path="/app/results" element={<RequireAuth><ResultsPage /></RequireAuth>} />
                    <Route path="/app/*"       element={<Navigate to="/app/login" replace />} />
                </Routes>
            </BrowserRouter>
        </AuthProvider>
    );
}
```

`resources/js/web/main.tsx` :
```tsx
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';

const root = document.getElementById('root');
if (!root) throw new Error('Element #root introuvable');

createRoot(root).render(
    <StrictMode>
        <App />
    </StrictMode>,
);
```

**Commande de vérification** :
```bash
cd /var/www && npm run build 2>&1 | grep -E "(error|Error|built)"
# Expected : ✓ built in Xs
```

**Commit** : `feat(web): add React Router setup and SPA entry point`

---

## Tâche 9 — Créer LoginPage.tsx

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/pages/LoginPage.tsx` (créer)

**Code complet** :

```tsx
import { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../store/authStore';

type Mode = 'login' | 'register';

export function LoginPage() {
    const { login, register, user } = useAuth();
    const navigate = useNavigate();

    const [mode, setMode]         = useState<Mode>('login');
    const [name, setName]         = useState('');
    const [email, setEmail]       = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading]   = useState(false);
    const [error, setError]       = useState('');

    // Si déjà connecté, rediriger vers le lobby
    if (user) {
        navigate('/app/lobby', { replace: true });
        return null;
    }

    async function handleSubmit(e: FormEvent) {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            if (mode === 'login') {
                await login(email, password);
            } else {
                await register(name, email, password);
            }
            navigate('/app/lobby', { replace: true });
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Erreur inattendue');
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="min-h-screen bg-slate-900 flex items-center justify-center px-4">
            <div className="w-full max-w-sm">
                <h1 className="text-3xl font-extrabold text-slate-100 text-center mb-8">
                    Quiz Multijoueur
                </h1>

                <div className="bg-slate-800 rounded-2xl border border-slate-700 p-6">
                    {/* Onglets */}
                    <div className="flex rounded-xl bg-slate-900 p-1 mb-6">
                        {(['login', 'register'] as Mode[]).map((m) => (
                            <button
                                key={m}
                                type="button"
                                onClick={() => { setMode(m); setError(''); }}
                                className={`flex-1 py-2 rounded-lg text-sm font-semibold transition-colors ${
                                    mode === m
                                        ? 'bg-indigo-500 text-white'
                                        : 'text-slate-400 hover:text-slate-200'
                                }`}
                            >
                                {m === 'login' ? 'Connexion' : 'Inscription'}
                            </button>
                        ))}
                    </div>

                    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                        {mode === 'register' && (
                            <div>
                                <label className="block text-xs uppercase tracking-widest text-slate-400 mb-1">
                                    Pseudo
                                </label>
                                <input
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    required
                                    placeholder="MonPseudo"
                                    className="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        )}

                        <div>
                            <label className="block text-xs uppercase tracking-widest text-slate-400 mb-1">
                                Email
                            </label>
                            <input
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                                placeholder="you@example.com"
                                className="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label className="block text-xs uppercase tracking-widest text-slate-400 mb-1">
                                Mot de passe
                            </label>
                            <input
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                                placeholder="••••••••"
                                className="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        {error && (
                            <p className="text-red-400 text-sm text-center bg-red-950 border border-red-800 rounded-xl px-4 py-2">
                                {error}
                            </p>
                        )}

                        <button
                            type="submit"
                            disabled={loading}
                            className="bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition-colors"
                        >
                            {loading
                                ? (mode === 'login' ? 'Connexion…' : 'Inscription…')
                                : (mode === 'login' ? 'Se connecter' : "S'inscrire")}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}
```

**Commande de vérification** :
```bash
cd /var/www && npm run build 2>&1 | grep -E "(error|Error|built)"
# Expected : ✓ built in Xs
```

**Commit** : `feat(web): add LoginPage with login/register tabs`

---

## Tâche 10 — Créer LobbyPage.tsx (créer / rejoindre une partie)

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/pages/LobbyPage.tsx` (créer)

**Code complet** :

```tsx
import { useState, useEffect, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../lib/api';
import { useAuth } from '../store/authStore';

interface Category {
    id: string;
    name: string;
    icon: string | null;
    color: string | null;
    question_count: number;
}

const QUESTION_COUNTS = [10, 20, 30] as const;

export function LobbyPage() {
    const navigate = useNavigate();
    const { logout, user } = useAuth();

    const [categories, setCategories] = useState<Category[]>([]);
    const [catsLoading, setCatsLoading] = useState(true);
    const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
    const [maxQuestions, setMaxQuestions] = useState<10 | 20 | 30>(10);
    const [createLoading, setCreateLoading] = useState(false);

    const [code, setCode]         = useState('');
    const [joinLoading, setJoinLoading] = useState(false);
    const [error, setError]       = useState('');

    useEffect(() => {
        apiClient.get('/v1/categories')
            .then(({ data }) => setCategories(data.data))
            .catch((e: unknown) => setError(e instanceof Error ? e.message : 'Erreur chargement'))
            .finally(() => setCatsLoading(false));
    }, []);

    function toggleCategory(id: string) {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    const totalAvailable = categories
        .filter((c) => selectedIds.has(c.id))
        .reduce((acc, c) => acc + c.question_count, 0);
    const effective = Math.min(maxQuestions, totalAvailable);

    async function createLobby(e: FormEvent) {
        e.preventDefault();
        if (selectedIds.size === 0 || createLoading) return;
        setError('');
        setCreateLoading(true);
        try {
            const { data } = await apiClient.post('/v1/lobbies', {
                category_ids:  Array.from(selectedIds),
                max_questions: effective,
                max_players:   4,
            });
            navigate(`/app/waiting?lobbyId=${data.data.id}&isHost=1`);
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Impossible de créer la partie');
        } finally {
            setCreateLoading(false);
        }
    }

    async function joinLobby(e: FormEvent) {
        e.preventDefault();
        if (code.trim().length !== 6) { setError('Le code doit faire 6 caractères.'); return; }
        setError('');
        setJoinLoading(true);
        try {
            const { data } = await apiClient.post('/v1/lobbies/join', { code: code.trim().toUpperCase() });
            navigate(`/app/waiting?lobbyId=${data.data.id}&isHost=0`);
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Code invalide ou partie introuvable');
        } finally {
            setJoinLoading(false);
        }
    }

    return (
        <div className="min-h-screen bg-slate-900 px-4 py-8">
            <div className="max-w-lg mx-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <h1 className="text-2xl font-extrabold text-slate-100">Multijoueur</h1>
                    <div className="flex items-center gap-3">
                        <span className="text-slate-400 text-sm">{user?.name}</span>
                        <button
                            onClick={logout}
                            className="text-xs text-slate-500 hover:text-slate-300 border border-slate-700 rounded-lg px-3 py-1"
                        >
                            Déconnexion
                        </button>
                    </div>
                </div>

                {error && (
                    <div className="mb-4 bg-red-950 border border-red-800 rounded-xl px-4 py-3 text-red-400 text-sm text-center">
                        {error}
                    </div>
                )}

                {/* Section Créer */}
                <div className="bg-slate-800 rounded-2xl border border-slate-700 p-6 mb-6">
                    <h2 className="text-lg font-bold text-slate-100 mb-1">Créer une partie</h2>
                    <p className="text-slate-400 text-sm mb-5">
                        Tu seras l'hôte. Un code sera généré pour inviter des amis.
                    </p>

                    {/* Nombre de questions */}
                    <p className="text-xs uppercase tracking-widest text-slate-400 mb-2">
                        Nombre de questions
                    </p>
                    <div className="flex gap-2 mb-5">
                        {QUESTION_COUNTS.map((count) => (
                            <button
                                key={count}
                                type="button"
                                onClick={() => setMaxQuestions(count)}
                                className={`flex-1 py-2 rounded-xl font-bold text-base border transition-colors ${
                                    maxQuestions === count
                                        ? 'bg-indigo-500 border-indigo-500 text-white'
                                        : 'bg-slate-900 border-slate-700 text-slate-400 hover:border-indigo-400'
                                }`}
                            >
                                {count}
                            </button>
                        ))}
                    </div>

                    {/* Catégories */}
                    <p className="text-xs uppercase tracking-widest text-slate-400 mb-2">Catégories</p>
                    {catsLoading ? (
                        <div className="flex items-center gap-2 text-slate-400 text-sm py-4">
                            <div className="w-4 h-4 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
                            Chargement…
                        </div>
                    ) : (
                        <div className="flex flex-col gap-2 mb-5">
                            {categories.map((cat) => {
                                const selected = selectedIds.has(cat.id);
                                return (
                                    <button
                                        key={cat.id}
                                        type="button"
                                        onClick={() => toggleCategory(cat.id)}
                                        style={{ borderLeftColor: cat.color ?? '#6366f1' }}
                                        className={`flex items-center gap-3 p-3 rounded-xl border border-l-4 transition-colors text-left ${
                                            selected
                                                ? 'bg-indigo-950 border-indigo-500'
                                                : 'bg-slate-900 border-slate-700 hover:border-slate-500'
                                        }`}
                                    >
                                        <span className="text-xl">{cat.icon ?? '📚'}</span>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-slate-100 font-semibold text-sm">{cat.name}</p>
                                            <p className="text-slate-400 text-xs">{cat.question_count} questions</p>
                                        </div>
                                        <div className={`w-5 h-5 rounded-full border flex items-center justify-center flex-shrink-0 ${
                                            selected ? 'bg-indigo-500 border-indigo-500' : 'border-slate-600'
                                        }`}>
                                            {selected && <span className="text-white text-xs font-bold">✓</span>}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    )}

                    <form onSubmit={createLobby}>
                        <button
                            type="submit"
                            disabled={selectedIds.size === 0 || createLoading}
                            className="w-full bg-indigo-500 hover:bg-indigo-600 disabled:opacity-45 text-white font-bold py-3 rounded-xl transition-colors"
                        >
                            {createLoading
                                ? 'Création…'
                                : selectedIds.size > 0
                                    ? `Créer la partie · ${effective} question${effective !== 1 ? 's' : ''}`
                                    : 'Sélectionne une catégorie'}
                        </button>
                    </form>
                </div>

                {/* Séparateur */}
                <div className="flex items-center gap-4 mb-6">
                    <div className="flex-1 h-px bg-slate-700" />
                    <span className="text-slate-500 text-sm">ou</span>
                    <div className="flex-1 h-px bg-slate-700" />
                </div>

                {/* Section Rejoindre */}
                <div className="bg-slate-800 rounded-2xl border border-slate-700 p-6">
                    <h2 className="text-lg font-bold text-slate-100 mb-5">Rejoindre une partie</h2>
                    <form onSubmit={joinLobby} className="flex flex-col gap-4">
                        <div>
                            <label className="block text-xs uppercase tracking-widest text-slate-400 mb-1">
                                Code d'invitation (6 lettres)
                            </label>
                            <input
                                type="text"
                                value={code}
                                onChange={(e) => setCode(e.target.value.toUpperCase())}
                                maxLength={6}
                                placeholder="ABC123"
                                className="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500 text-center text-2xl font-bold tracking-widest uppercase"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={joinLoading}
                            className="w-full border border-indigo-500 text-indigo-400 hover:bg-indigo-500 hover:text-white disabled:opacity-50 font-bold py-3 rounded-xl transition-colors"
                        >
                            {joinLoading ? 'Rejoindre…' : 'Rejoindre'}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}
```

**Commande de vérification** :
```bash
cd /var/www && npm run build 2>&1 | grep -E "(error|Error|built)"
# Expected : ✓ built in Xs
```

**Commit** : `feat(web): add LobbyPage with create/join multiplayer game`

---

## Tâche 11 — Créer WaitingPage.tsx (salle d'attente WebSocket)

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/pages/WaitingPage.tsx` (créer)

**Code complet** :

```tsx
import { useEffect, useState, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { apiClient } from '../lib/api';
import { getEcho, destroyEcho } from '../lib/echo';
import { useAuth } from '../store/authStore';

interface Participant { user_id: string; name: string; score: number; }
interface LobbyData {
    id: string; code: string; status: string;
    host_user_id: string; max_players: number;
    participants: Participant[];
}

export function WaitingPage() {
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const lobbyId  = params.get('lobbyId') ?? '';
    const isHost   = params.get('isHost') === '1';
    const { user } = useAuth();

    const [lobby, setLobby]       = useState<LobbyData | null>(null);
    const [loading, setLoading]   = useState(true);
    const [starting, setStarting] = useState(false);
    const [error, setError]       = useState('');
    const channelRef              = useRef<ReturnType<typeof getEcho>['join'] extends (...a: any[]) => infer R ? R : never | null>(null);

    useEffect(() => {
        let mounted = true;

        apiClient.get(`/v1/lobbies/${lobbyId}`)
            .then(({ data }) => { if (mounted) setLobby(data.data); })
            .catch((e: unknown) => { if (mounted) setError(e instanceof Error ? e.message : 'Erreur'); })
            .finally(() => { if (mounted) setLoading(false); });

        try {
            const echo    = getEcho();
            const channel = echo.join(`lobby.${lobbyId}`);

            channel
                .listen('.player.joined', (e: { participants: Participant[] }) => {
                    if (mounted) setLobby((prev) => prev ? { ...prev, participants: e.participants } : prev);
                })
                .listen('.player.left', (e: { participants: Participant[] }) => {
                    if (mounted) setLobby((prev) => prev ? { ...prev, participants: e.participants } : prev);
                })
                .listen('.lobby.started', (e: { session_map: Record<string, string> }) => {
                    if (!mounted || !user) return;
                    const mySessionId = e.session_map[user.id];
                    if (mySessionId) {
                        navigate(`/app/quiz?sessionId=${mySessionId}&lobbyId=${lobbyId}&isHost=${isHost ? '1' : '0'}`);
                    }
                });

            channelRef.current = channel as any;
        } catch {
            // WebSocket indisponible — continuer sans
        }

        return () => {
            mounted = false;
            channelRef.current?.stopListening('.player.joined');
            channelRef.current?.stopListening('.player.left');
            channelRef.current?.stopListening('.lobby.started');
            destroyEcho();
        };
    }, [lobbyId]);

    async function startGame() {
        setError('');
        setStarting(true);
        try {
            await apiClient.post(`/v1/lobbies/${lobbyId}/start`);
            // Navigation déclenchée par l'event .lobby.started via WebSocket
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Impossible de démarrer');
            setStarting(false);
        }
    }

    if (loading) {
        return (
            <div className="min-h-screen bg-slate-900 flex items-center justify-center">
                <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-slate-900 px-4 py-8">
            <div className="max-w-lg mx-auto">
                <h1 className="text-2xl font-extrabold text-slate-100 mb-6">Salle d'attente</h1>

                {/* Code d'invitation */}
                <div className="bg-slate-800 rounded-2xl border-2 border-indigo-500 p-6 text-center mb-6">
                    <p className="text-xs uppercase tracking-widest text-slate-400 mb-2">Code d'invitation</p>
                    <p className="text-5xl font-black text-indigo-400 tracking-[0.3em]">{lobby?.code}</p>
                    <p className="text-slate-500 text-xs mt-2">Partage ce code avec tes amis</p>
                </div>

                {/* Liste des joueurs */}
                <div className="bg-slate-800 rounded-2xl border border-slate-700 p-4 mb-6">
                    <p className="text-xs uppercase tracking-widest text-slate-400 mb-3">
                        Joueurs ({lobby?.participants.length ?? 0}/{lobby?.max_players})
                    </p>
                    <div className="flex flex-col gap-2">
                        {(lobby?.participants ?? []).map((p) => (
                            <div key={p.user_id} className="flex items-center gap-3 bg-slate-900 rounded-xl px-4 py-3">
                                <span className="flex-1 text-slate-100 font-medium">{p.name}</span>
                                {p.user_id === lobby?.host_user_id && (
                                    <span className="bg-indigo-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">Hôte</span>
                                )}
                                {p.user_id === user?.id && (
                                    <span className="border border-slate-600 text-slate-400 text-xs px-2 py-0.5 rounded-full">Moi</span>
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {error && (
                    <p className="text-red-400 text-sm text-center bg-red-950 border border-red-800 rounded-xl px-4 py-2 mb-4">
                        {error}
                    </p>
                )}

                {isHost ? (
                    <div className="flex flex-col gap-2">
                        {(lobby?.participants.length ?? 0) < 2 && (
                            <p className="text-slate-400 text-sm text-center">
                                En attente d'au moins 1 autre joueur…
                            </p>
                        )}
                        <button
                            onClick={startGame}
                            disabled={starting || (lobby?.participants.length ?? 0) < 2}
                            className="w-full bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 text-white font-bold py-4 rounded-xl text-lg transition-colors"
                        >
                            {starting
                                ? 'Démarrage…'
                                : `Démarrer (${lobby?.participants.length ?? 0}/${lobby?.max_players ?? 4} joueurs)`}
                        </button>
                    </div>
                ) : (
                    <div className="flex items-center gap-3 justify-center text-slate-400">
                        <div className="w-4 h-4 border-2 border-slate-600 border-t-indigo-400 rounded-full animate-spin" />
                        <p className="text-sm">En attente du démarrage par l'hôte…</p>
                    </div>
                )}
            </div>
        </div>
    );
}
```

**Commande de vérification** :
```bash
cd /var/www && npm run build 2>&1 | grep -E "(error|Error|built)"
# Expected : ✓ built in Xs
```

**Commit** : `feat(web): add WaitingPage with real-time participant list via WebSocket`

---

## Tâche 12 — Créer QuizPage.tsx

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/pages/QuizPage.tsx` (créer)

**Code complet** :

```tsx
import { useEffect, useRef, useState, useCallback } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { apiClient } from '../lib/api';
import { getEcho, destroyEcho } from '../lib/echo';

interface Choice   { id: string; text: string; }
interface Question { id: string; text: string; estimated_time_seconds: number; choices: Choice[]; }
interface AnswerResult {
    selectedId: string; correctId: string;
    isCorrect: boolean; explanation: string | null; score: number;
}

export function QuizPage() {
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const sessionId = params.get('sessionId') ?? '';
    const lobbyId   = params.get('lobbyId') ?? '';
    const isHost    = params.get('isHost') === '1';

    const [question, setQuestion] = useState<Question | null>(null);
    const [loading, setLoading]   = useState(true);
    const [result, setResult]     = useState<AnswerResult | null>(null);
    const [score, setScore]       = useState(0);
    const [questionNum, setNum]   = useState(0);
    const [submitting, setSubmit] = useState(false);
    const navigatedRef = useRef(false);

    const fetchNext = useCallback(async () => {
        setLoading(true);
        setResult(null);
        try {
            const { data } = await apiClient.get(`/v1/sessions/${sessionId}/next-question`);
            if (data.data === null) {
                if (navigatedRef.current) return;
                navigatedRef.current = true;
                await apiClient.post(`/v1/sessions/${sessionId}/complete`);
                if (isHost) {
                    await apiClient.post(`/v1/lobbies/${lobbyId}/complete`);
                }
                destroyEcho();
                navigate(`/app/results?lobbyId=${lobbyId}&score=${score}`);
                return;
            }
            setQuestion(data.data.question);
            setNum((n) => n + 1);
        } catch {
            if (navigatedRef.current) return;
            navigatedRef.current = true;
            destroyEcho();
            navigate(`/app/results?lobbyId=${lobbyId}&score=${score}`);
        } finally {
            setLoading(false);
        }
    }, [sessionId, lobbyId, isHost, score, navigate]);

    useEffect(() => { fetchNext(); }, []);

    // Listener WebSocket game.completed
    useEffect(() => {
        if (!lobbyId) return;
        let cleanup: (() => void) | null = null;

        try {
            const echo    = getEcho();
            const channel = echo.private(`lobby.${lobbyId}`);

            channel.listen('.game.completed', async () => {
                if (navigatedRef.current) return;
                navigatedRef.current = true;
                if (!isHost) {
                    try { await apiClient.post(`/v1/sessions/${sessionId}/complete`); } catch { /* ok */ }
                }
                destroyEcho();
                navigate(`/app/results?lobbyId=${lobbyId}&score=${score}`);
            });

            cleanup = () => echo.leave(`lobby.${lobbyId}`);
        } catch { /* WebSocket indisponible */ }

        return () => cleanup?.();
    }, [lobbyId, sessionId, isHost, score, navigate]);

    async function submitAnswer(choiceId: string) {
        if (result || submitting) return;
        setSubmit(true);
        try {
            const { data } = await apiClient.post(`/v1/sessions/${sessionId}/answers`, {
                question_id: question!.id,
                choice_id:   choiceId,
            });
            const d = data.data;
            setResult({ selectedId: choiceId, correctId: d.correct_choice_id, isCorrect: d.is_correct, explanation: d.explanation ?? null, score: d.score });
            setScore(d.score);
        } catch { /* ignorer */ } finally {
            setSubmit(false);
        }
    }

    function choiceClass(choiceId: string) {
        const base = 'w-full text-left p-4 rounded-xl border transition-colors';
        if (!result) return `${base} bg-slate-800 border-slate-700 hover:border-indigo-400 text-slate-100`;
        if (choiceId === result.correctId) return `${base} bg-green-950 border-green-500 text-green-200`;
        if (choiceId === result.selectedId) return `${base} bg-red-950 border-red-500 text-red-200`;
        return `${base} bg-slate-800 border-slate-700 text-slate-500 opacity-50`;
    }

    if (loading) {
        return (
            <div className="min-h-screen bg-slate-900 flex items-center justify-center">
                <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
            </div>
        );
    }

    if (!question) return null;

    return (
        <div className="min-h-screen bg-slate-900 px-4 py-8">
            <div className="max-w-2xl mx-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <span className="text-slate-400 text-sm">Q{questionNum} · Multi</span>
                    <span className="bg-slate-800 border border-indigo-500 text-indigo-400 font-bold text-sm px-4 py-1.5 rounded-full">
                        {score} pts
                    </span>
                </div>

                {/* Question */}
                <p className="text-xl font-bold text-slate-100 mb-8 leading-relaxed">{question.text}</p>

                {/* Choix */}
                <div className="flex flex-col gap-3 mb-6">
                    {question.choices.map((choice) => (
                        <button
                            key={choice.id}
                            onClick={() => submitAnswer(choice.id)}
                            disabled={!!result || submitting}
                            className={choiceClass(choice.id)}
                        >
                            {choice.text}
                        </button>
                    ))}
                </div>

                {/* Feedback */}
                {result && (
                    <div className="bg-slate-800 rounded-2xl border border-slate-700 p-5">
                        <p className={`text-lg font-bold mb-2 ${result.isCorrect ? 'text-green-400' : 'text-red-400'}`}>
                            {result.isCorrect ? '✓ Correct !' : '✗ Incorrect'}
                        </p>
                        {result.explanation && (
                            <p className="text-slate-400 text-sm leading-relaxed mb-4">{result.explanation}</p>
                        )}
                        <button
                            onClick={fetchNext}
                            className="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-3 rounded-xl transition-colors"
                        >
                            Question suivante →
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
```

**Commande de vérification** :
```bash
cd /var/www && npm run build 2>&1 | grep -E "(error|Error|built)"
# Expected : ✓ built in Xs
```

**Commit** : `feat(web): add QuizPage with answer submission and WebSocket game.completed listener`

---

## Tâche 13 — Créer ResultsPage.tsx (leaderboard)

**Agent** : mobile-agent

**Fichiers concernés** :
- `resources/js/web/pages/ResultsPage.tsx` (créer)

**Code complet** :

```tsx
import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { apiClient } from '../lib/api';
import { useAuth } from '../store/authStore';

interface Entry { user_id: string; name: string; score: number; }

const MEDALS: Record<number, string> = { 0: '🥇', 1: '🥈', 2: '🥉' };

export function ResultsPage() {
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const lobbyId  = params.get('lobbyId') ?? '';
    const { user } = useAuth();

    const [leaderboard, setLeaderboard] = useState<Entry[]>([]);
    const [loading, setLoading]         = useState(true);

    useEffect(() => {
        apiClient.get(`/v1/lobbies/${lobbyId}`)
            .then(({ data }) => {
                const sorted = [...data.data.participants]
                    .sort((a: any, b: any) => b.score - a.score)
                    .map((p: any) => ({ user_id: p.user_id, name: p.name, score: p.score }));
                setLeaderboard(sorted);
            })
            .finally(() => setLoading(false));
    }, [lobbyId]);

    if (loading) {
        return (
            <div className="min-h-screen bg-slate-900 flex items-center justify-center">
                <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-slate-900 px-4 py-8">
            <div className="max-w-lg mx-auto">
                <h1 className="text-2xl font-extrabold text-slate-100 mb-8">Résultats finaux</h1>

                <div className="flex flex-col gap-2 mb-8">
                    {leaderboard.map((entry, index) => (
                        <div
                            key={entry.user_id}
                            className={`flex items-center gap-4 rounded-2xl border px-5 py-4 ${
                                index === 0
                                    ? 'border-yellow-400 bg-yellow-950'
                                    : entry.user_id === user?.id
                                        ? 'border-indigo-500 bg-slate-800'
                                        : 'border-slate-700 bg-slate-800'
                            }`}
                        >
                            <span className="text-2xl w-8 flex-shrink-0">
                                {MEDALS[index] ?? `#${index + 1}`}
                            </span>
                            <span className={`flex-1 font-semibold text-base ${entry.user_id === user?.id ? 'text-indigo-300' : 'text-slate-100'}`}>
                                {entry.name}
                                {entry.user_id === user?.id && (
                                    <span className="ml-2 text-xs text-slate-400 font-normal">(moi)</span>
                                )}
                            </span>
                            <span className="text-indigo-400 font-bold text-base">{entry.score} pts</span>
                        </div>
                    ))}
                </div>

                <div className="flex flex-col gap-3">
                    <button
                        onClick={() => navigate('/app/lobby')}
                        className="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-3 rounded-xl transition-colors"
                    >
                        Rejouer
                    </button>
                    <button
                        onClick={() => navigate('/app/lobby')}
                        className="w-full border border-slate-700 text-slate-300 hover:border-slate-500 font-bold py-3 rounded-xl transition-colors"
                    >
                        Accueil
                    </button>
                </div>
            </div>
        </div>
    );
}
```

**Commande de vérification** :
```bash
cd /var/www && npm run build 2>&1 | grep -E "(error|Error|built)"
# Expected : ✓ built in Xs
```

**Commit** : `feat(web): add ResultsPage with ranked leaderboard`

---

## Tâche 14 — Ajouter VITE_API_HOST et VITE_REVERB_APP_KEY à .env

**Agent** : backend-agent

**Fichiers concernés** :
- `.env` (modifier — ajouter les variables)
- `.env.example` (modifier — documenter les variables)

**Code à ajouter dans `.env`** (à la suite des variables existantes) :
```
VITE_API_HOST=localhost
VITE_REVERB_APP_KEY=quiz-app-key
```

**Code à ajouter dans `.env.example`** :
```
VITE_API_HOST=localhost
VITE_REVERB_APP_KEY=quiz-app-key
```

**Commande de vérification** :
```bash
grep -E "VITE_API_HOST|VITE_REVERB_APP_KEY" .env
# Expected : les deux lignes affichées
```

**Commit** : `chore(web): expose VITE_API_HOST and VITE_REVERB_APP_KEY for React SPA`

---

## Tâche 15 — Vérification end-to-end et build final

**Agent** : testing-agent

**Fichiers concernés** : aucun (vérification uniquement)

**Commandes de vérification** :

```bash
# 1. Build propre
cd /var/www && npm run build
# Expected : ✓ built in Xs — fichiers dans public/build/

# 2. Vérification TypeScript
cd /var/www && npx tsc --noEmit
# Expected : aucune erreur

# 3. Vérification route Laravel
php artisan route:list | grep "app/"
# Expected : GET|HEAD  app/{any?}

# 4. Vérification que la vue existe
ls resources/views/spa.blade.php
# Expected : spa.blade.php

# 5. Vérification que les assets sont compilés
ls public/build/assets/ | grep -E "main"
# Expected : fichier main-*.js présent
```

**Commit** : `chore(web): verify full build and route setup`

---

## Résumé des fichiers créés / modifiés

| Fichier | Action |
|---|---|
| `package.json` | Modifier — ajouter React, react-router-dom, laravel-echo, pusher-js, @vitejs/plugin-react, typescript, @types/* |
| `vite.config.js` | Modifier — ajouter plugin React + entrée `resources/js/web/main.tsx` |
| `tsconfig.json` | Créer |
| `resources/views/spa.blade.php` | Créer — SPA shell HTML |
| `routes/web.php` | Modifier — catch-all `/app/{any?}` |
| `resources/js/web/main.tsx` | Créer — entry point React |
| `resources/js/web/App.tsx` | Créer — BrowserRouter + routes |
| `resources/js/web/lib/api.ts` | Créer — Axios client avec localStorage token |
| `resources/js/web/lib/echo.ts` | Créer — Laravel Echo client |
| `resources/js/web/store/authStore.tsx` | Créer — React Context auth |
| `resources/js/web/pages/LoginPage.tsx` | Créer |
| `resources/js/web/pages/LobbyPage.tsx` | Créer |
| `resources/js/web/pages/WaitingPage.tsx` | Créer |
| `resources/js/web/pages/QuizPage.tsx` | Créer |
| `resources/js/web/pages/ResultsPage.tsx` | Créer |
| `.env` + `.env.example` | Modifier — VITE_API_HOST, VITE_REVERB_APP_KEY |

## Option d'exécution

**Option A — Subagent-driven (recommandé)** : dispatcher un subagent `mobile-agent` par tâche avec double review.
Les tâches 1-3 peuvent s'exécuter en parallèle. Les tâches 4-15 sont séquentielles (chaque tâche dépend du build qui compile).

**Option B — Exécution inline** : exécuter tâche par tâche avec `npm run build` entre chaque pour valider.
