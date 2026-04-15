import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../../src/store/authStore';
import { colors, spacing, radius } from '../../src/theme';

const MENU = [
  { label: 'Jouer en solo',  icon: '🎯', route: '/solo/categories' as const },
  { label: 'Multijoueur',    icon: '👥', route: '/multi/lobby'     as const },
  { label: 'Classement',     icon: '🏆', route: '/leaderboard'     as const },
];

export default function HomeScreen() {
  const router = useRouter();
  const { user, logout } = useAuthStore();

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.greeting}>Bonjour, {user?.name} 👋</Text>
        <Text style={styles.title}>Quiz Claude</Text>
      </View>

      <View style={styles.menu}>
        {MENU.map((item) => (
          <TouchableOpacity
            key={item.route}
            style={styles.card}
            onPress={() => router.push(item.route)}
            activeOpacity={0.8}
          >
            <Text style={styles.icon}>{item.icon}</Text>
            <Text style={styles.cardLabel}>{item.label}</Text>
            <Text style={styles.arrow}>›</Text>
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
  container:  { flex: 1, backgroundColor: colors.background, padding: spacing.xl, paddingTop: 60 },
  header:     { marginBottom: spacing.xl * 1.5 },
  greeting:   { color: colors.textMuted, fontSize: 16, marginBottom: spacing.xs },
  title:      { fontSize: 34, fontWeight: '800', color: colors.primary },
  menu:       { gap: spacing.md },
  card:       { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.lg, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderWidth: 1, borderColor: colors.border },
  icon:       { fontSize: 28 },
  cardLabel:  { flex: 1, fontSize: 18, fontWeight: '600', color: colors.text },
  arrow:      { fontSize: 24, color: colors.textMuted },
  logoutBtn:  { marginTop: 'auto', paddingVertical: spacing.md, alignItems: 'center' },
  logoutText: { color: colors.textMuted, fontSize: 15 },
});
