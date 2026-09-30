import 'package:flutter/foundation.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';

import '../core/api/api_client.dart';
import '../core/constants/api_endpoints.dart';
import '../core/constants/env.dart';
import '../core/constants/storage_keys.dart';
import '../core/storage/secure_store.dart';

class OneSignalService {
  OneSignalService(this._api, this._store);

  final ApiClient _api;
  final SecureStore _store;
  bool _initialized = false;

  Future<void> init() async {
    if (_initialized || Env.onesignalAppId.isEmpty) return;
    OneSignal.initialize(Env.onesignalAppId);
    _initialized = true;
  }

  Future<void> requestPermissionAfterLogin() async {
    if (!_initialized) return;
    await OneSignal.Notifications.requestPermission(true);
  }

  Future<void> login(String externalId) async {
    if (!_initialized) return;
    await OneSignal.login(externalId);
    final subId = OneSignal.User.pushSubscription.id;
    if (subId != null && subId.isNotEmpty) {
      await _store.write(StorageKeys.onesignalSubscriptionId, subId);
      try {
        await _api.post<void>(
          ApiEndpoints.onesignalSubscription,
          body: {
            'subscription_id': subId,
            'platform': _platform,
          },
        );
      } catch (_) {}
    }
  }

  Future<void> logout() async {
    if (!_initialized) return;
    await OneSignal.logout();
    await _store.delete(StorageKeys.onesignalSubscriptionId);
  }

  String get _platform {
    if (kIsWeb) return 'web';
    return switch (defaultTargetPlatform) {
      TargetPlatform.android => 'android',
      TargetPlatform.iOS => 'ios',
      _ => 'flutter',
    };
  }
}
