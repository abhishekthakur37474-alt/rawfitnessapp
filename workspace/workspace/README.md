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

Upload the project to the InfinityFree `htdocs` root. `.htaccess` routes `/admin` to `admin/public/index.php` and `/api` to `backend/public/index.php`. Root `index.php` only redirects `/` to `/admin/`.

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

Notification permission is requested after login, not on splash. After OTP verify the app calls `OneSignal.login(member_id)` and saves the subscription id. The app also sets tags `branch_id` and `membership_status`. Logout calls `OneSignal.logout()`.

## OneSignal

1. Create an app at onesignal.com (Android + iOS). Do not use FCM code in this project.
2. Put the App ID and REST API Key in Admin → Settings (stored in the `settings` table).
3. Run Flutter with `--dart-define=ONESIGNAL_APP_ID=your-app-id`.
4. Backend sends with `Authorization: Key <REST_API_KEY>` to `https://api.onesignal.com/notifications`.
5. Single member: `include_aliases.external_id` + `target_channel: push` (external id = Member ID).
6. All members: `included_segments: ["Subscribed Users"]`.
7. Branch: OneSignal tag filter `branch_id`.

## Cron (expiry reminders)

Daily job expires past memberships and pushes at 7, 3, 1 days before expiry and on expiry day. Same reminder is not sent twice on the same day.

CLI:

```
php backend/cron/expiry-reminders.php
```

HTTP (InfinityFree cron / wget):

```
https://demoabhishek.ifree.page/api/cron/expiry-reminders?key=rawfitness-cron
```

Override the key with env `CRON_KEY` in `backend/config/config.php`.

Suggested crontab:

```
0 8 * * * php /path/to/backend/cron/expiry-reminders.php
```

## Build

```
flutter build apk --release --dart-define=ONESIGNAL_APP_ID=your-app-id --dart-define=API_BASE_URL=https://demoabhishek.ifree.page/api
flutter build ipa --release --dart-define=ONESIGNAL_APP_ID=your-app-id --dart-define=API_BASE_URL=https://demoabhishek.ifree.page/api
```

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

## Phase 2 test checklist

- `GET /api/health` returns phase 2
- Home with no active membership shows a dismissible "Get Membership" bottom sheet (close button + tap outside); dismissing leaves a persistent "Get your membership" card, and the sheet returns on next launch while there is still no active plan
- Home with an active/expiring membership shows the membership card (plan, Member ID + QR, validity, days left, due, status chip)
- Package list loads from `GET /packages`; selecting a plan opens the summary, then the payment method screen
- Payment method screen offers UPI / Card / Net Banking; Pay shows a "Coming Soon" dialog; "Request activation from gym" creates a pending `membership/renew` request
- Membership tab shows details, fees & due amount, validity/expiry, pending-request notice, and past membership history
- Payment history lists `GET /payments/history`; opening an item shows the receipt/invoice from `GET /receipts/{id}`
- Admin: Packages CRUD, members list/search/view, Government ID approve/reject with reason, assign/renew membership, record offline payment (cash/UPI/card), printable receipt
- Phase 1 flows still work (auth, onboarding, profile, portrait lock, no overflow)

## Phase 3 test checklist

- `GET /api/health` returns phase 3
- `GET /api/workout-plans` and `GET /api/diet-plans` return active plans plus categories; `?category=` filters the list
- `GET /api/workout-plans/{id}` returns days with exercises; `GET /api/diet-plans/{id}` returns meals
- Admin: Workout Plans CRUD (days + exercises, image upload, toggle, search, pagination)
- Admin: Diet Plans CRUD (meals, image upload, toggle, search, pagination)
- Flutter Workouts tab: "Workout Plans" / "Diet Plans" sub-tabs, image cards, category chips, detail screens, shimmer/empty/error
- Phase 1 and Phase 2 flows still work

## Phase 4 test checklist

- `GET /api/health` returns phase 4
- `GET /api/branches` and `GET /api/branches/{id}` (timings, facilities, parking, photos)
- `GET /api/trainers`, `/trainers/{id}`, `/events`, `/announcements`, `/banners` with optional `?branch_id=`
- `GET /api/attendance` history; `POST /api/attendance/checkin` is device-key protected
- Admin CRUD: Branches, Facilities, Trainers + certs, Events, Announcements, Banners, manual attendance
- Flutter: branch selector, gym details, photo viewer, trainers, events/announcements, home banners, attendance list + calendar
- Contact gym copies call / WhatsApp / email / maps links
- Face check-in shows "Face check-in coming soon"
- Phase 1–3 flows still work

## Phase 5 test checklist

- `GET /api/health` returns phase 5
- Admin Settings: OneSignal App ID / REST API Key, OTP provider, app name, contact
- Admin Notifications: send to all / member / branch, optional image, log of OneSignal status
- `GET /api/notifications`, `GET /api/notifications/unread-count`, `POST /api/notifications/{id}/read`
- Cron CLI or `GET /api/cron/expiry-reminders?key=...` sends 7/3/1/0-day reminders once per day and expires memberships
- Flutter home badge, notification list (read/unread), click routing for membership / event / announcement / general
- Foreground OneSignal display; login tags `branch_id` + `membership_status`; logout calls `OneSignal.logout()`
- Profile Terms and Privacy screens
- Portrait lock, shimmer/empty/error on network screens
- Phase 1–4 flows still work
