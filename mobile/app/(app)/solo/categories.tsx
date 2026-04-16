import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Platform,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { colors, radius, spacing } from '../../../src/theme';

interface Category {
  id: string;
  name: string;
  icon: string | null;
  color: string | null;
  question_count: number;
}

const QUESTION_COUNTS = [10, 20, 30] as const;
type QuestionCount = (typeof QUESTION_COUNTS)[number];

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
      <Text style={styles.icon}>{item.icon ?? '📚'}</Text>

      <View style={styles.cardText}>
        <Text style={styles.name}>{item.name}</Text>
        <Text style={styles.available}>
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

// ─── CategoriesScreen ─────────────────────────────────────────────────────────

export default function CategoriesScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();

  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [starting, setStarting] = useState(false);
  const [error, setError] = useState('');
  const [maxQuestions, setMaxQ] = useState<QuestionCount>(10);
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());

  // ── Chargement des catégories ──────────────────────────────────────────────

  useEffect(() => {
    apiClient
      .get('/v1/categories')
      .then(({ data }) => setCategories(data.data as Category[]))
      .catch((e: unknown) => {
        const msg = e instanceof Error ? e.message : 'Erreur de chargement';
        setError(msg);
      })
      .finally(() => setLoading(false));
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

  // ── Calculs dérivés ────────────────────────────────────────────────────────

  const { totalAvailable, effective, isLimited, selectionSummary } =
    useMemo(() => {
      const selected = categories.filter((c) => selectedIds.has(c.id));
      const total = selected.reduce((acc, c) => acc + c.question_count, 0);
      const eff = Math.min(maxQuestions, total);
      const limited = eff < maxQuestions && total > 0;
      const count = selected.length;
      const summary =
        count === 0
          ? 'Aucune catégorie sélectionnée'
          : `${count} catégorie${count > 1 ? 's' : ''} · ${total} question${total !== 1 ? 's' : ''} disponible${total !== 1 ? 's' : ''}`;

      return {
        totalAvailable: total,
        effective: eff,
        isLimited: limited,
        selectionSummary: summary,
      };
    }, [categories, selectedIds, maxQuestions]);

  const hasSelection = selectedIds.size > 0;

  // ── Lancement de la session ───────────────────────────────────────────────

  const startSession = useCallback(async () => {
    if (!hasSelection || starting) return;
    setStarting(true);
    setError('');
    try {
      const { data } = await apiClient.post('/v1/sessions', {
        category_ids: Array.from(selectedIds),
        max_questions: effective,
      });
      router.push({
        pathname: '/solo/quiz',
        params: {
          sessionId: data.data.id,
          maxQuestions: effective.toString(),
        },
      });
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : 'Impossible de démarrer la session';
      setError(msg);
      setStarting(false);
    }
  }, [hasSelection, starting, selectedIds, effective, router]);

  // ── Rendu ─────────────────────────────────────────────────────────────────

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.primary} size="large" />
        <Text style={styles.loadingText}>Chargement des catégories…</Text>
      </View>
    );
  }

  const bottomBarHeight =
    styles.bottomBar.paddingVertical * 2 +
    styles.launchBtn.paddingVertical * 2 +
    24 + // texte bouton
    insets.bottom;

  return (
    <View style={styles.container}>
      {/* Titre */}
      <Text style={styles.title}>Choisir les catégories</Text>

      {/* Sélecteur du nombre de questions */}
      <View style={styles.countSection}>
        <Text style={styles.countLabel}>Nombre de questions souhaité</Text>
        <View style={styles.countRow}>
          {QUESTION_COUNTS.map((count) => (
            <TouchableOpacity
              key={count}
              style={[
                styles.countBtn,
                maxQuestions === count && styles.countBtnActive,
              ]}
              onPress={() => setMaxQ(count)}
              activeOpacity={0.8}
              accessibilityRole="radio"
              accessibilityState={{ selected: maxQuestions === count }}
              accessibilityLabel={`${count} questions`}
            >
              <Text
                style={[
                  styles.countBtnText,
                  maxQuestions === count && styles.countBtnTextActive,
                ]}
              >
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
          <Text style={styles.limitedText}>
            · sera limité à {effective}
          </Text>
        )}
      </View>

      {error ? <Text style={styles.error}>{error}</Text> : null}

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

      {/* Liste des catégories */}
      <FlatList
        data={categories}
        keyExtractor={(item) => item.id}
        contentContainerStyle={[
          styles.list,
          { paddingBottom: bottomBarHeight + spacing.md },
        ]}
        renderItem={({ item }) => (
          <CategoryCard
            item={item}
            selected={selectedIds.has(item.id)}
            onPress={toggleCategory}
          />
        )}
        showsVerticalScrollIndicator={false}
      />

      {/* Bouton lancer — fixe en bas */}
      {hasSelection && (
        <View
          style={[
            styles.bottomBar,
            { paddingBottom: insets.bottom > 0 ? insets.bottom : spacing.lg },
          ]}
        >
          <TouchableOpacity
            style={[
              styles.launchBtn,
              (!hasSelection || starting) && styles.launchBtnDisabled,
            ]}
            onPress={startSession}
            disabled={!hasSelection || starting}
            activeOpacity={0.85}
            accessibilityRole="button"
            accessibilityLabel="Lancer le quiz"
          >
            {starting ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <>
                <Text style={styles.launchBtnText}>Lancer le quiz</Text>
                {isLimited && (
                  <Text style={styles.launchBtnSub}>
                    {effective} questions · limité au disponible
                  </Text>
                )}
                {!isLimited && totalAvailable > 0 && (
                  <Text style={styles.launchBtnSub}>
                    {effective} question{effective !== 1 ? 's' : ''}
                  </Text>
                )}
              </>
            )}
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  // Layout principal
  container: {
    flex: 1,
    backgroundColor: colors.background,
    paddingTop: spacing.lg,
    paddingHorizontal: spacing.lg,
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
    gap: spacing.md,
  },
  loadingText: {
    color: colors.textMuted,
    fontSize: 14,
  },

  // Titre
  title: {
    fontSize: 22,
    fontWeight: '700',
    color: colors.text,
    marginBottom: spacing.lg,
  },

  // Sélecteur nombre de questions
  countSection: {
    marginBottom: spacing.md,
  },
  countLabel: {
    color: colors.textMuted,
    fontSize: 12,
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginBottom: spacing.sm,
  },
  countRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  countBtn: {
    flex: 1,
    paddingVertical: spacing.md,
    borderRadius: radius.md,
    borderWidth: 1.5,
    borderColor: colors.border,
    backgroundColor: colors.surface,
    alignItems: 'center',
  },
  countBtnActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primary,
  },
  countBtnText: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textMuted,
  },
  countBtnTextActive: {
    color: '#fff',
  },

  // Résumé de sélection
  summaryRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    marginBottom: spacing.sm,
    minHeight: 20,
  },
  summaryText: {
    fontSize: 13,
    color: colors.textMuted,
  },
  limitedText: {
    fontSize: 13,
    color: colors.warning,
    fontWeight: '600',
  },

  // Erreur
  error: {
    color: colors.error,
    textAlign: 'center',
    marginBottom: spacing.md,
    fontSize: 13,
  },

  // Tout sélectionner
  selectAllRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm,
    marginBottom: spacing.xs,
  },
  selectAllBox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 1.5,
    borderColor: colors.border,
    backgroundColor: colors.surface,
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

  // Liste
  list: {
    gap: spacing.sm,
    paddingTop: spacing.xs,
  },

  // Carte catégorie
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.lg,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderLeftWidth: 4,
    borderWidth: 1.5,
    borderColor: 'transparent',
  },
  cardSelected: {
    borderColor: colors.primary,
    backgroundColor: '#1e293b',
    // léger teinté — on simule avec une ombre de couleur sur Android
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
  cardText: {
    flex: 1,
  },
  icon: {
    fontSize: 24,
  },
  name: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.text,
  },
  available: {
    fontSize: 12,
    color: colors.textMuted,
    marginTop: 2,
  },

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

  // Barre de lancement fixe
  bottomBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: colors.background,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  launchBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.md,
    paddingVertical: spacing.md,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 52,
  },
  launchBtnDisabled: {
    opacity: 0.5,
  },
  launchBtnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
  launchBtnSub: {
    color: 'rgba(255,255,255,0.75)',
    fontSize: 12,
    marginTop: 2,
  },
});
