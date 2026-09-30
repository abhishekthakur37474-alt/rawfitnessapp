import 'gov_id.dart';

class User {
  const User({
    required this.id,
    required this.memberId,
    required this.mobile,
    this.name,
    this.gender,
    this.heightCm,
    this.isOnboarded = false,
    this.govIdStatus,
    this.govId,
  });

  final int id;
  final String memberId;
  final String mobile;
  final String? name;
  final String? gender;
  final double? heightCm;
  final bool isOnboarded;
  final String? govIdStatus;
  final GovId? govId;

  factory User.fromJson(Map<String, dynamic> json) {
    final govRaw = json['gov_id'];
    return User(
      id: _asInt(json['id']),
      memberId: (json['member_id'] as String?) ?? '',
      mobile: (json['mobile'] as String?) ?? '',
      name: json['name'] as String?,
      gender: json['gender'] as String?,
      heightCm: json['height_cm'] == null
          ? null
          : double.tryParse(json['height_cm'].toString()),
      isOnboarded: json['is_onboarded'] == true || json['is_onboarded'] == 1,
      govIdStatus: json['gov_id_status'] as String?,
      govId: govRaw is Map
          ? GovId.fromJson(govRaw.cast<String, dynamic>())
          : null,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'member_id': memberId,
        'mobile': mobile,
        'name': name,
        'gender': gender,
        'height_cm': heightCm,
        'is_onboarded': isOnboarded,
        'gov_id_status': govIdStatus,
        'gov_id': govId?.toJson(),
      };

  User copyWith({
    String? name,
    String? gender,
    double? heightCm,
    bool? isOnboarded,
    String? govIdStatus,
    GovId? govId,
  }) {
    return User(
      id: id,
      memberId: memberId,
      mobile: mobile,
      name: name ?? this.name,
      gender: gender ?? this.gender,
      heightCm: heightCm ?? this.heightCm,
      isOnboarded: isOnboarded ?? this.isOnboarded,
      govIdStatus: govIdStatus ?? this.govIdStatus,
      govId: govId ?? this.govId,
    );
  }

  static int _asInt(dynamic v) {
    if (v is int) return v;
    return int.tryParse('$v') ?? 0;
  }
}
