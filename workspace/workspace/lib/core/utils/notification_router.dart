import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class NotificationRouter {
  NotificationRouter._();

  static void open(
    BuildContext context, {
    required String type,
    String? id,
  }) {
    switch (type.toLowerCase()) {
      case 'membership':
        context.push('/membership');
        return;
      case 'event':
        context.push((id != null && id.isNotEmpty) ? '/gym/events/$id' : '/gym/events');
        return;
      case 'announcement':
        context.push(
          (id != null && id.isNotEmpty) ? '/gym/announcements/$id' : '/gym/events',
        );
        return;
      default:
        context.push('/notifications');
    }
  }

  static String locationFor({required String type, String? id}) {
    switch (type.toLowerCase()) {
      case 'membership':
        return '/membership';
      case 'event':
        return (id != null && id.isNotEmpty) ? '/gym/events/$id' : '/gym/events';
      case 'announcement':
        return (id != null && id.isNotEmpty) ? '/gym/announcements/$id' : '/gym/events';
      default:
        return '/notifications';
    }
  }

  static String? payloadId(Map<String, dynamic> data) {
    final v = data['id'] ??
        data['event_id'] ??
        data['announcement_id'] ??
        data['membership_id'];
    if (v == null) return null;
    final s = '$v'.trim();
    return s.isEmpty ? null : s;
  }
}
