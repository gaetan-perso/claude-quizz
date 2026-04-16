import { useEffect, useRef, useState, useCallback } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  ScrollView,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { getEcho } from '../../../src/lib/echo';
import { colors, spacing, radius } from '../../../src/theme';

// ── Types ─────────────────────────────────────────────────────────────────────

interface Choice {
  id: string;
  text: string;
}

interface Question {
  id: string;
  text: string;
  estimated_time_seconds: number;
  choices: Choice[];
  category?: string;
}

interface AnswerResult {
  selectedId: string;
  correctId: string;
  isCorrect: boolean;
  explanation: string | null;
  score: number;
}

interface QuestionReadyEvent {
  question_index: number;
  total_questions: number;
  started_at: string;
  question: Question;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function computeTimeLeft(startedAt: string, estimatedSeconds: number): number {
  const elapsed = Math.floor((Date.now() - new Date(startedAt).getTime()) / 1000);
  return Math.max(0, estimatedSeconds - elapsed);
}

function timerColor(timeLeft: number, total: number): string {
  const ratio = total > 0 ? timeLeft / total : 0;
  if (ratio > 0.5) return colors.success;
  if (ratio > 0.2) return '#f59e0b'; // amber-500
  return colors.error;
}

// ── Composant principal ───────────────────────────────────────────────────────

export default function MultiQuizScreen() {
  const { sessionId, lobbyId, isHost } = useLocalSearchParams<{
    sessionId: string;
    lobbyId: string;
    isHost: string;
  }>();
  const router = useRouter();
  const isHostBool = isHost === '1';

  // ── État de la question courante
  const [question, setQuestion]         = useState<Question | null>(null);
  const [questionIndex, setQuestionIndex] = useState<number>(0);
  const [totalQuestions, setTotalQuestions] = useState<number>(0);
  const [shuffledChoices, setShuffledChoices] = useState<Choice[]>([]);
  const [startedAt, setStartedAt]       = useState<string>('');

  // ── État de réponse et feedback
  const [result, setResult]             = useState<AnswerResult | null>(null);
  const [score, setScore]               = useState(0);
  const [submitting, setSubmitting]     = useState(false);
  const [hasAnswered, setHasAnswered]   = useState(false);
  const [timedOut, setTimedOut]         = useState(false);

  // ── Timer
  const [timeLeft, setTimeLeft]         = useState(0);

  // ── État global de l'écran
  const [wsConnected, setWsConnected]   = useState(false);

  // ── Refs pour éviter les race conditions et les fuites
  const navigatedRef     = useRef(false);
  const intervalRef      = useRef<ReturnType<typeof setInterval> | null>(null);
  const mountedRef       = useRef(true);
  const advancingRef     = useRef(false);
  // Snapshot du score pour les handlers WS (closure stable)
  const scoreRef         = useRef(score);
  const hasAnsweredRef   = useRef(hasAnswered);
  const isHostRef        = useRef(isHostBool);
  const questionRef      = useRef<Question | null>(null);

  useEffect(() => { scoreRef.current = score; }, [score]);
  useEffect(() => { hasAnsweredRef.current = hasAnswered; }, [hasAnswered]);
  useEffect(() => { questionRef.current = question; }, [question]);

  // ── Nettoyage du timer ────────────────────────────────────────────────────

  function clearTimer() {
    if (intervalRef.current !== null) {
      clearInterval(intervalRef.current);
      intervalRef.current = null;
    }
  }

  // ── Avancer vers la question suivante (hôte uniquement) ──────────────────

  const advanceLobby = useCallback(async () => {
    if (!isHostRef.current || advancingRef.current) return;
    advancingRef.current = true;
    try {
      await apiClient.post(`/v1/lobbies/${lobbyId}/advance`);
    } catch {
      // On ne crashe pas — le backend peut avoir déjà avancé
    } finally {
      advancingRef.current = false;
    }
  }, [lobbyId]);

  // ── Démarrer le timer pour la question courante ───────────────────────────

  const startTimer = useCallback((startedAtISO: string, estimatedSeconds: number) => {
    clearTimer();

    // Calcul immédiat pour éviter le flash à 0
    const initial = computeTimeLeft(startedAtISO, estimatedSeconds);
    if (mountedRef.current) setTimeLeft(initial);

    if (initial === 0) {
      // Déjà expiré au moment du rendu
      if (mountedRef.current) setTimedOut(true);
      advanceLobby();
      return;
    }

    intervalRef.current = setInterval(() => {
      const remaining = computeTimeLeft(startedAtISO, estimatedSeconds);
      if (!mountedRef.current) return;
      setTimeLeft(remaining);

      if (remaining === 0) {
        clearTimer();
        setTimedOut(true);
        // L'hôte appelle advance, tous les joueurs pollent la question suivante
        advanceLobby();
        if (questionRef.current) {
          waitForNextQuestion(questionRef.current.id);
        }
      }
    }, 1000);
  }, [advanceLobby]);

  // ── Afficher une question (WebSocket ou API fallback) ─────────────────────

  const applyQuestion = useCallback((
    q: Question,
    index: number,
    total: number,
    startISO: string,
  ) => {
    if (!mountedRef.current) return;
    const shuffled = [...q.choices].sort(() => Math.random() - 0.5);
    setQuestion(q);
    setQuestionIndex(index);
    setTotalQuestions(total);
    setStartedAt(startISO);
    setShuffledChoices(shuffled);
    setResult(null);
    setHasAnswered(false);
    setTimedOut(false);
    setWsConnected(true);
    startTimer(startISO, q.estimated_time_seconds);
  }, [startTimer]);

  // ── Fallback API : récupère la question courante si WS manqué ─────────────

  const fetchCurrentQuestion = useCallback(async () => {
    try {
      const { data } = await apiClient.get(`/v1/sessions/${sessionId}/next-question`);
      if (!mountedRef.current || !data.data) return;
      const d = data.data;
      // Ignorer si WebSocket a déjà livré la question
      if (wsConnected) return;
      applyQuestion(
        d.question,
        d.question_index ?? 0,
        d.total_questions ?? 1,
        new Date().toISOString(), // on repart du moment actuel
      );
    } catch {
      // Ignorer silencieusement
    }
  }, [sessionId, applyQuestion, wsConnected]);

  // ── Connexion WebSocket + fallback API au montage ─────────────────────────

  useEffect(() => {
    if (!lobbyId) return;
    mountedRef.current = true;

    const channelName = `lobby.${lobbyId}`;
    let cleanup: (() => void) | null = null;

    // Fallback : si WS tarde, on charge la question via l'API
    const fallbackTimer = setTimeout(fetchCurrentQuestion, 800);

    getEcho()
      .then((echo) => {
        if (!mountedRef.current) return;

        const channel = echo.join(channelName);

        channel.listen('.question.ready', (event: QuestionReadyEvent) => {
          if (!mountedRef.current) return;
          applyQuestion(
            event.question,
            event.question_index,
            event.total_questions,
            event.started_at,
          );
        });

        channel.listen('.game.completed', () => {
          if (!mountedRef.current || navigatedRef.current) return;
          navigatedRef.current = true;
          clearTimer();
          router.replace({
            pathname: '/multi/results',
            params: { lobbyId, score: scoreRef.current.toString() },
          });
        });

        cleanup = () => {
          echo.leave(channelName);
        };
      })
      .catch(() => {
        // WebSocket indisponible — le fallback API prend le relais
      });

    return () => {
      mountedRef.current = false;
      clearTimeout(fallbackTimer);
      clearTimer();
      cleanup?.();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [lobbyId]);

  // ── Polling après réponse : attend la question suivante ──────────────────

  const waitForNextQuestion = useCallback((currentQuestionId: string) => {
    const pollNext = setInterval(async () => {
      if (!mountedRef.current) { clearInterval(pollNext); return; }
      try {
        const { data } = await apiClient.get(`/v1/sessions/${sessionId}/next-question`);
        if (!mountedRef.current) { clearInterval(pollNext); return; }
        if (!data.data) {
          // Plus de questions → fin de partie
          clearInterval(pollNext);
          if (!navigatedRef.current) {
            navigatedRef.current = true;
            clearTimer();
            router.replace({ pathname: '/multi/results', params: { lobbyId, score: scoreRef.current.toString() } });
          }
          return;
        }
        // Nouvelle question disponible ?
        if (data.data.question.id !== currentQuestionId) {
          clearInterval(pollNext);
          applyQuestion(
            data.data.question,
            data.data.question_index ?? 0,
            data.data.total_questions ?? 1,
            new Date().toISOString(),
          );
        }
      } catch { /* ignorer */ }
    }, 1500);
  }, [sessionId, lobbyId, router, applyQuestion, clearTimer]);

  // ── Soumettre une réponse ─────────────────────────────────────────────────

  const submitAnswer = useCallback(async (choiceId: string) => {
    if (result || submitting || hasAnsweredRef.current || !question) return;
    setSubmitting(true);
    try {
      const { data } = await apiClient.post(`/v1/sessions/${sessionId}/answers`, {
        question_id: question.id,
        choice_id:   choiceId,
      });
      if (!mountedRef.current) return;
      const d = data.data;
      const newScore: number = d.score;
      setResult({
        selectedId:  choiceId,
        correctId:   d.correct_choice_id,
        isCorrect:   d.is_correct,
        explanation: d.explanation ?? null,
        score:       newScore,
      });
      setScore(newScore);
      setHasAnswered(true);
      // L'hôte avance si le timer est déjà expiré au moment de la réponse
      if (timedOut) advanceLobby();
      // Polling de secours : attend la prochaine question si WS ne la livre pas
      waitForNextQuestion(question.id);
    } catch {
      // Erreur réseau — on laisse l'utilisateur réessayer
    } finally {
      if (mountedRef.current) setSubmitting(false);
    }
  }, [result, submitting, question, sessionId, timedOut, advanceLobby, waitForNextQuestion]);

  // ── Calcul de la couleur du timer ─────────────────────────────────────────

  const estimatedSeconds = question?.estimated_time_seconds ?? 0;
  const tColor = timerColor(timeLeft, estimatedSeconds);
  const progressRatio = estimatedSeconds > 0 ? timeLeft / estimatedSeconds : 0;

  // ── Couleur d'un bouton de choix ──────────────────────────────────────────

  function choiceStyle(choiceId: string): object | object[] {
    if (!result) return styles.choice;
    if (choiceId === result.correctId)  return [styles.choice, styles.choiceCorrect];
    if (choiceId === result.selectedId) return [styles.choice, styles.choiceWrong];
    return [styles.choice, styles.choiceDimmed];
  }

  // ── Rendu ─────────────────────────────────────────────────────────────────

  // Spinner pendant la connexion WebSocket initiale
  if (!wsConnected || !question) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.primary} size="large" />
        <Text style={styles.loadingText}>Connexion au quiz…</Text>
      </View>
    );
  }

