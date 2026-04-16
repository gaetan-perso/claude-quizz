import { useEffect, useRef, useState, useCallback } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator, ScrollView } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { apiClient } from '../../../src/lib/api';
import { getEcho } from '../../../src/lib/echo';
import { colors, spacing, radius } from '../../../src/theme';

interface Choice   { id: string; text: string; }
interface Question { id: string; text: string; estimated_time_seconds: number; choices: Choice[]; }
interface AnswerResult { selectedId: string; correctId: string; isCorrect: boolean; explanation: string | null; score: number; }

export default function MultiQuizScreen() {
  const { sessionId, lobbyId, isHost } = useLocalSearchParams<{ sessionId: string; lobbyId: string; isHost: string }>();
  const router = useRouter();

  const [question, setQuestion] = useState<Question | null>(null);
  const [loading, setLoading]   = useState(true);
  const [result, setResult]     = useState<AnswerResult | null>(null);
  const [score, setScore]       = useState(0);
  const [questionNum, setNum]   = useState(0);
  const [submitting, setSubmit] = useState(false);

  // Ref pour éviter la double navigation (fetchNext + game.completed)
  const navigatedRef = useRef(false);

  const fetchNext = useCallback(async () => {
    setLoading(true);
    setResult(null);
    try {
      const { data } = await apiClient.get(`/v1/sessions/${sessionId}/next-question`);
      if (data.data === null) {
        if (navigatedRef.current) return;
        navigatedRef.current = true;
        await apiClient.post(`/v1/sessions/${sessionId}/complete`);
        if (isHost === '1') {
          await apiClient.post(`/v1/lobbies/${lobbyId}/complete`);
        }
        router.replace({ pathname: '/multi/results', params: { lobbyId, score: score.toString() } });
        return;
      }
      setQuestion(data.data.question);
      setNum((n) => n + 1);
    } catch {
      if (navigatedRef.current) return;
      navigatedRef.current = true;
      router.replace({ pathname: '/multi/results', params: { lobbyId, score: score.toString() } });
    } finally {
      setLoading(false);
    }
  }, [sessionId, lobbyId, isHost, score, router]);

  // ── Premier chargement ────────────────────────────────────────────────────
  useEffect(() => { fetchNext(); }, []);

  // ── Listener WebSocket game.completed ─────────────────────────────────────
  useEffect(() => {
    if (!lobbyId) return;

    let channelName = `lobby.${lobbyId}`;
    let cleanup: (() => void) | null = null;

    getEcho().then((echo) => {
      const channel = echo.private(channelName);

      channel.listen('.game.completed', async () => {
        if (navigatedRef.current) return;
        navigatedRef.current = true;

        // Les non-hôtes complètent leur session avant de partir
        if (isHost !== '1') {
          try {
            await apiClient.post(`/v1/sessions/${sessionId}/complete`);
          } catch {
            // On navigue quoi qu'il arrive
          }
        }

        router.replace({ pathname: '/multi/results', params: { lobbyId, score: score.toString() } });
      });

      cleanup = () => {
        echo.leave(channelName);
      };
    }).catch(() => {
      // Connexion Echo impossible — on laisse le flux normal gérer la fin
    });

    return () => {
      cleanup?.();
    };
  }, [lobbyId, sessionId, isHost, score, router]);

  async function submitAnswer(choiceId: string) {
    if (result || submitting) return;
    setSubmit(true);
    try {
      const { data } = await apiClient.post(`/v1/sessions/${sessionId}/answers`, {
        question_id: question!.id,
        choice_id:   choiceId,
      });
      const d = data.data;
      setResult({ selectedId: choiceId, correctId: d.correct_choice_id, isCorrect: d.is_correct, explanation: d.explanation ?? null, score: d.score });
      setScore(d.score);
    } catch {
      // Ignorer silencieusement
    } finally {
      setSubmit(false);
    }
  }

  function choiceStyle(choiceId: string) {
    if (!result) return styles.choice;
    if (choiceId === result.correctId)  return [styles.choice, styles.correct];
    if (choiceId === result.selectedId) return [styles.choice, styles.wrong];
    return [styles.choice, styles.dimmed];
  }

  if (loading) return <View style={styles.center}><ActivityIndicator color={colors.primary} size="large" /></View>;
  if (!question) return null;

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <View style={styles.header}>
        <Text style={styles.counter}>Q{questionNum} · Multi</Text>
        <View style={styles.scoreBadge}><Text style={styles.scoreText}>{score} pts</Text></View>
      </View>

      <Text style={styles.questionText}>{question.text}</Text>

      <View style={styles.choices}>
        {question.choices.map((choice) => (
          <TouchableOpacity
            key={choice.id}
            style={choiceStyle(choice.id)}
            onPress={() => submitAnswer(choice.id)}
            disabled={!!result || submitting}
            activeOpacity={0.8}
          >
            <Text style={styles.choiceText}>{choice.text}</Text>
          </TouchableOpacity>
        ))}
      </View>

      {result && (
        <View style={styles.feedback}>
          <Text style={[styles.feedbackTitle, { color: result.isCorrect ? colors.success : colors.error }]}>
            {result.isCorrect ? '✓ Correct !' : '✗ Incorrect'}
          </Text>
          {result.explanation ? <Text style={styles.explanation}>{result.explanation}</Text> : null}
          <TouchableOpacity style={styles.nextBtn} onPress={fetchNext} activeOpacity={0.8}>
            <Text style={styles.nextBtnText}>Question suivante →</Text>
          </TouchableOpacity>
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: colors.background },
  content:      { padding: spacing.lg },
  center:       { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background },
  header:       { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.xl },
  counter:      { color: colors.textMuted, fontSize: 15 },
  scoreBadge:   { backgroundColor: colors.surface, paddingHorizontal: spacing.md, paddingVertical: spacing.xs, borderRadius: radius.full, borderWidth: 1, borderColor: colors.primary },
  scoreText:    { color: colors.primary, fontWeight: '700', fontSize: 14 },
  questionText: { fontSize: 20, fontWeight: '700', color: colors.text, marginBottom: spacing.xl, lineHeight: 28 },
  choices:      { gap: spacing.sm, marginBottom: spacing.lg },
  choice:       { padding: spacing.lg, borderRadius: radius.md, borderWidth: 1, borderColor: colors.border, backgroundColor: colors.surface },
  correct:      { backgroundColor: '#14532d', borderColor: colors.success },
  wrong:        { backgroundColor: '#450a0a', borderColor: colors.error },
  dimmed:       { opacity: 0.4 },
  choiceText:   { color: colors.text, fontSize: 16, lineHeight: 22 },
  feedback:     { backgroundColor: colors.surface, borderRadius: radius.lg, padding: spacing.lg, borderWidth: 1, borderColor: colors.border },
  feedbackTitle:{ fontSize: 18, fontWeight: '700', marginBottom: spacing.sm },
  explanation:  { color: colors.textMuted, fontSize: 14, lineHeight: 20, marginBottom: spacing.md },
  nextBtn:      { backgroundColor: colors.primary, padding: spacing.md, borderRadius: radius.md, alignItems: 'center' },
  nextBtnText:  { color: '#fff', fontWeight: '700', fontSize: 16 },
});
