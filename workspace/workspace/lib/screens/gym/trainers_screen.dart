import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../providers/gym_provider.dart';
import '../../widgets/branch_picker.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/shimmer_list.dart';

class TrainersScreen extends StatefulWidget {
  const TrainersScreen({super.key});

  @override
  State<TrainersScreen> createState() => _TrainersScreenState();
}

class _TrainersScreenState extends State<TrainersScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<GymProvider>().loadBranches();
    });
  }

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    return Scaffold(
      appBar: AppBar(title: const Text('Trainers')),
      body: Column(
        children: [
          const SizedBox(height: 8),
          const BranchChipBar(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => gym.loadFeed(force: true),
              child: _body(gym),
            ),
          ),
        ],
      ),
    );
  }

  Widget _body(GymProvider gym) {
    if (gym.loadingFeed && gym.trainers.isEmpty) {
      return const ShimmerList(itemCount: 6, height: 88);
    }
    if (gym.feedError != null && gym.trainers.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
          ErrorState(message: gym.feedError!, onRetry: () => gym.loadFeed(force: true)),
        ],
      );
    }
    if (gym.trainers.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 80),
          EmptyState(title: 'No trainers yet', subtitle: 'Trainers for this branch will show up here.'),
        ],
      );
    }
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      itemCount: gym.trainers.length,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (_, i) {
        final t = gym.trainers[i];
        return InfoCard(
          onTap: () => context.push('/gym/trainers/${t.id}'),
          child: Row(
            children: [
              CircleAvatar(
                radius: 28,
                backgroundColor: AppColors.surfaceHigh,
                backgroundImage: (t.imageUrl ?? '').isNotEmpty ? NetworkImage(t.imageUrl!) : null,
                child: (t.imageUrl ?? '').isEmpty
                    ? Text(
                        t.name.isNotEmpty ? t.name[0].toUpperCase() : 'T',
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      )
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(t.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700)),
                    if ((t.role ?? '').isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(t.role!, style: const TextStyle(color: AppColors.muted, fontSize: 13)),
                    ],
                    if ((t.branchName ?? '').isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(t.branchName!, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                    ],
                  ],
                ),
              ),
              const Icon(Icons.chevron_right, color: AppColors.muted),
            ],
          ),
        );
      },
    );
  }
}
