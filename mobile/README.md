# Prayer Journal — Mobile App

React Native (Expo SDK 57) client for the Prayer Journal platform. It uses the same backend as the website through `/api/v1`.

## Setup

```bash
npm install
```

Point the app at your backend with an environment variable (or edit the fallback in `src/api/client.ts`):

```bash
EXPO_PUBLIC_API_URL=http://192.168.1.20:8080/api/v1 npx expo start
```

- **Android emulator**: the default `http://10.0.2.2:8080/api/v1` reaches the host's `localhost`.
- **iOS simulator**: `http://localhost:8080/api/v1` works directly.
- **Physical device**: use your computer's LAN IP and run the PHP server on all interfaces (`php spark serve --host 0.0.0.0`).
- **Production**: `https://yourdomain.com/api/v1`.

## Run

```bash
npx expo start          # Expo Go / emulators
npx expo start --web    # browser preview
```

For the browser preview the API must allow the origin. Outside production, `localhost` and `127.0.0.1` on any port are allowed automatically. In production, list origins in `CORS_ALLOWED_ORIGINS` (comma-separated) in the web app's `.env`.

## Design

- **Logo on every screen.** Navigator headers are hidden. Every screen renders `AppHeader` (in `src/components/ui.tsx`), which shows the logo mark, title and actions.
- **Icons.** Only Feather line icons (`src/components/Icon.tsx`), the same family as the website's Lucide sprite. There are no emoji anywhere in the UI.
- **Themes.** Light, dark or follow the system. Reading size has four steps. Both are set in Profile and remembered on the device. Colour tokens live in `src/theme/colors.ts` and match the website.

## Screens

| Tab / screen | What it does |
| --- | --- |
| Onboarding, Sign in, Register | Brand onboarding (shown once) and token auth against Shield |
| Home | Greeting, verse of the day (with share), streak/journal stats, continue-learning card, quick actions, today's reading-plan readings, answered-prayer nudge |
| Learn | My learning with progress, and a searchable catalogue |
| Course | Facts, progress, "what you'll learn", collapsible curriculum with drip and sequential locks, enroll |
| Lesson | Key scripture card, rich lesson content, journal prompts that autosave to the Prayer Journal, complete & continue, previous/next |
| Quiz | All question types (single, multiple, true/false, short answer), attempt limits, scored review with explanations |
| Bible | Continue reading, verse of the day, Old/New Testament book index with chapter grid |
| Chapter | Serif reader with adjustable size. Select verses to highlight (5 colours), bookmark, share or send to the journal. Previous/next moves across books. |
| Bible search | Jump straight to a reference (e.g. "Romans 8:28") or search words and quoted phrases, with matches highlighted |
| Journal | Stats, search, filters (waiting / answered / prayer / meditation / gratitude / note) and one-tap "mark answered" |
| Journal entry | Create, edit, share or delete an entry, with an optional scripture reference |
| Prayer wall | Share requests (optionally anonymous) by category, "I prayed" with a live count, and answered testimonies |
| Shop | Search, sort, sale and download badges |
| Product | Image gallery, option pickers, quantity, wishlist, reviews |
| Cart | Change quantities or remove items. Checkout opens securely on the website. |
| Profile | Theme, reading size, links to orders, certificates, notifications and account, sign out |

## Checks

```bash
npx tsc --noEmit
```
