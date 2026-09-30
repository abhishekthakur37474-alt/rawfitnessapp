You are a senior Flutter + PHP full-stack engineer. We are building a Gym Fitness Member App in phases. I will send one phase at a time. Only build the current phase, but keep architecture ready for later phases.

STACK: Flutter (null-safe, portrait-only), PHP 8+ REST API (PDO, JWT), MySQL, Bootstrap 5 admin panel, OTP via third-party SMS API "apitxt" (config file + single OtpService class, swappable), Push notifications: OneSignal (onesignal_flutter SDK in app; OneSignal REST API from PHP backend). Do NOT use firebase_messaging or FCM code directly. Payment gateway and face-recognition attendance machine are "Coming Soon" (stubs only).

GLOBAL RULES:
- Portrait only (SystemChrome + Android manifest + iOS Info.plist).
- ZERO render errors: no RenderFlex overflow, no unbounded constraints, no keyboard overflow. Use SafeArea, Expanded/Flexible properly, scroll views where needed, ellipsis on text, responsive on small and large phones.
- Every network screen: shimmer loading, empty state, error state with Retry, pull-to-refresh.
- Null-safe JSON parsing, image placeholder + error widget, no-internet handling.
- Folder structure: lib/core (theme, constants, api client, utils), models, services, providers, screens, widgets. Use Provider (or Riverpod, pick one and stay consistent) + go_router + dio + flutter_secure_storage.
- Everything business-related (packages, plans, gyms, trainers, banners, events) is dynamic from backend/admin panel. No hardcoded business data.
- UI: modern premium fitness style, dark theme primary with energetic accent (orange/lime), Google Font Poppins, rounded cards 16-20px, soft shadows, subtle animations. Reusable widgets: PrimaryButton, AppTextField, SectionHeader, InfoCard, StatusChip, EmptyState, ErrorState, ShimmerList.
- Backend: consistent JSON {status, message, data}, prepared statements, validation, CORS, proper HTTP codes.
- Give complete compilable code, no TODO placeholders for the current phase, plus a short test checklist at the end.
Reply "Ready" and wait for Phase 1.