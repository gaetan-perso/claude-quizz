import { useState } from 'react';
import {
  Text,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from 'react-native';
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
    if (!name.trim()) { setError('Le nom est requis.'); return; }
    if (password.length < 8) { setError('Le mot de passe doit faire au moins 8 caractères.'); return; }
    setLoading(true);
    try {
      await register(name.trim(), email.trim().toLowerCase(), password);
    } catch (e: any) {
      setError(e.message ?? "Erreur lors de l'inscription");
    } finally {
      setLoading(false);
    }
  }

  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        contentContainerStyle={styles.container}
        keyboardShouldPersistTaps="handled"
      >
        <Text style={styles.title}>Quiz Claude</Text>
        <Text style={styles.subtitle}>Créer un compte</Text>

        {error ? <Text style={styles.errorBox}>{error}</Text> : null}

        <Input
          label="Nom d'affichage"
          value={name}
          onChangeText={setName}
          autoCapitalize="words"
          placeholder="Jean Dupont"
        />
        <Input
          label="Email"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          autoCorrect={false}
          placeholder="votre@email.com"
        />
        <Input
          label="Mot de passe (8 car. min.)"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          placeholder="••••••••"
        />

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
  errorBox:  { backgroundColor: '#450a0a', borderColor: colors.error, borderWidth: 1, borderRadius: 8, padding: spacing.md, color: colors.error, marginBottom: spacing.md, textAlign: 'center' },
  link:      { color: colors.primary, textAlign: 'center', marginTop: spacing.lg, fontSize: 15 },
});
