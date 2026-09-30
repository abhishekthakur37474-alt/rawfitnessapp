import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../models/gym.dart';
import '../../providers/gym_provider.dart';
import '../../widgets/branch_picker.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/plan_card.dart';
import '../../widgets/shimmer_list.dart';

class EventsScreen extends StatefulWidget {
  const EventsScreen({super.key});

  @override
  State<EventsScreen> createState() => _EventsScreenState();
}

class _EventsScreenState extends State<EventsScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabs;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<GymProvider>().loadBranches();
    });
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Updates'),
        bottom: TabBar(
          controller: _tabs,
          tabs: const [
            Tab(text: 'Events'),
            Tab(text: 'Announcements'),
          ],
        ),
      ),
      body: Column(
        children: [
          const SizedBox(height: 8),
          const BranchChipBar(),
          Expanded(
            child: TabBarView(
              controller: _tabs,
              children: const [
                _EventsTab(),
                _AnnouncementsTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _EventsTab extends StatelessWidget {
  const _EventsTab();

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    return RefreshIndicator(
      onRefresh: () => gym.loadFeed(force: true),
      child: _body(context, gym),
    );
  }

  Widget _body(BuildContext context, GymProvider gym) {
    if (gym.loadingFeed && gym.events.isEmpty) {
      return const ShimmerList(itemCount: 4, height: 140);
    }
    if (gym.feedError != null && gym.events.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
          ErrorState(message: gym.feedError!, onRetry: () => gym.loadFeed(force: true)),
        ],
      );
    }
    if (gym.events.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 80),
          EmptyState(title: 'No events', subtitle: 'Upcoming gym events will appear here.'),
        ],
      );
    }
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      itemCount: gym.events.length,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (_, i) {
        final e = gym.events[i];
        return PlanCard(
          title: e.title,
          imageUrl: e.imageUrl,
          category: e.branchName,
          meta: [
            if (e.startsAt != null) Formatters.dateTime(e.startsAt!),
            if ((e.location ?? '').isNotEmpty) e.location,
          ].join(' · '),
          description: e.description,
          onTap: () => context.push('/gym/events/${e.id}', extra: e),
        );
      },
    );
  }
}

class _AnnouncementsTab extends StatelessWidget {
  const _AnnouncementsTab();

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    return RefreshIndicator(
      onRefresh: () => gym.loadFeed(force: true),
      child: _annBody(context, gym),
    );
  }

  Widget _annBody(BuildContext context, GymProvider gym) {
    if (gym.loadingFeed && gym.announcements.isEmpty) {
      return const ShimmerList(itemCount: 5, height: 88);
    }
    if (gym.feedError != null && gym.announcements.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
          ErrorState(message: gym.feedError!, onRetry: () => gym.loadFeed(force: true)),
        ],
      );
    }
    if (gym.announcements.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 80),
          EmptyState(title: 'No announcements', icon: Icons.campaign_outlined),
        ],
      );
    }
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      itemCount: gym.announcements.length,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (_, i) {
        final a = gym.announcements[i];
        return InfoCard(
          onTap: () => context.push('/gym/announcements/${a.id}', extra: a),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(a.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
              if (a.publishedAt != null) ...[
                const SizedBox(height: 4),
                Text(Formatters.date(a.publishedAt!), style: const TextStyle(color: AppColors.muted, fontSize: 12)),
              ],
              if ((a.body ?? '').isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  a.body!,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: AppColors.muted, height: 1.4),
                ),
              ],
            ],
          ),
        );
      },
    );
  }
}

class EventDetailScreen extends StatelessWidget {
  const EventDetailScreen({super.key, required this.eventId, this.event});

  final int eventId;
  final GymEvent? event;

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    GymEvent? found = event;
    if (found == null) {
      for (final e in gym.events) {
        if (e.id == eventId) {
          found = e;
          break;
        }
      }
    }
    return Scaffold(
      appBar: AppBar(title: Text(found?.title ?? 'Event')),
      body: found == null
          ? const EmptyState(title: 'Event not found')
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
              children: [
                PlanCard(
                  title: found.title,
                  imageUrl: found.imageUrl,
                  category: found.branchName,
                  meta: [
                    if (found.startsAt != null) Formatters.dateTime(found.startsAt!),
                    if (found.endsAt != null) 'to ${Formatters.dateTime(found.endsAt!)}',
                  ].join(' · '),
                  description: found.location,
                  onTap: () {},
                ),
                if ((found.description ?? '').trim().isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(found.description!.trim(), style: const TextStyle(height: 1.45)),
                ],
              ],
            ),
    );
  }
}

class AnnouncementDetailScreen extends StatelessWidget {
  const AnnouncementDetailScreen({super.key, required this.announcementId, this.announcement});

  final int announcementId;
  final GymAnnouncement? announcement;

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    GymAnnouncement? found = announcement;
    if (found == null) {
      for (final a in gym.announcements) {
        if (a.id == announcementId) {
          found = a;
          break;
        }
      }
    }
    return Scaffold(
      appBar: AppBar(title: Text(found?.title ?? 'Announcement')),
      body: found == null
          ? const EmptyState(title: 'Announcement not found')
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
              children: [
                Text(
                  found.title,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
                ),
                if (found.publishedAt != null) ...[
                  const SizedBox(height: 6),
                  Text(Formatters.dateTime(found.publishedAt!), style: const TextStyle(color: AppColors.muted)),
                ],
                if ((found.body ?? '').isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(found.body!, style: const TextStyle(height: 1.45)),
                ],
              ],
            ),
    );
  }
}
