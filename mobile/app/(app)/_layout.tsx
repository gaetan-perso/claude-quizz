import { Stack } from 'expo-router';
import { colors } from '../../src/theme';

export default function AppLayout() {
  return (
    <Stack
      screenOptions={{
        headerStyle:     { backgroundColor: '#1e1b4b' },
        headerTintColor: '#a5b4fc',
        headerTitleStyle: { fontWeight: '700' },
      }}
    />
  );
}
