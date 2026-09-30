import 'package:flutter/foundation.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';

import '../core/api/api_client.dart';
import '../core/constants/api_endpoints.dart';
import '../core/constants/env.dart';
import '../core/constants/storage_keys.dart';
import '../core/storage/secure_store.dart';

typedef NotificationClickHandler = void Function(Map<String, dynamic> data);

class OneSignalService {
  OneSignalService(this._api, this._store);

  final ApiClient _api;
  final SecureStore _store;
  bool _initialized = false;
  NotificationClickHandler? onClick;
  Map<String, dynamic>? pendingClick;

  Future<void> init() async {
    if (_initialized || Env.onesignalAppId.isEmpty) return;
    OneSignal.initialize(Env.onesignalAppId);
    OneSignal.Notifications.addForegroundWillDisplayListener((event) {
      event.notification.display();
    });
    OneSignal.Notifications.addClickListener((event) {
      final data = <String, dynamic>{};
      final additional = event.notification.additionalData;
      if (additional != null) {
        additional.forEach((key, value) => data['$key'] = value);
      }
      if (data.isEmpty) {
        data['type'] = 'general';
      }
      if (onClick != null) {
        onClick!(data);
      } else {
        pendingClick = data;
      }
    });
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

  Future<void> setTags({String? branchId, String? membershipStatus}) async {
    if (!_initialized) return;
    final tags = <String, String>{};
    if (branchId != null) tags['branch_id'] = branchId;
    if (membershipStatus != null) tags['membership_status'] = membershipStatus;
    if (tags.isEmpty) return;
    await OneSignal.User.addTags(tags);
  }

  Future<void> logout() async {
    if (!_initialized) return;
    try {
      await OneSignal.User.removeTags(['branch_id', 'membership_status']);
    } catch (_) {}
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
