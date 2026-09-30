import 'dart:convert';

import 'package:flutter/foundation.dart';

import '../core/constants/storage_keys.dart';
import '../core/storage/secure_store.dart';
import '../models/otp_send_result.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/onesignal_service.dart';

enum AuthStatus { unknown, unauthenticated, needsOnboarding, authenticated }

class AuthProvider extends ChangeNotifier {
  AuthProvider({
    required AuthService authService,
    required SecureStore store,
    required OneSignalService oneSignal,
  })  : _auth = authService,
        _store = store,
        _oneSignal = oneSignal;

  final AuthService _auth;
  final SecureStore _store;
  final OneSignalService _oneSignal;

  AuthStatus status = AuthStatus.unknown;
  User? user;
  String? error;
  String? lastDevOtp;
  bool busy = false;

  AuthService get authService => _auth;

  Future<void> bootstrap() async {
    final token = await _store.read(StorageKeys.accessToken);
    if (token == null || token.isEmpty) {
      status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }
    try {
      user = await _auth.getProfile();
      await _persistUser(user!);
      status = user!.isOnboarded
          ? AuthStatus.authenticated
          : AuthStatus.needsOnboarding;
    } catch (_) {
      await _store.clear();
      user = null;
      status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  Future<bool> sendOtp(String mobile) async {
    busy = true;
    error = null;
    lastDevOtp = null;
    notifyListeners();
    try {
      final result = await _auth.sendOtp(mobile);
      lastDevOtp = result.devOtp;
      return true;
    } catch (e) {
      error = _clean(e);
      return false;
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<OtpSendResult?> peekLastOtp() async =>
      lastDevOtp == null ? null : OtpSendResult(devOtp: lastDevOtp);

  Future<bool> verifyOtp(String mobile, String otp) async {
    busy = true;
    error = null;
    notifyListeners();
    try {
      final session = await _auth.verifyOtp(mobile, otp);
      await _store.write(StorageKeys.accessToken, session.token);
      user = session.user;
      await _persistUser(session.user);
      status = session.user.isOnboarded
          ? AuthStatus.authenticated
          : AuthStatus.needsOnboarding;
      try {
        await _oneSignal.requestPermissionAfterLogin();
        final externalId = session.user.memberId.isNotEmpty
            ? session.user.memberId
            : '${session.user.id}';
        await _oneSignal.login(externalId);
      } catch (_) {}
      return true;
    } catch (e) {
      error = _clean(e);
      return false;
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<bool> completeOnboarding(User updated) async {
    await setUser(updated);
    return true;
  }

  Future<void> setUser(User updated) async {
    user = updated;
    status = updated.isOnboarded
        ? AuthStatus.authenticated
        : AuthStatus.needsOnboarding;
    await _persistUser(updated);
    notifyListeners();
  }

  Future<void> refreshProfile() async {
    try {
      user = await _auth.getProfile();
      await _persistUser(user!);
      status = user!.isOnboarded
          ? AuthStatus.authenticated
          : AuthStatus.needsOnboarding;
      notifyListeners();
    } catch (e) {
      error = _clean(e);
      notifyListeners();
    }
  }

  Future<void> logout() async {
    try {
      await _oneSignal.logout();
    } catch (_) {}
    await _store.clear();
    user = null;
    lastDevOtp = null;
    status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  Future<void> _persistUser(User value) {
    return _store.write(StorageKeys.userJson, jsonEncode(value.toJson()));
  }

  String _clean(Object e) => e.toString().replaceFirst('Exception: ', '');
}
