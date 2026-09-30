import 'package:flutter/foundation.dart';

import '../models/app_notification.dart';
import '../services/notification_service.dart';

class NotificationProvider extends ChangeNotifier {
  NotificationProvider(this._service);

  final NotificationService _service;

  List<AppNotification> items = [];
  int unreadCount = 0;
  bool loading = false;
  String? error;

  Future<void> refresh({bool force = false}) async {
    if (items.isNotEmpty && !force) {
      await refreshUnread();
      return;
    }
    loading = true;
    error = null;
    notifyListeners();
    try {
      final result = await _service.list();
      items = result.items;
      unreadCount = result.unreadCount;
    } catch (e) {
      error = e.toString().replaceFirst('Exception: ', '');
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> refreshUnread() async {
    try {
      unreadCount = await _service.unreadCount();
      notifyListeners();
    } catch (_) {}
  }

  Future<void> markRead(AppNotification item) async {
    if (item.isRead) return;
    try {
      unreadCount = await _service.markRead(item.id);
      items = [
        for (final n in items)
          if (n.id == item.id)
            AppNotification(
              id: n.id,
              title: n.title,
              body: n.body,
              type: n.type,
              data: n.data,
              isRead: true,
              imageUrl: n.imageUrl,
              createdAt: n.createdAt,
            )
          else
            n,
      ];
      notifyListeners();
    } catch (_) {}
  }

  Future<void> markAllRead() async {
    try {
      await _service.markAllRead();
      unreadCount = 0;
      items = [
        for (final n in items)
          AppNotification(
            id: n.id,
            title: n.title,
            body: n.body,
            type: n.type,
            data: n.data,
            isRead: true,
            imageUrl: n.imageUrl,
            createdAt: n.createdAt,
          ),
      ];
      notifyListeners();
    } catch (_) {}
  }

  void reset() {
    items = [];
    unreadCount = 0;
    error = null;
    notifyListeners();
  }
}
