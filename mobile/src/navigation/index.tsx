import React, { useEffect, useState } from 'react';
import { Platform, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { DarkTheme, DefaultTheme, NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useAuth } from '../context/AuthContext';
import { useTheme } from '../context/ThemeContext';
import Icon, { IconName } from '../components/Icon';
import { Logo } from '../components/ui';

import OnboardingScreen from '../screens/OnboardingScreen';
import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';
import HomeScreen from '../screens/HomeScreen';
import CoursesScreen from '../screens/CoursesScreen';
import CourseDetailScreen from '../screens/CourseDetailScreen';
import LessonScreen from '../screens/LessonScreen';
import QuizScreen from '../screens/QuizScreen';
import BibleScreen from '../screens/BibleScreen';
import BibleChapterScreen from '../screens/BibleChapterScreen';
import BibleSearchScreen from '../screens/BibleSearchScreen';
import JournalScreen from '../screens/JournalScreen';
import JournalEntryScreen from '../screens/JournalEntryScreen';
import PrayerWallScreen from '../screens/PrayerWallScreen';
import ShopScreen from '../screens/ShopScreen';
import ProductScreen from '../screens/ProductScreen';
import CartScreen from '../screens/CartScreen';
import ProfileScreen from '../screens/ProfileScreen';
import BlogArchiveScreen from '../screens/BlogArchiveScreen';
import BlogPostScreen from '../screens/BlogPostScreen';
import LibraryScreen from '../screens/LibraryScreen';

const Root = createNativeStackNavigator();
const Auth = createNativeStackNavigator();
const Tabs = createBottomTabNavigator();

const TAB_ICONS: Record<string, IconName> = { Home: 'home', Learn: 'award', Bible: 'book-open', Journal: 'feather', Shop: 'shopping-bag' };

function MainTabs() {
  const { c } = useTheme();
  const insets = useSafeAreaInsets();
  return (
    <Tabs.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarActiveTintColor: c.brand,
        tabBarInactiveTintColor: c.muted,
        tabBarLabelStyle: { fontSize: 11, fontWeight: '700' },
        tabBarIconStyle: { marginBottom: 2 },
        tabBarStyle: { backgroundColor: c.surface, borderTopColor: c.border, height: 64 + insets.bottom, paddingTop: 6, paddingBottom: Math.max(insets.bottom, 6) },
        tabBarIcon: ({ color, focused }) => <Icon name={TAB_ICONS[route.name]} size={focused ? 23 : 21} color={color} />,
      })}
    >
      <Tabs.Screen name="Home" component={HomeScreen} />
      <Tabs.Screen name="Learn" component={CoursesScreen} />
      <Tabs.Screen name="Bible" component={BibleScreen} />
      <Tabs.Screen name="Journal" component={JournalScreen} />
      <Tabs.Screen name="Shop" component={ShopScreen} />
    </Tabs.Navigator>
  );
}

function SignedIn() {
  return (
    <Root.Navigator screenOptions={{ headerShown: false, animation: Platform.OS === 'ios' ? 'default' : 'slide_from_right' }}>
      <Root.Screen name="Tabs" component={MainTabs} />
      <Root.Screen name="CourseDetail" component={CourseDetailScreen} />
      <Root.Screen name="Lesson" component={LessonScreen} />
      <Root.Screen name="Quiz" component={QuizScreen} />
      <Root.Screen name="BibleChapter" component={BibleChapterScreen} />
      <Root.Screen name="BibleSearch" component={BibleSearchScreen} />
      <Root.Screen name="JournalEntry" component={JournalEntryScreen} options={{ presentation: 'modal' }} />
      <Root.Screen name="PrayerWall" component={PrayerWallScreen} />
      <Root.Screen name="Product" component={ProductScreen} />
      <Root.Screen name="Cart" component={CartScreen} />
      <Root.Screen name="Profile" component={ProfileScreen} />
      <Root.Screen name="BlogArchive" component={BlogArchiveScreen} />
      <Root.Screen name="BlogPost" component={BlogPostScreen} />
      <Root.Screen name="Library" component={LibraryScreen} />
    </Root.Navigator>
  );
}

function SignedOut({ onboarded }: { onboarded: boolean }) {
  return (
    <Auth.Navigator screenOptions={{ headerShown: false }} initialRouteName={onboarded ? 'Login' : 'Onboarding'}>
      <Auth.Screen name="Onboarding" component={OnboardingScreen} />
      <Auth.Screen name="Login" component={LoginScreen} />
      <Auth.Screen name="Register" component={RegisterScreen} />
    </Auth.Navigator>
  );
}

export default function RootNavigator() {
  const { user, loading } = useAuth();
  const { c, isDark } = useTheme();
  const [onboarded, setOnboarded] = useState<boolean | null>(null);

  useEffect(() => {
    AsyncStorage.getItem('pj_onboarded').then((v) => setOnboarded(v === 'true'));
  }, []);

  if (loading || onboarded === null) {
    // Branded splash while the session is restored.
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: c.bg }}>
        <Logo size={96} />
      </View>
    );
  }

  const base = isDark ? DarkTheme : DefaultTheme;
  return (
    <NavigationContainer theme={{ ...base, colors: { ...base.colors, background: c.bg, card: c.surface, border: c.border, primary: c.brand, text: c.text } }}>
      {user ? <SignedIn /> : <SignedOut onboarded={onboarded} />}
    </NavigationContainer>
  );
}