  const choicesDisabled = !!result || submitting || timedOut;

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>

      {/* ── Header ─────────────────────────────────────────────── */}
      <View style={styles.header}>
        <Text style={styles.counter}>
          Q{questionIndex + 1}/{totalQuestions}
        </Text>
        <View style={styles.scoreBadge}>
          <Text style={styles.scoreText}>{score} pts</Text>
        </View>
      </View>

      {/* ── Barre de timer ─────────────────────────────────────── */}
      <View style={styles.timerWrapper}>
        <View style={styles.timerTrack}>
          <View
            style={[
              styles.timerFill,
              { width: `${Math.round(progressRatio * 100)}%`, backgroundColor: tColor },
            ]}
          />
        </View>
        <Text style={[styles.timerLabel, { color: tColor }]}>{timeLeft}s</Text>
      </View>

      {/* ── Catégorie de la question ────────────────────────────── */}
      {question.category ? (
        <Text style={styles.categoryLabel}>{question.category}</Text>
      ) : null}

      {/* ── Question ───────────────────────────────────────────── */}
      <Text style={styles.questionText}>{question.text}</Text>

      {/* ── Choix ──────────────────────────────────────────────── */}
      <View style={styles.choices}>
        {shuffledChoices.map((choice) => (
          <TouchableOpacity
            key={choice.id}
            style={choiceStyle(choice.id)}
            onPress={() => submitAnswer(choice.id)}
            disabled={choicesDisabled}
            activeOpacity={0.8}
          >
            <Text style={styles.choiceText}>{choice.text}</Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* ── Banner timeout (sans réponse) ─────────────────────── */}
      {timedOut && !result && (
        <View style={styles.timeoutBanner}>
          <Text style={styles.timeoutText}>Temps ecoulé !</Text>
        </View>
      )}

      {/* ── Feedback après réponse ────────────────────────────── */}
      {result && (
        <View style={styles.feedback}>
          <Text style={[styles.feedbackTitle, { color: result.isCorrect ? colors.success : colors.error }]}>
            {result.isCorrect ? '✓ Correct !' : '✗ Incorrect'}
          </Text>
          {result.explanation ? (
            <Text style={styles.explanation}>{result.explanation}</Text>
          ) : null}
          <View style={styles.waitingBanner}>
            <ActivityIndicator color={colors.primary} size="small" style={styles.waitingSpinner} />
            <Text style={styles.waitingText}>En attente des autres joueurs…</Text>
          </View>
        </View>
      )}

    </ScrollView>
  );
}

// ── Styles ────────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  content: {
    padding: spacing.lg,
    paddingBottom: spacing.xl,
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
    fontSize: 15,
    marginTop: spacing.sm,
  },

  // Header
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.md,
  },
  counter: {
    color: colors.textMuted,
    fontSize: 15,
    fontWeight: '600',
  },
  scoreBadge: {
    backgroundColor: colors.surface,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    borderRadius: radius.full,
    borderWidth: 1,
    borderColor: colors.primary,
  },
  scoreText: {
    color: colors.primary,
    fontWeight: '700',
    fontSize: 14,
  },

  // Timer
  timerWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginBottom: spacing.xl,
  },
  timerTrack: {
    flex: 1,
    height: 8,
    backgroundColor: colors.surface,
    borderRadius: radius.full,
    overflow: 'hidden',
  },
  timerFill: {
    height: '100%',
    borderRadius: radius.full,
  },
  timerLabel: {
    fontSize: 14,
    fontWeight: '700',
    minWidth: 32,
    textAlign: 'right',
  },

  // Catégorie
  categoryLabel: {
    fontSize: 12,
    color: colors.primary,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginBottom: spacing.sm,
  },

  // Question
  questionText: {
    fontSize: 20,
    fontWeight: '700',
    color: colors.text,
    marginBottom: spacing.xl,
    lineHeight: 28,
  },

  // Choix
  choices: {
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  choice: {
    padding: spacing.lg,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  choiceCorrect: {
    backgroundColor: '#14532d',
    borderColor: colors.success,
  },
  choiceWrong: {
    backgroundColor: '#450a0a',
    borderColor: colors.error,
  },
  choiceDimmed: {
    opacity: 0.4,
  },
  choiceText: {
    color: colors.text,
    fontSize: 16,
    lineHeight: 22,
  },

  // Timeout banner
  timeoutBanner: {
    backgroundColor: '#450a0a',
    borderRadius: radius.md,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.error,
    alignItems: 'center',
    marginBottom: spacing.lg,
  },
  timeoutText: {
    color: colors.error,
    fontWeight: '700',
    fontSize: 16,
  },

  // Feedback
  feedback: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
  },
  feedbackTitle: {
    fontSize: 18,
    fontWeight: '700',
    marginBottom: spacing.sm,
  },
  explanation: {
    color: colors.textMuted,
    fontSize: 14,
    lineHeight: 20,
    marginBottom: spacing.md,
  },

  // Waiting banner (dans le feedback)
  waitingBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.background,
    borderRadius: radius.md,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  waitingSpinner: {
    marginRight: spacing.sm,
  },
  waitingText: {
    color: colors.textMuted,
    fontSize: 14,
  },
});
