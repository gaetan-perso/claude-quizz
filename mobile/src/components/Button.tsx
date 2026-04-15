import {
  TouchableOpacity,
  Text,
  ActivityIndicator,
  StyleSheet,
  ViewStyle,
} from 'react-native';
import { colors, radius, spacing } from '../theme';

interface Props {
  label: string;
  onPress: () => void;
  loading?: boolean;
  variant?: 'primary' | 'outline' | 'ghost';
  disabled?: boolean;
  style?: ViewStyle;
}

export function Button({
  label,
  onPress,
  loading = false,
  variant = 'primary',
  disabled = false,
  style,
}: Props) {
  const bg          = variant === 'primary' ? colors.primary : 'transparent';
  const borderColor = variant === 'outline'  ? colors.primary : 'transparent';
  const textColor   = variant === 'primary'  ? '#fff'         : colors.primary;

  return (
    <TouchableOpacity
      onPress={onPress}
      disabled={disabled || loading}
      style={[
        styles.btn,
        { backgroundColor: bg, borderColor },
        disabled && styles.disabled,
        style,
      ]}
      activeOpacity={0.8}
    >
      {loading ? (
        <ActivityIndicator color={textColor} />
      ) : (
        <Text style={[styles.label, { color: textColor }]}>{label}</Text>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  btn: {
    padding:       spacing.md,
    borderRadius:  radius.md,
    alignItems:    'center',
    borderWidth:   1.5,
    minHeight:     52,
    justifyContent:'center',
  },
  label: {
    fontSize:   16,
    fontWeight: '600',
  },
  disabled: {
    opacity: 0.5,
  },
});
