import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/utils/notification_router.dart';
import '../../models/app_notification.dart';
import '../../providers/notification_provider.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NotificationProvider>().refresh(force: true);
    });
  }

  @override
  Widget build(BuildContext context) {
    final inbox = context.watch<NotificationProvider>();
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          if (inbox.unreadCount > 0)
            TextButton(
              onPressed: inbox.markAllRead,
              child: const Text('Mark all read'),
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => inbox.refresh(force: true),
        child: _body(inbox),
      ),
    );
  }

  Widget _body(NotificationProvider inbox) {
    if (inbox.loading && inbox.items.isEmpty) {
      return const ShimmerList(itemCount: 6, height: 80);
    }
    if (inbox.error != null && inbox.items.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.22),
          ErrorState(message: inbox.error!, onRetry: () => inbox.refresh(force: true)),
        ],
      );
    }
    if (inbox.items.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 80),
          EmptyState(
            title: 'No notifications',
            subtitle: 'Membership reminders and gym updates will show up here.',
            icon: Icons.notifications_none,
          ),
        ],
      );
    }
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      itemCount: inbox.items.length,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (_, i) {
        final n = inbox.items[i];
        return InfoCard(
          onTap: () => _open(n),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 10,
                height: 10,
                margin: const EdgeInsets.only(top: 6),
                decoration: BoxDecoration(
                  color: n.isRead ? AppColors.border : AppColors.primary,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            n.title,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontWeight: n.isRead ? FontWeight.w500 : FontWeight.w700,
                            ),
                          ),
                        ),
                        StatusChip(
                          label: n.routeType.toUpperCase(),
                          tone: n.isRead ? StatusTone.neutral : StatusTone.info,
                        ),
                      ],
                    ),
                    if ((n.body ?? '').isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(
                        n.body!,
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: AppColors.muted, height: 1.4),
                      ),
                    ],
                    if (n.createdAt != null) ...[
                      const SizedBox(height: 6),
                      Text(
                        Formatters.dateTime(n.createdAt!),
                        style: const TextStyle(color: AppColors.muted, fontSize: 12),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _open(AppNotification n) async {
    await context.read<NotificationProvider>().markRead(n);
    if (!mounted) return;
    NotificationRouter.open(context, type: n.routeType, id: n.linkId);
  }
}
