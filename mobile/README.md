# Prayer Journal — Mobile App

React Native (Expo) client for the Prayer Journal platform, consuming the same backend as the web app via `/api/v1`.

## Setup

```bash
npm install
```

Before running, point the app at your backend in `src/api/client.ts`:

```ts
export const API_BASE_URL = 'http://10.0.2.2:8080/api/v1'; // Android emulator -> host localhost
```

- **Android emulator**: use `10.0.2.2` in place of `localhost`.
- **iOS simulator**: `localhost` works directly.
- **Physical device**: use your computer's LAN IP (e.g. `http://192.168.1.20:8080/api/v1`) and make sure the PHP dev server is bound to `0.0.0.0`, not just `localhost` (`php spark serve --host 0.0.0.0`).
- **Live subdomain**: once deployed, just use `https://app.yourdomain.com/api/v1`.

## Run

```bash
npx expo start
```

Scan the QR code with Expo Go, or press `a` / `i` for an Android/iOS emulator.

## What's built

- Onboarding carousel (first launch only, remembered via AsyncStorage)
- Auth (register/login/logout) against the Shield token API, token persisted in AsyncStorage
- Home dashboard with quick links
- Courses: list, detail (with enroll), lesson viewer with mark-complete
- Bible: book grid, chapter reader with prev/next, bookmark on long-press
- Shop: product list, detail with add-to-cart
- Profile: user info, Bible reading streak, logout

## Not yet built

- Quiz-taking and assignment submission screens (the API endpoints exist — `POST /study/:slug/quiz/:id/submit` and `POST /study/:slug/assignments/:id/submit` — just no UI yet)
- Cart/checkout screens (adding to cart works; there's no in-app checkout — payment flows are web-only for now)
- Push notifications
- Custom Playfair Display font (currently using the system font for headings)
