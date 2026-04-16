import { useEffect, useState, useRef } from 'react';
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
}

export default function WaitingRoomScreen() {
  const router = useRouter();
  const { lobbyId, isHost } = useLocalSearchParams<{ lobbyId: string; isHost: string }>();
  const { user } = useAuthStore();
  const isHostBool = isHost === '1';

  const [lobby, setLobby]       = useState<LobbyData | null>(null);
  const [loading, setLoading]   = useState(true);
  const [starting, setStarting] = useState(false);
  const [error, setError]       = useState('');
  const channelRef              = useRef<any>(null);

  useEffect(() => {
    let mounted = true;

    // Charger le lobby
    apiClient.get(`/v1/lobbies/${lobbyId}`)
      .then(({ data }) => { if (mounted) setLobby(data.data); })
      .catch((e: any) => { if (mounted) setError(e.message); })
      .finally(() => { if (mounted) setLoading(false); });

    // Écouter le WebSocket
    (async () => {
      try {
        const echo    = await getEcho();
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
              router.replace({
                pathname: '/multi/quiz',
                params: { sessionId: mySessionId, lobbyId, isHost },
              });
            }
          });

        channelRef.current = channel;
      } catch {
        // WebSocket non disponible — continuer sans
      }
    })();

    return () => {
      mounted = false;
      channelRef.current?.stopListening('.player.joined');
      channelRef.current?.stopListening('.player.left');
      channelRef.current?.stopListening('.lobby.started');
    };
  }, [lobbyId]);

  async function startGame() {
    setError('');
    setStarting(true);
    try {
      await apiClient.post(`/v1/lobbies/${lobbyId}/start`);
      // La navigation sera déclenchée par l'event .lobby.started via WebSocket
    } catch (e: any) {
      setError(e.message);
      setStarting(false);
    }
  }

  if (loading) {
    return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Salle d'attente</Text>

      <View style={styles.codeBox}>
        <Text style={styles.codeLabel}>Code d'invitation</Text>
        <Text style={styles.code}>{lobby?.code}</Text>
      </View>

      <Text style={styles.sectionLabel}>
        Joueurs ({lobby?.participants.length ?? 0}/{lobby?.max_players})
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
          {(lobby?.participants.length ?? 0) < 2 && (
            <Text style={styles.waitingHint}>
              En attente d'au moins 1 autre joueur…
            </Text>
          )}
          <Button
            label={starting ? 'Démarrage…' : `Démarrer (${lobby?.participants.length ?? 0}/${lobby?.max_players ?? 4} joueurs)`}
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
  title:         { fontSize: 24, fontWeight: '800', color: colors.text, marginBottom: spacing.lg },
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
