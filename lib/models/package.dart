class GymPackage {
  const GymPackage({
    required this.id,
    required this.name,
    required this.durationDays,
    required this.price,
    required this.discount,
    required this.finalPrice,
    this.description,
    this.branchId,
    this.isActive = true,
  });

  final int id;
  final String name;
  final int durationDays;
  final double price;
  final double discount;
  final double finalPrice;
  final String? description;
  final int? branchId;
  final bool isActive;

  bool get hasDiscount => discount > 0;

  String get durationLabel {
    if (durationDays % 365 == 0 && durationDays >= 365) {
      final years = durationDays ~/ 365;
      return '$years year${years > 1 ? 's' : ''}';
    }
    if (durationDays % 30 == 0) {
      final months = durationDays ~/ 30;
      return '$months month${months > 1 ? 's' : ''}';
    }
    return '$durationDays days';
  }

  factory GymPackage.fromJson(Map<String, dynamic> json) {
    final price = _asDouble(json['price']);
    final discount = _asDouble(json['discount']);
    return GymPackage(
      id: _asInt(json['id']),
      name: (json['name'] as String?) ?? '',
      durationDays: _asInt(json['duration_days']),
      price: price,
      discount: discount,
      finalPrice: json['final_price'] == null
          ? (price - discount < 0 ? 0 : price - discount)
          : _asDouble(json['final_price']),
      description: json['description'] as String?,
      branchId: json['branch_id'] == null ? null : _asInt(json['branch_id']),
      isActive: json['is_active'] == true || json['is_active'] == 1,
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
}
