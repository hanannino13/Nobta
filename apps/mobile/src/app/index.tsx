import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { APP_NAME } from '@/config/app';

/**
 * M1 placeholder. Replaced by the role-based entry flow (auth → context switcher) in M2/M6.
 */
export default function HomeScreen() {
  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.content}>
        <Text accessibilityRole="header" style={styles.title}>
          {APP_NAME}
        </Text>
        <Text style={styles.subtitle}>Development build</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1 },
  content: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    gap: 8,
  },
  title: { fontSize: 32, fontWeight: '700', textAlign: 'center' },
  subtitle: { fontSize: 16, opacity: 0.7, textAlign: 'center' },
});
