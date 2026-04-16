import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { Input } from '../../../src/components/Input';
import { Button } from '../../../src/components/Button';
import { colors, spacing, radius } from '../../../src/theme';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Category {
  id: string;
  name: string;
  icon: string | null;
  color: string | null;
  question_count: number;
}

const QUESTION_COUNTS = [10, 20, 30] as const;
type QuestionCount = (typeof QUESTION_COUNTS)[number];

const PLAYER_COUNTS = [2, 4, 6, 8, 10] as const;
type PlayerCount = (typeof PLAYER_COUNTS)[number];

// ─── CategoryCard ─────────────────────────────────────────────────────────────

interface CategoryCardProps {
  item: Category;
  selected: boolean;
  onPress: (id: string) => void;
}

const CategoryCard = React.memo(function CategoryCard({
  item,
  selected,
  onPress,
}: CategoryCardProps) {
  const handlePress = useCallback(() => onPress(item.id), [item.id, onPress]);

  const cardStyle = useMemo(
    () => [
      styles.card,
      { borderLeftColor: item.color ?? colors.primary },
      selected && styles.cardSelected,
    ],
    [item.color, selected],
  );

  return (
    <TouchableOpacity
      style={cardStyle}
      onPress={handlePress}
      activeOpacity={0.75}
      accessibilityRole="checkbox"
      accessibilityState={{ checked: selected }}
      accessibilityLabel={`${item.name}, ${item.question_count} questions disponibles`}
    >
      <Text style={styles.cardIcon}>{item.icon ?? '📚'}</Text>

      <View style={styles.cardText}>
        <Text style={styles.cardName}>{item.name}</Text>
        <Text style={styles.cardAvailable}>
          {item.question_count} question{item.question_count !== 1 ? 's' : ''} disponible
          {item.question_count !== 1 ? 's' : ''}
        </Text>
      </View>

      {selected ? (
        <View style={styles.checkmark}>
          <Text style={styles.checkmarkText}>✓</Text>
        </View>
      ) : (
        <View style={styles.checkmarkPlaceholder} />
      )}
    </TouchableOpacity>
  );
});

// ─── LobbyScreen ──────────────────────────────────────────────────────────────

