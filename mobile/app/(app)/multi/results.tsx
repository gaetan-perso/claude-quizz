import { useEffect, useState } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { useAuthStore } from '../../../src/store/authStore';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

interface Entry { user_id: string; name: string; score: number; }

export default function MultiResultsScreen() {
  const router  = useRouter();
  const { lobbyId } = useLocalSearchParams<{ lobbyId: string }>();
  const { user } = useAuthStore();
  const [leaderboard, setLeaderboard] = useState<Entry[]>([]);
  const [loading, setLoading]         = useState(true);

  useEffect(() => {
    apiClient.get(`/v1/lobbies/${lobbyId}`)
      .then(({ data }) => {
        const sorted = [...data.data.participants]
          .sort((a: any, b: any) => b.score - a.score);
        setLeaderboard(sorted.map((p: any) => ({ user_id: p.user_id, name: p.name, score: p.score })));
      })
      .finally(() => setLoading(false));
  }, [lobbyId]);

  const medals: Record<number, string> = { 0: '🥇', 1: '🥈', 2: '🥉' };

  if (loading) {
    return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Résultats finaux</Text>

      <FlatList
        data={leaderboard}
        keyExtractor={(item) => item.user_id}
        style={styles.list}
        renderItem={({ item, index }) => (
          <View style={[styles.row, item.user_id === user?.id && styles.myRow, index === 0 && styles.firstRow]}>
            <Text style={styles.rank}>{medals[index] ?? `#${index + 1}`}</Text>
            <Text style={styles.name}>{item.name}</Text>
            <Text style={styles.score}>{item.score} pts</Text>
          </View>
        )}
      />

      <View style={styles.actions}>
        <Button label="Rejouer" onPress={() => router.push('/multi/lobby')} />
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
  firstRow:  { borderColor: '#fbbf24', borderWidth: 2 },
  myRow:     { borderColor: colors.primary },
  rank:      { fontSize: 24, marginRight: spacing.md, minWidth: 40 },
  name:      { flex: 1, color: colors.text, fontSize: 16, fontWeight: '600' },
  score:     { color: colors.primary, fontWeight: '700', fontSize: 16 },
  actions:   { gap: spacing.sm, marginTop: spacing.lg },
});
