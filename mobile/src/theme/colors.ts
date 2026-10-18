// Brand palette — matches web/public/assets/css/app.css. Keep the two in sync.
export const brand = {
  navy: '#232D47',
  navyDark: '#161C2E',
  navyLight: '#4F5F8A',
  gold: '#C9A227',
  goldLight: '#F0D878',
};

export type Palette = {
  bg: string;
  surface: string;
  surface2: string;
  border: string;
  text: string;
  text2: string;
  muted: string;
  brand: string;
  brandSoft: string;
  brandInk: string;
  accent: string;
  accentSoft: string;
  accentInk: string;
  success: string;
  successSoft: string;
  danger: string;
  dangerSoft: string;
  onBrand: string;
  overlay: string;
  highlight: Record<string, string>;
};

export const light: Palette = {
  bg: '#F6F7FA',
  surface: '#FFFFFF',
  surface2: '#EFF1F6',
  border: '#E0E4EF',
  text: '#12151C',
  text2: '#353A48',
  muted: '#676C79',
  brand: brand.navy,
  brandSoft: '#ECEEF5',
  brandInk: '#1B2338',
  accent: brand.gold,
  accentSoft: '#FAF3DC',
  accentInk: '#7A5B00',
  success: '#1E8A5A',
  successSoft: '#E3F4EB',
  danger: '#C0392B',
  dangerSoft: '#FBE9E7',
  onBrand: '#FFFFFF',
  overlay: 'rgba(18,21,28,0.45)',
  highlight: { yellow: '#FFF1A8', green: '#CFF2D8', blue: '#D3E6FF', pink: '#FFD6E7', purple: '#E6D9FB' },
};

export const dark: Palette = {
  bg: '#0D1017',
  surface: '#161A24',
  surface2: '#1D2230',
  border: '#272E40',
  text: '#F1F3F8',
  text2: '#CFD4E1',
  muted: '#959CAD',
  brand: '#6E7FAD',
  brandSoft: '#1F283D',
  brandInk: '#D2D9EC',
  accent: '#E0BC4A',
  accentSoft: '#33290F',
  accentInk: '#F0D878',
  success: '#4CC38A',
  successSoft: '#13291F',
  danger: '#F07B6E',
  dangerSoft: '#341714',
  onBrand: '#FFFFFF',
  overlay: 'rgba(0,0,0,0.6)',
  highlight: { yellow: '#5A4E12', green: '#1D4A2D', blue: '#1D3557', pink: '#55203A', purple: '#3B2A5E' },
};

export const radius = { sm: 8, md: 12, lg: 16, xl: 22, pill: 999 };
export const space = { xs: 4, sm: 8, md: 12, lg: 16, xl: 24, xxl: 32 };

export const type = {
  display: { fontSize: 28, fontWeight: '800' as const, letterSpacing: -0.4 },
  title: { fontSize: 22, fontWeight: '700' as const, letterSpacing: -0.2 },
  heading: { fontSize: 17, fontWeight: '700' as const },
  body: { fontSize: 15, lineHeight: 22 },
  small: { fontSize: 13, lineHeight: 18 },
  tiny: { fontSize: 11, fontWeight: '700' as const, letterSpacing: 0.8, textTransform: 'uppercase' as const },
  serif: 'Georgia',
};
