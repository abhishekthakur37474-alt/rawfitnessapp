import '../core/api/api_client.dart';
import '../core/constants/api_endpoints.dart';
import '../models/membership.dart';
import '../models/package.dart';
import '../models/payment.dart';

class MembershipService {
  MembershipService(this._api);

  final ApiClient _api;

  Future<List<GymPackage>> packages() async {
    final res = await _api.get<List<GymPackage>>(
      ApiEndpoints.packages,
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        final list = (map['packages'] as List?) ?? const [];
        return list
            .map((e) => GymPackage.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status) throw Exception(res.message);
    return res.data ?? const [];
  }

  Future<MembershipSnapshot> current() async {
    final res = await _api.get<MembershipSnapshot>(
      ApiEndpoints.membershipCurrent,
      parse: (raw) =>
          MembershipSnapshot.fromJson((raw as Map).cast<String, dynamic>()),
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load membership' : res.message);
    }
    return res.data!;
  }

  Future<List<Membership>> history() async {
    final res = await _api.get<List<Membership>>(
      ApiEndpoints.membershipHistory,
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        final list = (map['memberships'] as List?) ?? const [];
        return list
            .map((e) => Membership.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status) throw Exception(res.message);
    return res.data ?? const [];
  }

  Future<void> renew(int packageId, {String? note}) async {
    final res = await _api.post<void>(
      ApiEndpoints.membershipRenew,
      body: {
        'package_id': packageId,
        if (note != null && note.trim().isNotEmpty) 'note': note.trim(),
      },
    );
    if (!res.status) throw Exception(res.message);
  }

  Future<List<PaymentRecord>> payments() async {
    final res = await _api.get<List<PaymentRecord>>(
      ApiEndpoints.paymentsHistory,
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        final list = (map['payments'] as List?) ?? const [];
        return list
            .map((e) => PaymentRecord.fromJson((e as Map).cast<String, dynamic>()))
            .toList();
      },
    );
    if (!res.status) throw Exception(res.message);
    return res.data ?? const [];
  }

  Future<Receipt> receipt(int id) async {
    final res = await _api.get<Receipt>(
      ApiEndpoints.receipt(id),
      parse: (raw) => Receipt.fromJson((raw as Map).cast<String, dynamic>()),
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load receipt' : res.message);
    }
    return res.data!;
  }

  Future<String> initiate({required String method, required num amount}) async {
    final res = await _api.post<Map<String, dynamic>>(
      ApiEndpoints.paymentsInitiate,
      body: {'method': method, 'amount': amount},
      parse: (raw) => (raw as Map).cast<String, dynamic>(),
    );
    if (!res.status) throw Exception(res.message);
    return res.message.isEmpty ? 'Online payments are coming soon.' : res.message;
  }
}
