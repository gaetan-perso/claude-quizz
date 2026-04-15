import { useEffect, useState } from 'react';
import {
  View, Text, FlatList, TouchableOpacity,
  StyleSheet, ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { colors, spacing, radius } from '../../../src/theme';

interface Category { id: string; name: string; icon: string | null; color: string | null; }

export default function CategoriesScreen() {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading]       = useState(true);
  const [starting, setStarting]     = useState<string | null>(null);
  const [error, setError]           = useState('');

  useEffect(() => {
    apiClient.get('/v1/categories')
      .then(({ data }) => setCategories(data.data))
      .catch((e: any) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  async function startSession(categoryId: string) {
    setStarting(categoryId);
    setError('');
    try {
      const { data } = await apiClient.post('/v1/sessions', { category_id: categoryId });
      router.push({ pathname: '/solo/quiz', params: { sessionId: data.data.id } });
    } catch (e: any) {
      setError(e.message);
    } finally {
      setStarting(null);
    }
  }

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.primary} size="large" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Choisir une catégorie</Text>
      {error ? <Text style={styles.error}>{error}</Text> : null}
      <FlatList
        data={categories}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[styles.card, { borderLeftColor: item.color ?? colors.primary }]}
            onPress={() => startSession(item.id)}
            disabled={starting !== null}
            activeOpacity={0.8}
          >
            <Text style={styles.icon}>{item.icon ?? '📚'}</Text>
            <Text style={styles.name}>{item.name}</Text>
            {starting === item.id
              ? <ActivityIndicator color={colors.primary} size="small" />
              : <Text style={styles.arrow}>›</Text>
            }
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
  card:      { backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.lg, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderLeftWidth: 4, borderLeftColor: colors.primary },
  icon:      { fontSize: 24 },
  name:      { flex: 1, fontSize: 17, fontWeight: '600', color: colors.text },
  arrow:     { fontSize: 22, color: colors.textMuted },
  error:     { color: colors.error, textAlign: 'center', marginBottom: spacing.md },
});
