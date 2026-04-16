import { useEffect, useState, useRef } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { useAuthStore } from '../../../src/store/authStore';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

interface Entry { user_id: string; name: string; score: number; }

const POLL_INTERVAL = 2000;

export default function MultiResultsScreen() {
  const router             = useRouter();
  const { lobbyId }        = useLocalSearchParams<{ lobbyId: string }>();
  const { user }           = useAuthStore();

  const [leaderboard, setLeaderboard] = useState<Entry[]>([]);
  const [status, setStatus]           = useState<string>('in_progress');
  const [loading, setLoading]         = useState(true);

  const pollRef   = useRef<ReturnType<typeof setInterval> | null>(null);
  const mountedRef = useRef(true);

  function stopPolling() {
    if (pollRef.current) {
      clearInterval(pollRef.current);
      pollRef.current = null;
    }
  }

  async function fetchResults() {
    try {
      const { data } = await apiClient.get(`/v1/lobbies/${lobbyId}`);
      if (!mountedRef.current) return;

      const lobby = data.data;
      setStatus(lobby.status);

      if (lobby.leaderboard && lobby.leaderboard.length > 0) {
        setLeaderboard(lobby.leaderboard);
      }

      // Arrêter le polling une fois terminé
      if (lobby.status === 'completed') {
        stopPolling();
      }
    } catch {
      // Ignorer silencieusement
    } finally {
      if (mountedRef.current) setLoading(false);
    }
  }

  useEffect(() => {
    mountedRef.current = true;

    fetchResults();
    // Poller jusqu'à ce que le lobby soit completed
    pollRef.current = setInterval(fetchResults, POLL_INTERVAL);

    return () => {
      mountedRef.current = false;
      stopPolling();
    };
  }, [lobbyId]);

  const medals: Record<number, string> = { 0: '🥇', 1: '🥈', 2: '🥉' };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.primary} size="large" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Résultats finaux</Text>

      {status !== 'completed' && (
        <View style={styles.waitingBanner}>
          <ActivityIndicator color={colors.primary} size="small" style={{ marginRight: spacing.sm }} />
          <Text style={styles.waitingText}>En attente de la fin de partie…</Text>
        </View>
      )}

      {leaderboard.length === 0 ? (
        <View style={styles.center}>
          <Text style={styles.emptyText}>Calcul des scores en cours…</Text>
        </View>
      ) : (
        <FlatList
          data={leaderboard}
          keyExtractor={(item) => item.user_id}
          style={styles.list}
          renderItem={({ item, index }) => (
            <View style={[
              styles.row,
              item.user_id === user?.id && styles.myRow,
              index === 0 && styles.firstRow,
            ]}>
              <Text style={styles.rank}>{medals[index] ?? `#${index + 1}`}</Text>
              <Text style={styles.name}>{item.name}</Text>
              <Text style={styles.score}>{item.score} pts</Text>
            </View>
          )}
        />
      )}

      {status === 'completed' && (
        <View style={styles.actions}>
          <Button label="Rejouer" onPress={() => router.push('/multi/lobby')} />
          <Button label="Accueil" onPress={() => router.replace('/(app)')} variant="outline" />
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container:     { flex: 1, backgroundColor: colors.background, padding: spacing.xl },
  center:        { flex: 1, justifyContent: 'center', alignItems: 'center' },
  title:         { fontSize: 26, fontWeight: '800', color: colors.text, marginBottom: spacing.lg },
  waitingBanner: { flexDirection: 'row', alignItems: 'center', backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.md, marginBottom: spacing.lg, borderWidth: 1, borderColor: colors.border },
  waitingText:   { color: colors.textMuted, fontSize: 14 },
  emptyText:     { color: colors.textMuted, fontSize: 15 },
  list:          { flex: 1 },
  row:           { flexDirection: 'row', alignItems: 'center', backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.lg, marginBottom: spacing.sm, borderWidth: 1, borderColor: colors.border },
  firstRow:      { borderColor: '#fbbf24', borderWidth: 2 },
  myRow:         { borderColor: colors.primary },
  rank:          { fontSize: 24, marginRight: spacing.md, minWidth: 40 },
  name:          { flex: 1, color: colors.text, fontSize: 16, fontWeight: '600' },
  score:         { color: colors.primary, fontWeight: '700', fontSize: 16 },
  actions:       { gap: spacing.sm, marginTop: spacing.lg },
});
