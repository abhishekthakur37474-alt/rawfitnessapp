import 'package:flutter/foundation.dart';

import '../models/membership.dart';
import '../models/package.dart';
import '../models/payment.dart';
import '../services/membership_service.dart';

class MembershipProvider extends ChangeNotifier {
  MembershipProvider(this._service);

  final MembershipService _service;

  Membership? current;
  bool hasActive = false;
  MembershipRequest? pendingRequest;
  List<GymPackage> packages = [];
  List<Membership> history = [];
  List<PaymentRecord> payments = [];

  bool loadingCurrent = false;
  bool loadingPackages = false;
  bool loadingHistory = false;
  bool loadingPayments = false;
  bool submitting = false;

  String? currentError;
  String? packagesError;
  String? historyError;
  String? paymentsError;
  String? actionError;

  bool popupShown = false;

  MembershipService get service => _service;

  bool get hasMembership => current != null;
  bool get hasDue => (current?.dueAmount ?? 0) > 0;
  bool get hasPendingRequest => pendingRequest != null;

  Future<void> refreshCurrent() async {
    loadingCurrent = true;
    currentError = null;
    notifyListeners();
    try {
      final snap = await _service.current();
      current = snap.membership;
      hasActive = snap.hasActive;
      pendingRequest = snap.pendingRequest;
    } catch (e) {
      currentError = _clean(e);
    } finally {
      loadingCurrent = false;
      notifyListeners();
    }
  }

  Future<void> loadPackages({bool force = false}) async {
    if (packages.isNotEmpty && !force) return;
    loadingPackages = true;
    packagesError = null;
    notifyListeners();
    try {
      packages = await _service.packages();
    } catch (e) {
      packagesError = _clean(e);
    } finally {
      loadingPackages = false;
      notifyListeners();
    }
  }

  Future<void> loadHistory() async {
    loadingHistory = true;
    historyError = null;
    notifyListeners();
    try {
      history = await _service.history();
    } catch (e) {
      historyError = _clean(e);
    } finally {
      loadingHistory = false;
      notifyListeners();
    }
  }

  Future<void> loadPayments() async {
    loadingPayments = true;
    paymentsError = null;
    notifyListeners();
    try {
      payments = await _service.payments();
    } catch (e) {
      paymentsError = _clean(e);
    } finally {
      loadingPayments = false;
      notifyListeners();
    }
  }

  Future<bool> submitRenewal(int packageId) async {
    submitting = true;
    actionError = null;
    notifyListeners();
    try {
      await _service.renew(packageId);
      await refreshCurrent();
      return true;
    } catch (e) {
      actionError = _clean(e);
      return false;
    } finally {
      submitting = false;
      notifyListeners();
    }
  }

  Future<Receipt> fetchReceipt(int id) => _service.receipt(id);

  Future<String> initiatePayment(String method, num amount) =>
      _service.initiate(method: method, amount: amount);

  void markPopupShown() {
    if (popupShown) return;
    popupShown = true;
    notifyListeners();
  }

  void reset() {
    current = null;
    hasActive = false;
    pendingRequest = null;
    packages = [];
    history = [];
    payments = [];
    currentError = null;
    packagesError = null;
    historyError = null;
    paymentsError = null;
    actionError = null;
    popupShown = false;
    notifyListeners();
  }

  String _clean(Object e) => e.toString().replaceFirst('Exception: ', '');
}
