import { TextInput, View, Text, StyleSheet, TextInputProps } from 'react-native';
import { colors, radius, spacing } from '../theme';

interface Props extends TextInputProps {
  label?: string;
  error?: string;
}

export function Input({ label, error, style, ...props }: Props) {
  return (
    <View style={styles.wrapper}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <TextInput
        {...props}
        style={[styles.input, error ? styles.inputError : null, style]}
        placeholderTextColor={colors.textMuted}
      />
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    marginBottom: spacing.md,
  },
  label: {
    color:        colors.textMuted,
    marginBottom: spacing.xs,
    fontSize:     14,
    fontWeight:   '500',
  },
  input: {
    backgroundColor: colors.surface,
    color:           colors.text,
    padding:         spacing.md,
    borderRadius:    radius.md,
    borderWidth:     1,
    borderColor:     colors.border,
    fontSize:        16,
    minHeight:       52,
  },
  inputError: {
    borderColor: colors.error,
  },
  error: {
    color:     colors.error,
    fontSize:  12,
    marginTop: spacing.xs,
  },
});
