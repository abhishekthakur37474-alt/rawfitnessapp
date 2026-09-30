PHASE 5: Notifications (OneSignal) + Expiry Reminders + Final Polish

Build:
1. MySQL: notifications (user_id, title, body, type, data_json, is_read, created_at) as in-app history log.
2. Backend: OneSignalService class (PHP cURL) using POST https://api.onesignal.com/notifications with header "Authorization: Key <REST_API_KEY>" and app_id from settings table. Support:
   - send to single user via include_aliases {"external_id": [..]} + target_channel "push"
   - send to all (Subscribed Users segment)
   - send to branch (filter by tag branch_id, or list of external_ids)
   Every send also inserts a row in notifications table. Log OneSignal response/errors.
   Endpoints: GET notifications, POST notifications/{id}/read, GET notifications/unread-count.
   Cron script (daily): push at 7, 3, 1 days before expiry and on expiry day, and update expired memberships. No duplicate reminders same day.
3. Admin: send notification page (all / single member / branch, title, body, optional image), notification log, Settings page (OneSignal App ID, REST API Key, OTP provider config, app name, contact details), dashboard extras (today's attendance, monthly revenue).
4. Flutter: onesignal_flutter handling: foreground display, click listener that routes to the right screen using additional data (type: membership / event / announcement / general), notification list screen with read/unread + badge, Terms/Privacy screens, logout cleanup (OneSignal.logout()). Set OneSignal tags on login: branch_id, membership_status.
5. Final polish: audit every screen for overflow, keyboard issues, missing loading/empty/error states, null-safety crashes, portrait lock. README: setup, OneSignal setup, API base URL, cron setup, build APK/IPA.
End with full QA checklist.