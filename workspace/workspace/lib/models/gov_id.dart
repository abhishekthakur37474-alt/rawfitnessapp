class GovId {
  const GovId({
    required this.type,
    required this.idNumber,
    required this.status,
    this.reason,
    this.imageUrl,
  });

  final String type;
  final String idNumber;
  final String status;
  final String? reason;
  final String? imageUrl;

  factory GovId.fromJson(Map<String, dynamic> json) {
    return GovId(
      type: (json['type'] as String?) ?? '',
      idNumber: (json['id_number'] as String?) ?? '',
      status: (json['status'] as String?) ?? 'pending',
      reason: json['reason'] as String?,
      imageUrl: json['image_url'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'type': type,
        'id_number': idNumber,
        'status': status,
        'reason': reason,
        'image_url': imageUrl,
      };
}
