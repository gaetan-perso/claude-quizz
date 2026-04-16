import { useEffect, useState, useRef, useCallback } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { getEcho } from '../../../src/lib/echo';
import { useAuthStore } from '../../../src/store/authStore';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

interface Participant { user_id: string; name: string; score: number; }
interface LobbyData {
  id: string; code: string; status: string;
  host_user_id: string; max_players: number;
  participants: Participant[];
  category?: { id: string; name: string };
  category_ids?: string[];
}

const POLL_INTERVAL = 3000; // ms — fallback si WebSocket indisponible

export default function WaitingRoomScreen() {
  const router = useRouter();
  const { lobbyId, isHost } = useLocalSearchParams<{ lobbyId: string; isHost: string }>();
  const { user } = useAuthStore();
  const isHostBool = isHost === '1';

  const [lobby, setLobby]       = useState<LobbyData | null>(null);
  const [loading, setLoading]   = useState(true);
  const [starting, setStarting] = useState(false);
  const [error, setError]       = useState('');
  const [wsConnected, setWsConnected] = useState(false);

  const mountedRef   = useRef(true);
  const channelRef   = useRef<any>(null);
  const pollRef      = useRef<ReturnType<typeof setInterval> | null>(null);
  const navigatedRef = useRef(false);

  // ── Fetch lobby depuis l'API ────────────────────────────────────────────────
  const fetchLobby = useCallback(async () => {
    try {
      const { data } = await apiClient.get(`/v1/lobbies/${lobbyId}`);
      if (!mountedRef.current) return;
      const lobbyData: LobbyData & { session_map?: Record<string, string> } = data.data;
      setLobby(lobbyData);

      // Si le lobby vient de démarrer et qu'on a la session_map → naviguer
      if (lobbyData.status === 'in_progress' && lobbyData.session_map) {
        navigateToQuiz(lobbyData.session_map);
      }
    } catch {
      // Ignorer silencieusement
    }
  }, [lobbyId, navigateToQuiz]);

  // ── Démarrage du polling de secours ────────────────────────────────────────
  const startPolling = useCallback(() => {
    if (pollRef.current) return;
    pollRef.current = setInterval(() => {
      if (mountedRef.current) fetchLobby();
    }, POLL_INTERVAL);
  }, [fetchLobby]);

  const stopPolling = useCallback(() => {
    if (pollRef.current) {
      clearInterval(pollRef.current);
      pollRef.current = null;
    }
  }, []);

  // ── Navigation sécurisée (anti double-navigation) ──────────────────────────
  const navigateToQuiz = useCallback((sessionMap: Record<string, string>) => {
    if (navigatedRef.current || !mountedRef.current) return;
    const mySessionId = sessionMap[user!.id];
    if (!mySessionId) return;
    navigatedRef.current = true;
    stopPolling();
    router.replace({
      pathname: '/multi/quiz',
      params: { sessionId: mySessionId, lobbyId, isHost },
    });
  }, [user, lobbyId, isHost, router, stopPolling]);

  // ── Setup WebSocket + chargement initial ───────────────────────────────────
  useEffect(() => {
    mountedRef.current = true;

    // Chargement initial
    apiClient.get(`/v1/lobbies/${lobbyId}`)
      .then(({ data }) => { if (mountedRef.current) setLobby(data.data); })
      .catch((e: any) => { if (mountedRef.current) setError(e.message); })
      .finally(() => { if (mountedRef.current) setLoading(false); });

    // WebSocket
    (async () => {
      try {
        const echo    = await getEcho();
        const channel = echo.join(`lobby.${lobbyId}`);

        channel
          .here(() => {
            if (mountedRef.current) setWsConnected(true);
          })
          .listen('.player.joined', (e: { participants: Participant[] }) => {
            if (mountedRef.current) setLobby((prev) => prev ? { ...prev, participants: e.participants } : prev);
          })
          .listen('.player.left', (e: { participants: Participant[] }) => {
            if (mountedRef.current) setLobby((prev) => prev ? { ...prev, participants: e.participants } : prev);
          })
          .listen('.lobby.started', (e: { session_map: Record<string, string> }) => {
            navigateToQuiz(e.session_map);
          });

        channelRef.current = channel;
      } catch {
        // WebSocket indisponible — le polling prend le relai
      }
    })();

    // Démarrer le polling de secours dans tous les cas
    startPolling();

    return () => {
      mountedRef.current = false;
      stopPolling();
      channelRef.current?.stopListening('.player.joined');
      channelRef.current?.stopListening('.player.left');
      channelRef.current?.stopListening('.lobby.started');
    };
  }, [lobbyId]);

  // ── Démarrer la partie ─────────────────────────────────────────────────────
  async function startGame() {
    setError('');
    setStarting(true);
    try {
      const { data } = await apiClient.post(`/v1/lobbies/${lobbyId}/start`);
      // Naviguer directement depuis la réponse API (sans attendre le WebSocket)
      const sessionMap: Record<string, string> = data.data.session_map ?? {};
      navigateToQuiz(sessionMap);
    } catch (e: any) {
      setError(e.message);
      setStarting(false);
    }
  }

  if (loading) {
    return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;
  }

  const participantCount = lobby?.participants.length ?? 0;

  return (
    <View style={styles.container}>
      <View style={styles.titleRow}>
        <Text style={styles.title}>Salle d'attente</Text>
        <View style={[styles.wsBadge, wsConnected && styles.wsBadgeOk]}>
          <Text style={styles.wsBadgeText}>{wsConnected ? '● Live' : '○ Sync'}</Text>
        </View>
      </View>

      <View style={styles.codeBox}>
        <Text style={styles.codeLabel}>Code d'invitation</Text>
        <Text style={styles.code}>{lobby?.code}</Text>
      </View>

      <Text style={styles.sectionLabel}>
        Joueurs ({participantCount}/{lobby?.max_players})
      </Text>

      <FlatList
        data={lobby?.participants ?? []}
        keyExtractor={(item) => item.user_id}
        style={styles.list}
        renderItem={({ item }) => (
          <View style={styles.playerRow}>
            <Text style={styles.playerName}>{item.name}</Text>
            {item.user_id === lobby?.host_user_id && (
              <View style={styles.hostBadge}>
                <Text style={styles.hostBadgeText}>Hôte</Text>
              </View>
            )}
            {item.user_id === user?.id && (
              <View style={styles.meBadge}>
                <Text style={styles.meBadgeText}>Moi</Text>
              </View>
            )}
          </View>
        )}
      />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      {isHostBool ? (
        <View>
          {participantCount < 2 && (
            <Text style={styles.waitingHint}>
              En attente d'au moins 1 autre joueur…
            </Text>
          )}
          <Button
            label={starting ? 'Démarrage…' : `Démarrer (${participantCount}/${lobby?.max_players ?? 4} joueurs)`}
            onPress={startGame}
            loading={starting}
            disabled={starting}
          />
        </View>
      ) : (
        <Text style={styles.waitingText}>
          En attente du démarrage par l'hôte…
        </Text>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container:     { flex: 1, backgroundColor: colors.background, padding: spacing.lg },
  center:        { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background },
  titleRow:      { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: spacing.lg },
  title:         { fontSize: 24, fontWeight: '800', color: colors.text },
  wsBadge:       { paddingHorizontal: spacing.sm, paddingVertical: 2, borderRadius: radius.full, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border },
  wsBadgeOk:     { borderColor: colors.success },
  wsBadgeText:   { fontSize: 11, color: colors.textMuted, fontWeight: '600' },
  codeBox:       { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.lg, alignItems: 'center', marginBottom: spacing.lg, borderWidth: 2, borderColor: colors.primary },
  codeLabel:     { color: colors.textMuted, fontSize: 13, marginBottom: spacing.xs, textTransform: 'uppercase', letterSpacing: 1 },
  code:          { fontSize: 38, fontWeight: '900', color: colors.primary, letterSpacing: 10 },
  sectionLabel:  { color: colors.textMuted, fontSize: 13, textTransform: 'uppercase', letterSpacing: 1, marginBottom: spacing.sm },
  list:          { flex: 1, marginBottom: spacing.lg },
  playerRow:     { flexDirection: 'row', alignItems: 'center', backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.md, marginBottom: spacing.xs, gap: spacing.sm },
  playerName:    { flex: 1, color: colors.text, fontSize: 16, fontWeight: '500' },
  hostBadge:     { backgroundColor: colors.primary, paddingHorizontal: spacing.sm, paddingVertical: 2, borderRadius: radius.full },
  hostBadgeText: { color: '#fff', fontSize: 11, fontWeight: '700' },
  meBadge:       { backgroundColor: colors.surface, paddingHorizontal: spacing.sm, paddingVertical: 2, borderRadius: radius.full, borderWidth: 1, borderColor: colors.border },
  meBadgeText:   { color: colors.textMuted, fontSize: 11 },
  error:         { color: colors.error, textAlign: 'center', marginBottom: spacing.md },
  waitingText:   { color: colors.textMuted, textAlign: 'center', fontSize: 14, paddingVertical: spacing.md },
  waitingHint:   { color: colors.textMuted, textAlign: 'center', fontSize: 13, marginBottom: spacing.sm },
});
