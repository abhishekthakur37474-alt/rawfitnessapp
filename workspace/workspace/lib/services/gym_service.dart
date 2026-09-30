import '../core/api/api_client.dart';
import '../core/constants/api_endpoints.dart';
import '../models/gym.dart';

class GymService {
  GymService(this._api);

  final ApiClient _api;

  Map<String, dynamic> _branchQuery(int? branchId) => {
        if (branchId != null) 'branch_id': branchId,
      };

  Future<List<GymBranch>> branches() async {
    final res = await _api.get<List<GymBranch>>(
      ApiEndpoints.branches,
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        return ((map['branches'] as List?) ?? const [])
            .map((e) => GymBranch.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load branches' : res.message);
    }
    return res.data!;
  }

  Future<GymBranch> branch(int id) async {
    final res = await _api.get<GymBranch>(
      ApiEndpoints.branch(id),
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        final row = map['branch'];
        return GymBranch.fromJson(
          row is Map ? row.cast<String, dynamic>() : map,
        );
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load branch' : res.message);
    }
    return res.data!;
  }

  Future<List<Trainer>> trainers({int? branchId}) async {
    final res = await _api.get<List<Trainer>>(
      ApiEndpoints.trainers,
      query: _branchQuery(branchId),
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        return ((map['trainers'] as List?) ?? const [])
            .map((e) => Trainer.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load trainers' : res.message);
    }
    return res.data!;
  }

  Future<Trainer> trainer(int id) async {
    final res = await _api.get<Trainer>(
      ApiEndpoints.trainer(id),
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        final row = map['trainer'];
        return Trainer.fromJson(
          row is Map ? row.cast<String, dynamic>() : map,
        );
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load trainer' : res.message);
    }
    return res.data!;
  }

  Future<List<GymEvent>> events({int? branchId}) async {
    final res = await _api.get<List<GymEvent>>(
      ApiEndpoints.events,
      query: _branchQuery(branchId),
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        return ((map['events'] as List?) ?? const [])
            .map((e) => GymEvent.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load events' : res.message);
    }
    return res.data!;
  }

  Future<List<GymAnnouncement>> announcements({int? branchId}) async {
    final res = await _api.get<List<GymAnnouncement>>(
      ApiEndpoints.announcements,
      query: _branchQuery(branchId),
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        return ((map['announcements'] as List?) ?? const [])
            .map((e) => GymAnnouncement.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(
        res.message.isEmpty ? 'Unable to load announcements' : res.message,
      );
    }
    return res.data!;
  }

  Future<List<GymBanner>> banners({int? branchId}) async {
    final res = await _api.get<List<GymBanner>>(
      ApiEndpoints.banners,
      query: _branchQuery(branchId),
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        return ((map['banners'] as List?) ?? const [])
            .map((e) => GymBanner.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load banners' : res.message);
    }
    return res.data!;
  }

  Future<List<AttendanceRecord>> attendance({
    int? branchId,
    String? month,
  }) async {
    final res = await _api.get<List<AttendanceRecord>>(
      ApiEndpoints.attendance,
      query: {
        ..._branchQuery(branchId),
        if (month != null && month.isNotEmpty) 'month': month,
      },
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        return ((map['attendance'] as List?) ?? const [])
            .map((e) => AttendanceRecord.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(
        res.message.isEmpty ? 'Unable to load attendance' : res.message,
      );
    }
    return res.data!;
  }
}
