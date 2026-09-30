enum MembershipStatus { active, expiring, expired, cancelled, none }

class Membership {
  const Membership({
    required this.id,
    required this.status,
    required this.startDate,
    required this.endDate,
    required this.amount,
    required this.paidAmount,
    required this.dueAmount,
    required this.daysLeft,
    this.packageId,
    this.packageName,
    this.packageDurationDays,
  });

  final int id;
  final MembershipStatus status;
  final DateTime startDate;
  final DateTime endDate;
  final double amount;
  final double paidAmount;
  final double dueAmount;
  final int daysLeft;
  final int? packageId;
  final String? packageName;
  final int? packageDurationDays;

  bool get isActive =>
      status == MembershipStatus.active || status == MembershipStatus.expiring;

  bool get hasDue => dueAmount > 0;

  String get planName =>
      (packageName == null || packageName!.isEmpty) ? 'Custom plan' : packageName!;

  String get statusLabel => switch (status) {
        MembershipStatus.active => 'Active',
        MembershipStatus.expiring => 'Expiring soon',
        MembershipStatus.expired => 'Expired',
        MembershipStatus.cancelled => 'Cancelled',
        MembershipStatus.none => 'No plan',
      };

  factory Membership.fromJson(Map<String, dynamic> json) {
    return Membership(
      id: _asInt(json['id']),
      status: parseStatus(json['status'] as String?),
      startDate: _asDate(json['start_date']) ?? DateTime.now(),
      endDate: _asDate(json['end_date']) ?? DateTime.now(),
      amount: _asDouble(json['amount']),
      paidAmount: _asDouble(json['paid_amount']),
      dueAmount: _asDouble(json['due_amount']),
      daysLeft: _asInt(json['days_left']),
      packageId: json['package_id'] == null ? null : _asInt(json['package_id']),
      packageName: json['package_name'] as String?,
      packageDurationDays: json['package_duration_days'] == null
          ? null
          : _asInt(json['package_duration_days']),
    );
  }

  static MembershipStatus parseStatus(String? raw) {
    return switch (raw) {
      'active' => MembershipStatus.active,
      'expiring' => MembershipStatus.expiring,
      'expired' => MembershipStatus.expired,
      'cancelled' => MembershipStatus.cancelled,
      _ => MembershipStatus.none,
    };
  }

  static int _asInt(dynamic v) {
    if (v is int) return v;
    return int.tryParse('$v') ?? 0;
  }

  static double _asDouble(dynamic v) {
    if (v is num) return v.toDouble();
    return double.tryParse('$v') ?? 0;
  }

  static DateTime? _asDate(dynamic v) {
    if (v == null) return null;
    return DateTime.tryParse('$v');
  }
}

class MembershipRequest {
  const MembershipRequest({
    required this.id,
    required this.amount,
    required this.status,
    this.packageId,
    this.createdAt,
  });

  final int id;
  final double amount;
  final String status;
  final int? packageId;
  final DateTime? createdAt;

  factory MembershipRequest.fromJson(Map<String, dynamic> json) {
    return MembershipRequest(
      id: Membership._asInt(json['id']),
      amount: Membership._asDouble(json['amount']),
      status: (json['status'] as String?) ?? 'pending',
      packageId: json['package_id'] == null ? null : Membership._asInt(json['package_id']),
      createdAt: Membership._asDate(json['created_at']),
    );
  }
}

class MembershipSnapshot {
  const MembershipSnapshot({
    this.membership,
    required this.hasActive,
    this.pendingRequest,
  });

  final Membership? membership;
  final bool hasActive;
  final MembershipRequest? pendingRequest;

  factory MembershipSnapshot.fromJson(Map<String, dynamic> json) {
    final raw = json['membership'];
    final pending = json['pending_request'];
    return MembershipSnapshot(
      membership: raw is Map
          ? Membership.fromJson(raw.cast<String, dynamic>())
          : null,
      hasActive: json['has_active'] == true,
      pendingRequest: pending is Map
          ? MembershipRequest.fromJson(pending.cast<String, dynamic>())
          : null,
    );
  }
}
