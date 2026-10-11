import React from 'react';
import Feather from '@expo/vector-icons/Feather';

/**
 * Line icons only (Feather, the same family as the website's Lucide sprite)
 * so the app never falls back to emoji.
 */
export type IconName = React.ComponentProps<typeof Feather>['name'];

export default function Icon({ name, size = 20, color }: { name: IconName; size?: number; color: string }) {
  return <Feather name={name} size={size} color={color} />;
}
