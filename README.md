# Raw Fitness

Gym member app: Flutter (portrait-only) + PHP 8 REST API + MySQL + Bootstrap 5 admin.

Push: OneSignal only (no FCM). Payments and face attendance are stubs.

## Layout

```
lib/          Flutter app (core, models, services, providers, screens, widgets)
backend/     PHP API (PDO, JWT, OtpService, OneSignalService)
admin/        Bootstrap 5 admin panel
plans/        Phase briefs
```

## Live host

API base: `https://demoabhishek.ifree.page/api`

Admin: `https://demoabhishek.ifree.page/admin`

Upload the project to the InfinityFree `htdocs` root so `.htaccess` and `index.php` can route `/api` and `/admin`.

MySQL is configured in `backend/config/config.php`. Tables are created automatically on first API or admin request.

Default admin login (change after first login):

```
admin@rawfitness.local
Admin@123
```

Until apitxt is configured, `POST /api/auth/send-otp` returns `data.dev_otp` so you can verify login.

## Flutter

```
flutter pub get
flutter run --dart-define=ONESIGNAL_APP_ID=your-app-id
```

API defaults to `https://demoabhishek.ifree.page/api`. Override with `--dart-define=API_BASE_URL=...`.

Notification permission is requested after login, not on splash. After OTP verify the app calls `OneSignal.login(member_id)` and saves the subscription id. Logout calls `OneSignal.logout()`.

## Phase 1 test checklist

- Portrait only on Android and iOS; keyboard does not overflow login/OTP/onboarding/profile
- Splash with no token goes to Login; with token and `is_onboarded=false` goes to Onboarding; else Home
- Login: 10-digit Indian mobile, +91 prefix, invalid number blocked
- OTP: 6 boxes, 30s resend, auto-verify on 6th digit, rate limit 5/hour, expiry 5 min
- New user gets Member ID `GYM000123` style
- Onboarding: name, gender, height cm, one gov ID type with format checks, camera/gallery preview, jpg/png max 5MB
- After submit, gov ID status is Pending and Home is reachable
- Profile: view/edit name, gender, height, member ID, gov ID status; logout clears session
- `GET /api/health` returns phase 1
- Admin login works with CSRF; dashboard shows member count
