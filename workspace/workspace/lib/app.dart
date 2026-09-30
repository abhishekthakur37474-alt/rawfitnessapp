import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'core/constants/app_constants.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'core/utils/notification_router.dart';
import 'providers/auth_provider.dart';
import 'providers/notification_provider.dart';
import 'services/onesignal_service.dart';

class RawFitnessApp extends StatefulWidget {
  const RawFitnessApp({super.key});

  @override
  State<RawFitnessApp> createState() => _RawFitnessAppState();
}

class _RawFitnessAppState extends State<RawFitnessApp> {
  GoRouter? _router;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_router != null) return;
    final auth = context.read<AuthProvider>();
    final oneSignal = context.read<OneSignalService>();
    final inbox = context.read<NotificationProvider>();
    final router = createRouter(auth);
    _router = router;
    oneSignal.onClick = (data) {
      final type = '${data['type'] ?? 'general'}';
      final id = NotificationRouter.payloadId(data);
      inbox.refreshUnread();
      router.push(NotificationRouter.locationFor(type: type, id: id));
    };
    final pending = oneSignal.pendingClick;
    if (pending != null) {
      oneSignal.pendingClick = null;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        oneSignal.onClick?.call(pending);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: AppConstants.appName,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.dark,
      darkTheme: AppTheme.dark,
      themeMode: ThemeMode.dark,
      routerConfig: _router,
    );
  }
}
