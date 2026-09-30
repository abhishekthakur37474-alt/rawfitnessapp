import '../core/api/api_client.dart';
import '../core/constants/api_endpoints.dart';
import '../models/app_notification.dart';

class NotificationListResult {
  const NotificationListResult({required this.items, required this.unreadCount});

  final List<AppNotification> items;
  final int unreadCount;
}

class NotificationService {
  NotificationService(this._api);

  final ApiClient _api;

  Future<NotificationListResult> list() async {
    final res = await _api.get<NotificationListResult>(
      ApiEndpoints.notifications,
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        return NotificationListResult(
          items: ((map['notifications'] as List?) ?? const [])
              .map((e) => AppNotification.fromJson((e as Map).cast<String, dynamic>()))
              .toList(),
          unreadCount: _asInt(map['unread_count']),
        );
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load notifications' : res.message);
    }
    return res.data!;
  }

  Future<int> unreadCount() async {
    final res = await _api.get<int>(
      ApiEndpoints.notificationsUnread,
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        return _asInt(map['unread_count']);
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load unread count' : res.message);
    }
    return res.data!;
  }

  Future<int> markRead(int id) async {
    final res = await _api.post<int>(
      ApiEndpoints.notificationRead(id),
      parse: (raw) {
        final map = raw is Map ? raw.cast<String, dynamic>() : <String, dynamic>{};
        return _asInt(map['unread_count']);
      },
    );
    if (!res.status) {
      throw Exception(res.message.isEmpty ? 'Unable to mark as read' : res.message);
    }
    return res.data ?? 0;
  }

  Future<void> markAllRead() async {
    final res = await _api.post<void>(ApiEndpoints.notificationsReadAll);
    if (!res.status) {
      throw Exception(res.message.isEmpty ? 'Unable to mark all as read' : res.message);
    }
  }

  static int _asInt(dynamic v) {
    if (v is int) return v;
    return int.tryParse('$v') ?? 0;
  }
}
