---
name: react-native-best-practices
description: Guide de performance React Native (Expo) pour le client mobile du quiz. Activer pour tout travail sur les composants, animations, listes, connexion WebSocket ou bundle size. Basé sur le guide Callstack Ultimate React Native Optimization.
---

# React Native Best Practices

Source : [callstackincubator/agent-skills](https://github.com/callstackincubator/agent-skills/blob/main/skills/react-native-best-practices/SKILL.md)

## Cycle obligatoire

```
Mesure → Optimise → Re-mesure → Valide
```

Ne jamais optimiser sans mesurer d'abord. Ne jamais valider sans re-mesurer après.

## Priorités par impact

| Priorité | Problème | Impact |
|---|---|---|
| CRITIQUE | FPS / Re-renders | Fluidité de l'UI quiz |
| CRITIQUE | Bundle size | Temps de démarrage |
| HIGH | TTI (Time to Interactive) | Expérience de lancement |
| HIGH | Performance native | Animations timer, transitions |
| MEDIUM | Gestion mémoire | Sessions longues |
| MEDIUM | Animations | Timer de question |

## Performances FPS — CRITIQUE

### Identifier les re-renders inutiles

```bash
# Activer le profiler React
npx react-devtools
```

### React.memo pour les composants de liste

```typescript
// ✅ Correct
const ChoiceButton = React.memo(({ choice, onSelect, isSelected }: Props) => {
    return (
        <Pressable
            onPress={() => onSelect(choice.id)}
            style={[styles.button, isSelected && styles.selected]}
        >
            <Text>{choice.text}</Text>
        </Pressable>
    );
}, (prev, next) => prev.choice.id === next.choice.id && prev.isSelected === next.isSelected);

// ❌ Interdit — re-render à chaque update de score
const ChoiceButton = ({ choice, onSelect, isSelected }) => { ... };
```

### useCallback pour les handlers

```typescript
// ✅ Correct
const handleSelect = useCallback((choiceId: string) => {
    submitAnswer(choiceId);
}, [submitAnswer]);

// ❌ Crée une nouvelle fonction à chaque render
const handleSelect = (choiceId: string) => { submitAnswer(choiceId); };
```

### Sélecteurs Zustand granulaires — éviter les re-renders de score

```typescript
// ✅ S'abonner uniquement à ce dont on a besoin
const myScore = useQuizStore((state) => state.scores[playerId]);
const currentQuestion = useQuizStore((state) => state.currentQuestion);

// ❌ Re-render à chaque update du store
const { myScore, currentQuestion, players, session } = useQuizStore();
```

## Bundle Size — CRITIQUE

### Pas de barrel exports

```typescript
// ✅ Import direct
import QuestionCard from '@/components/QuestionCard';
import { Difficulty } from '@/types/quiz';

// ❌ Barrel export — empêche le tree shaking
import { QuestionCard, PlayerAvatar, ScoreBar } from '@/components';
```

### Analyser le bundle

```bash
npx expo export --analyze
# Vérifier : aucun module > 100kb inattendu
```

### Lazy loading des écrans non-critiques

```typescript
const LeaderboardScreen = lazy(() => import('./screens/Leaderboard'));
```

## Animations — Thread natif

```typescript
// ✅ react-native-reanimated — s'exécute sur le thread natif
import Animated, { useSharedValue, withTiming, useAnimatedStyle } from 'react-native-reanimated';

const timerProgress = useSharedValue(1);

const animatedStyle = useAnimatedStyle(() => ({
    width: `${timerProgress.value * 100}%`,
}));

// Démarrer le timer
timerProgress.value = withTiming(0, { duration: questionDurationMs });

// ❌ Animated de React Native — JS thread, jank sous charge
import { Animated } from 'react-native';
```

## FlatList pour les listes de questions

```typescript
// ✅ getItemLayout pour les listes de taille fixe
<FlatList
    data={questions}
    renderItem={renderQuestion}
    keyExtractor={(item) => item.id}
    getItemLayout={(_, index) => ({
        length: QUESTION_ITEM_HEIGHT,
        offset: QUESTION_ITEM_HEIGHT * index,
        index,
    })}
    removeClippedSubviews
    maxToRenderPerBatch={5}
    windowSize={10}
/>
```

## Images

```typescript
// ✅ expo-image — cache natif, formats modernes
import { Image } from 'expo-image';
<Image source={avatarUrl} style={styles.avatar} contentFit="cover" />

// ❌ Image de React Native — pas de cache natif
import { Image } from 'react-native';
```

## Gestion de la connexion WebSocket

```typescript
// Reconnexion automatique avec état visible
const useQuizSession = (sessionId: string) => {
    const [connectionState, setConnectionState] = useState<'connected' | 'disconnected' | 'reconnecting'>('disconnected');

    useEffect(() => {
        const channel = pusher.subscribe(`private-quiz-session.${sessionId}`);

        pusher.connection.bind('connected', () => setConnectionState('connected'));
        pusher.connection.bind('disconnected', () => setConnectionState('disconnected'));
        pusher.connection.bind('connecting', () => setConnectionState('reconnecting'));

        return () => {
            pusher.unsubscribe(`private-quiz-session.${sessionId}`);
        };
    }, [sessionId]);

    return { connectionState };
};
```

## TypeScript strict

```typescript
// tsconfig.json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true
  }
}
```

Zéro `any` dans le code de production.

## Checklist avant PR

- [ ] FPS mesuré avec Flipper avant et après
- [ ] Bundle size analysé (`npx expo export --analyze`)
- [ ] `React.memo` sur tous les composants de liste
- [ ] Sélecteurs Zustand granulaires
- [ ] Animations sur thread natif (reanimated)
- [ ] Reconnexion WebSocket testée
- [ ] TypeScript strict — zéro `any`
- [ ] Tests Jest verts
