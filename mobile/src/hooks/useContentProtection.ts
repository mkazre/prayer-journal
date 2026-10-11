import { useEffect } from 'react';
import { Platform } from 'react-native';
import * as ScreenCapture from 'expo-screen-capture';

/**
 * Blocks screenshots and screen recording while protected course content is
 * on screen (Android blocks them; iOS hides the content from recordings and
 * the app switcher). Turned on per course in Studio → Course settings.
 */
export function useContentProtection(enabled: boolean | undefined, key: string) {
  useEffect(() => {
    if (! enabled || Platform.OS === 'web') return;
    ScreenCapture.preventScreenCaptureAsync(key).catch(() => undefined);
    ScreenCapture.enableAppSwitcherProtectionAsync(0.9).catch(() => undefined);
    return () => {
      ScreenCapture.allowScreenCaptureAsync(key).catch(() => undefined);
      ScreenCapture.disableAppSwitcherProtectionAsync().catch(() => undefined);
    };
  }, [enabled, key]);
}
