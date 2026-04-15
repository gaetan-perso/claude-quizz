import { View, Text, StyleSheet } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

export default function SoloResultsScreen() {
  const router = useRouter();
  const { score } = useLocalSearchParams<{ score: string }>();
  const finalScore = parseInt(score ?? '0', 10);

  const medal =
    finalScore >= 8 ? '🥇'
    : finalScore >= 5 ? '🥈'
    : '🥉';

  return (
    <View style={styles.container}>
      <Text style={styles.medal}>{medal}</Text>
      <Text style={styles.title}>Partie terminée !</Text>

      <View style={styles.scoreBox}>
        <Text style={styles.scoreLabel}>Score final</Text>
        <Text style={styles.scoreValue}>{finalScore}</Text>
        <Text style={styles.scoreUnit}>points</Text>
      </View>

      <View style={styles.actions}>
        <Button
          label="Rejouer"
          onPress={() => router.replace('/solo/categories')}
        />
        <Button
          label="Accueil"
          onPress={() => router.replace('/(app)')}
          variant="outline"
        />
        <Button
          label="Classement"
          onPress={() => router.push('/leaderboard')}
          variant="ghost"
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container:  { flex: 1, backgroundColor: colors.background, padding: spacing.xl, justifyContent: 'center', alignItems: 'center' },
  medal:      { fontSize: 80, marginBottom: spacing.md },
  title:      { fontSize: 26, fontWeight: '800', color: colors.text, marginBottom: spacing.xl },
  scoreBox:   { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.xl, alignItems: 'center', marginBottom: spacing.xl, width: '100%', borderWidth: 1, borderColor: colors.border },
  scoreLabel: { color: colors.textMuted, fontSize: 16, marginBottom: spacing.xs },
  scoreValue: { fontSize: 72, fontWeight: '900', color: colors.primary, lineHeight: 80 },
  scoreUnit:  { color: colors.textMuted, fontSize: 14, marginTop: spacing.xs },
  actions:    { width: '100%', gap: spacing.sm },
});
