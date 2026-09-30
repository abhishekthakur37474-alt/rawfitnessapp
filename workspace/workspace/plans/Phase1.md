PHASE 1: Foundation + Auth + Onboarding

Build:
1. Folder structure (Flutter, backend/api, admin) and setup instructions.
2. MySQL schema + seed for: users, otp_requests, user_gov_ids, admin_users, device_tokens, settings.
3. Backend: JWT middleware, config file, OtpService (apitxt, configurable), endpoints:
   - POST auth/send-otp (rate limit 5/hour per number, OTP expiry 5 min, hashed OTP)
   - POST auth/verify-otp (creates user if new, returns JWT + is_onboarded flag)
   - GET/PUT profile
   - POST profile/onboarding (name, gender, height_cm)
   - POST profile/gov-id (type: aadhaar/pan/passport/driving_license, number, image upload jpg/png max 5MB, random filename) and GET profile/gov-id (status pending/verified/rejected + reason)
   - POST device/- POST device/onesignal-subscription (saves OneSignal subscription id for the logged-in user; table onesignal_subscriptions: user_id, subscription_id, platform, updated_at) — replaces device_tokens/FCM token.
- settings table must hold: onesignal_app_id, onesignal_rest_api_key.
Flutter: initialize OneSignal in main.dart (app id from a constants file), ask notification permission AFTER login (not on splash), and after successful OTP verify call OneSignal.login(<user_id or member_id>) so the user is targetable by external_id. On logout call OneSignal.logout(). Also save subscription id to backend via the endpoint above.
   - Auto-generate Member ID like GYM000123.
4. Flutter:
   - Theme (dark + accent), reusable widgets, API client with token interceptor, secure storage.
   - Splash -> routes: no token -> Login, token but not onboarded -> Onboarding, else -> Home (placeholder screen with bottom nav: Home, Workouts, Membership, Profile).
   - Login screen (mobile number, +91, validation), OTP screen (6 digits, 30s resend timer, auto-verify).
   - Onboarding: Full Name, Gender (Male/Female/Other), Height (cm), Government ID (select ONE: Aadhaar, PAN, Passport, Driving License; ID number with per-type format validation; upload photo via camera/gallery with preview). Submit -> status Pending -> Home.
   - Profile screen basic (view/edit name, gender, height, member ID, gov ID status).
   - Logout.
5. Portrait lock + no overflow on all screens (test with keyboard open).
End with test checklist.