import 'package:go_router/go_router.dart';

import '../../providers/auth_provider.dart';
import '../../screens/auth/login_screen.dart';
import '../../screens/auth/onboarding_screen.dart';
import '../../screens/auth/otp_screen.dart';
import '../../screens/home/home_placeholder_screen.dart';
import '../../screens/membership/membership_placeholder_screen.dart';
import '../../screens/profile/profile_screen.dart';
import '../../screens/shell/main_shell.dart';
import '../../screens/splash/splash_screen.dart';
import '../../screens/workouts/workouts_placeholder_screen.dart';

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
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return MainShell(navigationShell: navigationShell);
        },
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/home',
                builder: (_, _) => const HomePlaceholderScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/workouts',
                builder: (_, _) => const WorkoutsPlaceholderScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/membership',
                builder: (_, _) => const MembershipPlaceholderScreen(),
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
