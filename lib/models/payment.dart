class PaymentRecord {
  const PaymentRecord({
    required this.id,
    required this.amount,
    required this.mode,
    required this.status,
    this.membershipId,
    this.txnRef,
    this.receiptNo,
    this.packageName,
    this.startDate,
    this.endDate,
    this.createdAt,
  });

  final int id;
  final double amount;
  final String mode;
  final String status;
  final int? membershipId;
  final String? txnRef;
  final String? receiptNo;
  final String? packageName;
  final DateTime? startDate;
  final DateTime? endDate;
  final DateTime? createdAt;

  bool get isSuccess => status == 'success';

  factory PaymentRecord.fromJson(Map<String, dynamic> json) {
    return PaymentRecord(
      id: _asInt(json['id']),
      amount: _asDouble(json['amount']),
      mode: (json['mode'] as String?) ?? 'cash',
      status: (json['status'] as String?) ?? 'success',
      membershipId: json['membership_id'] == null ? null : _asInt(json['membership_id']),
      txnRef: json['txn_ref'] as String?,
      receiptNo: json['receipt_no'] as String?,
      packageName: json['package_name'] as String?,
      startDate: _asDate(json['start_date']),
      endDate: _asDate(json['end_date']),
      createdAt: _asDate(json['created_at']),
    );
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

class Receipt {
  const Receipt({
    required this.payment,
    this.memberId,
    this.memberName,
    this.memberMobile,
    this.gymName = 'Raw Fitness',
  });

  final PaymentRecord payment;
  final String? memberId;
  final String? memberName;
  final String? memberMobile;
  final String gymName;

  factory Receipt.fromJson(Map<String, dynamic> json) {
    final member = json['member'];
    final memberMap = member is Map ? member.cast<String, dynamic>() : const <String, dynamic>{};
    return Receipt(
      payment: PaymentRecord.fromJson(json),
      memberId: memberMap['member_id'] as String?,
      memberName: memberMap['name'] as String?,
      memberMobile: memberMap['mobile'] as String?,
      gymName: (json['gym_name'] as String?) ?? 'Raw Fitness',
    );
  }
}
