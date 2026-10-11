import React, { useEffect, useState } from 'react';
import { ActivityIndicator, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';

import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

import OnboardingScreen from '../screens/OnboardingScreen';
import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';
import HomeScreen from '../screens/HomeScreen';
import CoursesScreen from '../screens/CoursesScreen';
import CourseDetailScreen from '../screens/CourseDetailScreen';
import LessonScreen from '../screens/LessonScreen';
import BibleScreen from '../screens/BibleScreen';
import BibleChapterScreen from '../screens/BibleChapterScreen';
import ShopScreen from '../screens/ShopScreen';
import ProductScreen from '../screens/ProductScreen';
import ProfileScreen from '../screens/ProfileScreen';

const AuthStack = createNativeStackNavigator();
const CoursesStack = createNativeStackNavigator();
const BibleStack = createNativeStackNavigator();
const ShopStack = createNativeStackNavigator();
const Tabs = createBottomTabNavigator();

const screenOptions = {
  headerStyle: { backgroundColor: colors.black },
  headerTintColor: colors.white,
  headerTitleStyle: { fontWeight: '700' as const },
};

function CoursesNavigator() {
  return (
    <CoursesStack.Navigator screenOptions={screenOptions}>
      <CoursesStack.Screen name="CoursesList" component={CoursesScreen} options={{ title: 'Courses' }} />
      <CoursesStack.Screen name="CourseDetail" component={CourseDetailScreen} options={({ route }: any) => ({ title: route.params.title })} />
      <CoursesStack.Screen name="Lesson" component={LessonScreen} options={({ route }: any) => ({ title: route.params.title })} />
    </CoursesStack.Navigator>
  );
}

function BibleNavigator() {
  return (
    <BibleStack.Navigator screenOptions={screenOptions}>
      <BibleStack.Screen name="BibleBooks" component={BibleScreen} options={{ title: 'The Bible' }} />
      <BibleStack.Screen name="BibleChapter" component={BibleChapterScreen} options={{ title: '' }} />
    </BibleStack.Navigator>
  );
}

function ShopNavigator() {
  return (
    <ShopStack.Navigator screenOptions={screenOptions}>
      <ShopStack.Screen name="ShopList" component={ShopScreen} options={{ title: 'Shop' }} />
      <ShopStack.Screen name="Product" component={ProductScreen} options={({ route }: any) => ({ title: route.params.title })} />
    </ShopStack.Navigator>
  );
}

function MainTabs() {
  return (
    <Tabs.Navigator screenOptions={{ ...screenOptions, tabBarActiveTintColor: colors.purple }}>
      <Tabs.Screen name="HomeTab" component={HomeScreen} options={{ title: 'Home' }} />
      <Tabs.Screen name="CoursesTab" component={CoursesNavigator} options={{ title: 'Courses', headerShown: false }} />
      <Tabs.Screen name="BibleTab" component={BibleNavigator} options={{ title: 'Bible', headerShown: false }} />
      <Tabs.Screen name="ShopTab" component={ShopNavigator} options={{ title: 'Shop', headerShown: false }} />
      <Tabs.Screen name="ProfileTab" component={ProfileScreen} options={{ title: 'Profile' }} />
    </Tabs.Navigator>
  );
}

function AuthNavigator() {
  return (
    <AuthStack.Navigator screenOptions={{ headerShown: false }}>
      <AuthStack.Screen name="Login" component={LoginScreen} />
      <AuthStack.Screen name="Register" component={RegisterScreen} />
    </AuthStack.Navigator>
  );
}

export default function RootNavigator() {
  const { user, loading } = useAuth();
  const [onboarded, setOnboarded] = useState<boolean | null>(null);

  useEffect(() => {
    AsyncStorage.getItem('pj_onboarded').then((v) => setOnboarded(v === 'true'));
  }, []);

  if (loading || onboarded === null) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.black }}>
        <ActivityIndicator size="large" color={colors.gold} />
      </View>
    );
  }

  if (! onboarded) {
    return <OnboardingScreen onDone={() => setOnboarded(true)} />;
  }

  return (
    <NavigationContainer>
      {user ? <MainTabs /> : <AuthNavigator />}
    </NavigationContainer>
  );
}
