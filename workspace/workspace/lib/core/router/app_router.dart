import 'package:go_router/go_router.dart';

import '../../models/package.dart';
import '../../providers/auth_provider.dart';
import '../../screens/auth/login_screen.dart';
import '../../screens/auth/onboarding_screen.dart';
import '../../screens/auth/otp_screen.dart';
import '../../screens/home/home_screen.dart';
import '../../screens/membership/membership_plans_screen.dart';
import '../../screens/membership/membership_screen.dart';
import '../../screens/membership/payment_history_screen.dart';
import '../../screens/membership/payment_method_screen.dart';
import '../../screens/membership/plan_summary_screen.dart';
import '../../screens/membership/receipt_screen.dart';
import '../../models/gym.dart';
import '../../screens/gym/attendance_screen.dart';
import '../../screens/gym/events_screen.dart';
import '../../screens/gym/gym_details_screen.dart';
import '../../screens/gym/photo_viewer_screen.dart';
import '../../screens/gym/trainer_detail_screen.dart';
import '../../screens/gym/trainers_screen.dart';
import '../../screens/legal/legal_screen.dart';
import '../../screens/notifications/notifications_screen.dart';
import '../../screens/profile/profile_screen.dart';
import '../../screens/shell/main_shell.dart';
import '../../screens/splash/splash_screen.dart';
import '../../screens/workouts/diet_plan_detail_screen.dart';
import '../../screens/workouts/workout_plan_detail_screen.dart';
import '../../screens/workouts/workouts_screen.dart';

GoRouter createRouter(AuthProvider auth) {
  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: auth,
    redirect: (context, state) {
      final loc = state.matchedLocation;
      final onSplash = loc == '/splash';
      final onLogin = loc == '/login' || loc == '/otp';
      final onOnboarding = loc == '/onboarding';

      if (onSplash) return null;

      if (auth.status == AuthStatus.unknown) {
        return '/splash';
      }
      if (auth.status == AuthStatus.unauthenticated) {
        return onLogin ? null : '/login';
      }
      if (auth.status == AuthStatus.needsOnboarding) {
        return onOnboarding ? null : '/onboarding';
      }
      if (onLogin || onOnboarding) return '/home';
      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (_, _) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (_, _) => const LoginScreen(),
      ),
      GoRoute(
        path: '/otp',
        builder: (_, state) {
          final mobile = state.uri.queryParameters['mobile'] ??
              (state.extra as String? ?? '');
          return OtpScreen(mobile: mobile);
        },
      ),
      GoRoute(
        path: '/onboarding',
        builder: (_, _) => const OnboardingScreen(),
      ),
      GoRoute(
        path: '/membership/plans',
        builder: (_, _) => const MembershipPlansScreen(),
      ),
      GoRoute(
        path: '/membership/summary',
        builder: (_, state) => PlanSummaryScreen(
          package: state.extra is GymPackage ? state.extra as GymPackage : null,
        ),
      ),
      GoRoute(
        path: '/membership/payment',
        builder: (_, state) => PaymentMethodScreen(
          package: state.extra is GymPackage ? state.extra as GymPackage : null,
        ),
      ),
      GoRoute(
        path: '/membership/history',
        builder: (_, _) => const PaymentHistoryScreen(),
      ),
      GoRoute(
        path: '/membership/receipt/:id',
        builder: (_, state) => ReceiptScreen(
          paymentId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      GoRoute(
        path: '/workouts/:id',
        builder: (_, state) => WorkoutPlanDetailScreen(
          planId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      GoRoute(
        path: '/diets/:id',
        builder: (_, state) => DietPlanDetailScreen(
          planId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      GoRoute(
        path: '/gym',
        builder: (_, _) => const GymDetailsScreen(),
      ),
      GoRoute(
        path: '/gym/branches/:id',
        builder: (_, state) => GymDetailsScreen(
          branchId: int.tryParse(state.pathParameters['id'] ?? ''),
        ),
      ),
      GoRoute(
        path: '/gym/trainers',
        builder: (_, _) => const TrainersScreen(),
      ),
      GoRoute(
        path: '/gym/trainers/:id',
        builder: (_, state) => TrainerDetailScreen(
          trainerId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      GoRoute(
        path: '/gym/events',
        builder: (_, _) => const EventsScreen(),
      ),
      GoRoute(
        path: '/gym/events/:id',
        builder: (_, state) => EventDetailScreen(
          eventId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
          event: state.extra is GymEvent ? state.extra as GymEvent : null,
        ),
      ),
      GoRoute(
        path: '/gym/announcements/:id',
        builder: (_, state) => AnnouncementDetailScreen(
          announcementId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
          announcement: state.extra is GymAnnouncement
              ? state.extra as GymAnnouncement
              : null,
        ),
      ),
      GoRoute(
        path: '/gym/attendance',
        builder: (_, _) => const AttendanceScreen(),
      ),
      GoRoute(
        path: '/gym/photos',
        builder: (_, state) {
          final extra = state.extra;
          final map = extra is Map ? extra : const {};
          final urls = ((map['urls'] as List?) ?? const [])
              .map((e) => '$e')
              .where((e) => e.isNotEmpty)
              .toList();
          final index = map['index'] is int ? map['index'] as int : 0;
          return PhotoViewerScreen(urls: urls, initialIndex: index);
        },
      ),
      GoRoute(
        path: '/notifications',
        builder: (_, _) => const NotificationsScreen(),
      ),
      GoRoute(
        path: '/legal/terms',
        builder: (_, _) => const LegalScreen(kind: 'terms'),
      ),
      GoRoute(
        path: '/legal/privacy',
        builder: (_, _) => const LegalScreen(kind: 'privacy'),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return MainShell(navigationShell: navigationShell);
        },
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/home',
                builder: (_, _) => const HomeScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/workouts',
                builder: (_, _) => const WorkoutsScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/membership',
                builder: (_, _) => const MembershipScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/profile',
                builder: (_, _) => const ProfileScreen(),
              ),
            ],
          ),
        ],
      ),
    ],
  );
}