export default function LobbyScreen() {
  const router = useRouter();

  // État partagé
  const [error, setError] = useState('');

  // État "Créer une partie"
  const [categories, setCategories]   = useState<Category[]>([]);
  const [catsLoading, setCatsLoading] = useState(true);
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [maxQuestions, setMaxQ]       = useState<QuestionCount>(10);
  const [maxPlayers, setMaxPlayers]   = useState<PlayerCount>(4);
  const [createLoading, setCreateL]   = useState(false);

  // État "Rejoindre"
  const [code, setCode]           = useState('');
  const [joinLoading, setJoinL]   = useState(false);

  // ── Chargement des catégories ──────────────────────────────────────────────

  useEffect(() => {
    apiClient
      .get('/v1/categories')
      .then(({ data }) => setCategories(data.data as Category[]))
      .catch((e: unknown) => {
        const msg = e instanceof Error ? e.message : 'Erreur de chargement des catégories';
        setError(msg);
      })
      .finally(() => setCatsLoading(false));
  }, []);

  // ── Gestion de la sélection ───────────────────────────────────────────────

  const toggleCategory = useCallback((id: string) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  }, []);

  const allSelected = categories.length > 0 && selectedIds.size === categories.length;

  const toggleSelectAll = useCallback(() => {
    if (allSelected) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(categories.map((c) => c.id)));
    }
  }, [allSelected, categories]);

  // ── Calculs dérivés ───────────────────────────────────────────────────────

  const { totalAvailable, effective, isLimited, selectionSummary } = useMemo(() => {
    const selected = categories.filter((c) => selectedIds.has(c.id));
    const total = selected.reduce((acc, c) => acc + c.question_count, 0);
    const eff = Math.min(maxQuestions, total);
    const limited = eff < maxQuestions && total > 0;
    const count = selected.length;
    const summary =
      count === 0
        ? 'Aucune catégorie sélectionnée'
        : `${count} catégorie${count > 1 ? 's' : ''} · ${total} question${total !== 1 ? 's' : ''} disponible${total !== 1 ? 's' : ''}`;

    return { totalAvailable: total, effective: eff, isLimited: limited, selectionSummary: summary };
  }, [categories, selectedIds, maxQuestions]);

  const hasSelection = selectedIds.size > 0;

  // ── Actions ───────────────────────────────────────────────────────────────

  async function createLobby() {
    if (!hasSelection || createLoading) return;
    setError('');
    setCreateL(true);
    try {
      const { data } = await apiClient.post('/v1/lobbies', {
        category_ids:  Array.from(selectedIds),
        max_questions: effective,
        max_players:   maxPlayers,
      });
      router.push({
        pathname: '/multi/waiting',
        params: { lobbyId: data.data.id, isHost: '1' },
      });
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : 'Impossible de créer la partie';
      setError(msg);
    } finally {
      setCreateL(false);
    }
  }

  async function joinLobby() {
    setError('');
    if (code.trim().length !== 6) { setError('Le code doit faire 6 caractères.'); return; }
    setJoinL(true);
    try {
      const { data } = await apiClient.post('/v1/lobbies/join', { code: code.trim().toUpperCase() });
      router.push({ pathname: '/multi/waiting', params: { lobbyId: data.data.id, isHost: '0' } });
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : 'Code invalide ou partie introuvable';
      setError(msg);
    } finally {
      setJoinL(false);
    }
  }

  // ── Rendu ─────────────────────────────────────────────────────────────────

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Text style={styles.title}>Multijoueur</Text>

      {error ? <Text style={styles.errorBox}>{error}</Text> : null}

      {/* ── Bloc "Créer une partie" ── */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Créer une partie</Text>
        <Text style={styles.sectionDesc}>
          Tu seras l'hôte. Un code sera généré pour inviter des amis.
        </Text>

        {/* Sélecteur nombre de questions */}
        <View style={styles.countSection}>
          <Text style={styles.countLabel}>Nombre de questions</Text>
          <View style={styles.countRow}>
            {QUESTION_COUNTS.map((count) => (
              <TouchableOpacity
                key={count}
                style={[styles.countBtn, maxQuestions === count && styles.countBtnActive]}
                onPress={() => setMaxQ(count)}
                activeOpacity={0.8}
                accessibilityRole="radio"
                accessibilityState={{ selected: maxQuestions === count }}
                accessibilityLabel={`${count} questions`}
              >
                <Text style={[styles.countBtnText, maxQuestions === count && styles.countBtnTextActive]}>
                  {count}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* Sélecteur nombre de joueurs */}
        <View style={styles.countSection}>
          <Text style={styles.countLabel}>Nombre de joueurs max</Text>
          <View style={styles.countRow}>
            {PLAYER_COUNTS.map((count) => (
              <TouchableOpacity
                key={count}
                style={[styles.countBtn, maxPlayers === count && styles.countBtnActive]}
                onPress={() => setMaxPlayers(count)}
                activeOpacity={0.8}
                accessibilityRole="radio"
                accessibilityState={{ selected: maxPlayers === count }}
                accessibilityLabel={`${count} joueurs`}
              >
                <Text style={[styles.countBtnText, maxPlayers === count && styles.countBtnTextActive]}>
                  {count}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* Résumé de sélection */}
        <View style={styles.summaryRow}>
          <Text style={styles.summaryText}>{selectionSummary}</Text>
          {isLimited && (
            <Text style={styles.limitedText}> · sera limité à {effective}</Text>
          )}
        </View>

        {/* Liste des catégories */}
        {catsLoading ? (
          <View style={styles.loadingRow}>
            <ActivityIndicator color={colors.primary} size="small" />
            <Text style={styles.loadingText}>Chargement des catégories…</Text>
          </View>
        ) : (
          <>
            {/* Tout sélectionner */}
            {categories.length > 0 && (
              <TouchableOpacity
                style={styles.selectAllRow}
                onPress={toggleSelectAll}
                activeOpacity={0.7}
                accessibilityRole="checkbox"
                accessibilityState={{ checked: allSelected }}
                accessibilityLabel="Sélectionner tous les thèmes"
              >
                <View style={[styles.selectAllBox, allSelected && styles.selectAllBoxChecked]}>
                  {allSelected && <Text style={styles.selectAllCheck}>✓</Text>}
                </View>
                <Text style={styles.selectAllLabel}>Tous les thèmes</Text>
              </TouchableOpacity>
            )}

            <View style={styles.categoryList}>
              {categories.map((item) => (
                <CategoryCard
                  key={item.id}
                  item={item}
                  selected={selectedIds.has(item.id)}
                  onPress={toggleCategory}
                />
              ))}
            </View>
          </>
        )}

        {/* Bouton créer */}
        <TouchableOpacity
          style={[styles.createBtn, (!hasSelection || createLoading) && styles.createBtnDisabled]}
          onPress={createLobby}
          disabled={!hasSelection || createLoading}
          activeOpacity={0.85}
          accessibilityRole="button"
          accessibilityLabel="Créer la partie"
        >
          {createLoading ? (
            <ActivityIndicator color="#fff" size="small" />
          ) : (
            <>
              <Text style={styles.createBtnText}>Créer la partie</Text>
              {hasSelection && (
                <Text style={styles.createBtnSub}>
                  {effective} question{effective !== 1 ? 's' : ''}
                  {isLimited ? ' · limité au disponible' : ''}
                </Text>
              )}
            </>
          )}
        </TouchableOpacity>
      </View>

      {/* ── Séparateur ── */}
      <View style={styles.dividerRow}>
        <View style={styles.dividerLine} />
        <Text style={styles.dividerText}>ou</Text>
        <View style={styles.dividerLine} />
      </View>

      {/* ── Bloc "Rejoindre" (inchangé) ── */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Rejoindre une partie</Text>
        <Input
          label="Code d'invitation (6 lettres)"
          value={code}
          onChangeText={(t) => setCode(t.toUpperCase())}
          autoCapitalize="characters"
          maxLength={6}
          placeholder="ABC123"
        />
        <Button label="Rejoindre" onPress={joinLobby} loading={joinLoading} variant="outline" />
      </View>
    </ScrollView>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  content:   { padding: spacing.xl, paddingBottom: spacing.xl * 2 },

  title: { fontSize: 26, fontWeight: '800', color: colors.text, marginBottom: spacing.xl },

  errorBox: {
    backgroundColor: '#450a0a',
    borderColor: colors.error,
    borderWidth: 1,
    borderRadius: radius.md,
    padding: spacing.md,
    color: colors.error,
    marginBottom: spacing.md,
    textAlign: 'center',
  },

  // Sections
  section: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
    gap: spacing.md,
  },
  sectionTitle: { fontSize: 18, fontWeight: '700', color: colors.text },
  sectionDesc:  { color: colors.textMuted, fontSize: 14, lineHeight: 20 },

  // Sélecteur nombre de questions
  countSection: { gap: spacing.sm },
  countLabel: {
    color: colors.textMuted,
    fontSize: 12,
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  countRow: { flexDirection: 'row', gap: spacing.sm },
  countBtn: {
    flex: 1,
    paddingVertical: spacing.md,
    borderRadius: radius.md,
    borderWidth: 1.5,
    borderColor: colors.border,
    backgroundColor: colors.background,
    alignItems: 'center',
  },
  countBtnActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primary,
  },
  countBtnText:       { fontSize: 16, fontWeight: '700', color: colors.textMuted },
  countBtnTextActive: { color: '#fff' },

  // Résumé de sélection
  summaryRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    minHeight: 18,
  },
  summaryText:  { fontSize: 13, color: colors.textMuted },
  limitedText:  { fontSize: 13, color: colors.warning, fontWeight: '600' },

  // Chargement
  loadingRow:  { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm },
  loadingText: { color: colors.textMuted, fontSize: 14 },

  // Tout sélectionner
  selectAllRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.xs,
    marginBottom: spacing.xs,
  },
  selectAllBox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 1.5,
    borderColor: colors.border,
    backgroundColor: colors.background,
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectAllBoxChecked: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  selectAllCheck: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '700',
    lineHeight: 15,
  },
  selectAllLabel: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '600',
  },

  // Liste catégories
  categoryList: { gap: spacing.sm },

  // Carte catégorie
  card: {
    backgroundColor: colors.background,
    borderRadius: radius.md,
    padding: spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderLeftWidth: 4,
    borderWidth: 1.5,
    borderColor: 'transparent',
  },
  cardSelected: {
    borderColor: colors.primary,
    backgroundColor: '#0f1f3d',
    ...Platform.select({
      android: { elevation: 3 },
      ios: {
        shadowColor: colors.primary,
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.25,
        shadowRadius: 6,
      },
    }),
  },
  cardIcon:      { fontSize: 22 },
  cardText:      { flex: 1 },
  cardName:      { fontSize: 15, fontWeight: '600', color: colors.text },
  cardAvailable: { fontSize: 12, color: colors.textMuted, marginTop: 2 },

  // Checkmark
  checkmark: {
    width: 24,
    height: 24,
    borderRadius: radius.full,
    backgroundColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  checkmarkText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '700',
    lineHeight: 16,
  },
  checkmarkPlaceholder: {
    width: 24,
    height: 24,
    borderRadius: radius.full,
    borderWidth: 1.5,
    borderColor: colors.border,
  },

  // Bouton créer
  createBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.md,
    paddingVertical: spacing.md,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 52,
  },
  createBtnDisabled: { opacity: 0.45 },
  createBtnText:     { color: '#fff', fontSize: 16, fontWeight: '700' },
  createBtnSub:      { color: 'rgba(255,255,255,0.75)', fontSize: 12, marginTop: 2 },

  // Séparateur
  dividerRow:  { flexDirection: 'row', alignItems: 'center', marginVertical: spacing.lg, gap: spacing.md },
  dividerLine: { flex: 1, height: 1, backgroundColor: colors.border },
  dividerText: { color: colors.textMuted, fontSize: 14 },
});
