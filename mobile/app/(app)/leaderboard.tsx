import { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { apiClient } from '../../src/lib/api';
import { useAuthStore } from '../../src/store/authStore';
import { colors, spacing, radius } from '../../src/theme';

interface Row {
  rank: number;
  user_id: string;
  name: string;
  total_score: number;
  sessions_count: number;
}

const medals: Record<number, string> = { 1: '🥇', 2: '🥈', 3: '🥉' };

function LeaderboardRow({
  item,
  isCurrentUser,
}: {
  item: Row;
  isCurrentUser: boolean;
}) {
  return (
    <View
      style={[
        styles.row,
        item.rank <= 3 && styles.topRow,
        isCurrentUser && styles.myRow,
      ]}
    >
      <Text style={styles.rank}>{medals[item.rank] ?? `#${item.rank}`}</Text>
      <View style={styles.info}>
        <Text style={styles.name}>
          {item.name}
          {isCurrentUser ? ' (moi)' : ''}
        </Text>
        <Text style={styles.sessions}>
          {item.sessions_count} partie{item.sessions_count > 1 ? 's' : ''}
        </Text>
      </View>
      <Text style={styles.score}>{item.total_score} pts</Text>
    </View>
  );
}

const MemoLeaderboardRow = LeaderboardRow;

export default function LeaderboardScreen() {
  const { user } = useAuthStore();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    try {
      const { data } = await apiClient.get('/v1/leaderboard');
      setRows(data.data);
    } catch {
      // L'erreur est déjà normalisée par l'intercepteur apiClient
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const handleRefresh = useCallback(() => {
    load(true);
  }, [load]);

  const keyExtractor = useCallback((item: Row) => item.user_id, []);

  const renderItem = useCallback(
    ({ item }: { item: Row }) => (
      <MemoLeaderboardRow
        item={item}
        isCurrentUser={item.user_id === user?.id}
      />
    ),
    [user?.id],
  );

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.primary} size="large" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Classement</Text>

      <FlatList
        data={rows}
        keyExtractor={keyExtractor}
        renderItem={renderItem}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={styles.emptyText}>
              Aucune partie terminée pour l&apos;instant.
            </Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
    padding: spacing.lg,
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
  },
  title: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.text,
    marginBottom: spacing.lg,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
    borderWidth: 1,
    borderColor: colors.border,
  },
  topRow: {
    borderColor: colors.primary,
  },
  myRow: {
    borderColor: '#fbbf24',
    borderWidth: 2,
  },
  rank: {
    fontSize: 22,
    marginRight: spacing.md,
    minWidth: 44,
    textAlign: 'center',
  },
  info: {
    flex: 1,
  },
  name: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '600',
  },
  sessions: {
    color: colors.textMuted,
    fontSize: 12,
    marginTop: 2,
  },
  score: {
    color: colors.primary,
    fontWeight: '700',
    fontSize: 16,
  },
  empty: {
    padding: spacing.xl,
    alignItems: 'center',
  },
  emptyText: {
    color: colors.textMuted,
    fontSize: 15,
    textAlign: 'center',
  },
});
