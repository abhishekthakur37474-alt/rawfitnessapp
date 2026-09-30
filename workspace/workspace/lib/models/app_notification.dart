class AppNotification {
  const AppNotification({
    required this.id,
    required this.title,
    this.body,
    this.type = 'general',
    this.data = const {},
    this.isRead = false,
    this.imageUrl,
    this.createdAt,
  });

  final int id;
  final String title;
  final String? body;
  final String type;
  final Map<String, dynamic> data;
  final bool isRead;
  final String? imageUrl;
  final DateTime? createdAt;

  String get routeType {
    final fromData = '${data['type'] ?? ''}'.trim();
    if (fromData.isNotEmpty) return fromData.toLowerCase();
    return type.toLowerCase();
  }

  String? get linkId {
    final v = data['id'] ?? data['event_id'] ?? data['announcement_id'] ?? data['membership_id'];
    if (v == null) return null;
    final s = '$v'.trim();
    return s.isEmpty ? null : s;
  }

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    final dataRaw = json['data'];
    return AppNotification(
      id: _asInt(json['id']),
      title: (json['title'] as String?) ?? '',
      body: json['body'] as String?,
      type: (json['type'] as String?) ?? 'general',
      data: dataRaw is Map ? dataRaw.cast<String, dynamic>() : const {},
      isRead: json['is_read'] == true || json['is_read'] == 1,
      imageUrl: json['image_url'] as String?,
      createdAt: json['created_at'] == null ? null : DateTime.tryParse('${json['created_at']}'),
    );
  }

  static int _asInt(dynamic v) {
    if (v is int) return v;
    return int.tryParse('$v') ?? 0;
  }
}
