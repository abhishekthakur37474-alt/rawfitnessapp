import 'package:flutter/foundation.dart';

import '../core/constants/storage_keys.dart';
import '../core/storage/secure_store.dart';
import '../models/gym.dart';
import '../services/gym_service.dart';

class GymProvider extends ChangeNotifier {
  GymProvider(this._service, this._store);

  final GymService _service;
  final SecureStore _store;

  List<GymBranch> branches = [];
  GymBranch? selectedBranch;
  GymBranch? branchDetail;

  List<Trainer> trainers = [];
  List<GymEvent> events = [];
  List<GymAnnouncement> announcements = [];
  List<GymBanner> banners = [];
  List<AttendanceRecord> attendance = [];

  DateTime attendanceMonth = DateTime(DateTime.now().year, DateTime.now().month);

  bool loadingBranches = false;
  bool loadingFeed = false;
  bool loadingAttendance = false;
  bool loadingDetail = false;

  String? branchesError;
  String? feedError;
  String? attendanceError;
  String? detailError;

  GymService get service => _service;

  int? get selectedBranchId => selectedBranch?.id;

  String get selectedBranchLabel => selectedBranch?.name ?? 'All branches';

  Set<DateTime> get attendanceDays {
    return {
      for (final row in attendance)
        DateTime(row.checkIn.year, row.checkIn.month, row.checkIn.day),
    };
  }

  Future<void> bootstrap() async {
    await loadBranches();
  }

  Future<void> loadBranches({bool force = false}) async {
    if (branches.isNotEmpty && !force) {
      await loadFeed();
      return;
    }
    loadingBranches = true;
    branchesError = null;
    notifyListeners();
    try {
      branches = await _service.branches();
      final saved = await _store.read(StorageKeys.selectedBranchId);
      final savedId = int.tryParse(saved ?? '');
      if (savedId != null) {
        for (final b in branches) {
          if (b.id == savedId) {
            selectedBranch = b;
            break;
          }
        }
      }
      selectedBranch ??= branches.length == 1 ? branches.first : null;
    } catch (e) {
      branchesError = _clean(e);
    } finally {
      loadingBranches = false;
      notifyListeners();
    }
    await loadFeed(force: true);
  }

  Future<void> selectBranch(GymBranch? branch) async {
    if (selectedBranch?.id == branch?.id) return;
    selectedBranch = branch;
    if (branch == null) {
      await _store.delete(StorageKeys.selectedBranchId);
    } else {
      await _store.write(StorageKeys.selectedBranchId, '${branch.id}');
    }
    notifyListeners();
    await loadFeed(force: true);
  }

  String get branchTag => selectedBranchId?.toString() ?? 'all';

  Future<void> loadFeed({bool force = false}) async {
    if (!force &&
        (banners.isNotEmpty || events.isNotEmpty || announcements.isNotEmpty || trainers.isNotEmpty)) {
      return;
    }
    loadingFeed = true;
    feedError = null;
    notifyListeners();
    try {
      final id = selectedBranchId;
      final results = await Future.wait([
        _service.banners(branchId: id),
        _service.events(branchId: id),
        _service.announcements(branchId: id),
        _service.trainers(branchId: id),
      ]);
      banners = results[0] as List<GymBanner>;
      events = results[1] as List<GymEvent>;
      announcements = results[2] as List<GymAnnouncement>;
      trainers = results[3] as List<Trainer>;
    } catch (e) {
      feedError = _clean(e);
    } finally {
      loadingFeed = false;
      notifyListeners();
    }
  }

  Future<GymBranch?> loadBranchDetail(int id, {bool force = false}) async {
    if (!force && branchDetail?.id == id && branchDetail?.timings.isNotEmpty == true) {
      return branchDetail;
    }
    loadingDetail = true;
    detailError = null;
    notifyListeners();
    try {
      branchDetail = await _service.branch(id);
      return branchDetail;
    } catch (e) {
      detailError = _clean(e);
      return null;
    } finally {
      loadingDetail = false;
      notifyListeners();
    }
  }

  Future<void> loadAttendance({bool force = false}) async {
    if (attendance.isNotEmpty && !force) return;
    loadingAttendance = true;
    attendanceError = null;
    notifyListeners();
    try {
      final month =
          '${attendanceMonth.year.toString().padLeft(4, '0')}-${attendanceMonth.month.toString().padLeft(2, '0')}';
      attendance = await _service.attendance(
        branchId: selectedBranchId,
        month: month,
      );
    } catch (e) {
      attendanceError = _clean(e);
    } finally {
      loadingAttendance = false;
      notifyListeners();
    }
  }

  Future<void> setAttendanceMonth(DateTime month) async {
    final next = DateTime(month.year, month.month);
    if (attendanceMonth.year == next.year && attendanceMonth.month == next.month) {
      return;
    }
    attendanceMonth = next;
    notifyListeners();
    await loadAttendance(force: true);
  }

  void reset() {
    branches = [];
    selectedBranch = null;
    branchDetail = null;
    trainers = [];
    events = [];
    announcements = [];
    banners = [];
    attendance = [];
    attendanceMonth = DateTime(DateTime.now().year, DateTime.now().month);
    branchesError = null;
    feedError = null;
    attendanceError = null;
    detailError = null;
    notifyListeners();
  }

  String _clean(Object e) => e.toString().replaceFirst('Exception: ', '');
}
