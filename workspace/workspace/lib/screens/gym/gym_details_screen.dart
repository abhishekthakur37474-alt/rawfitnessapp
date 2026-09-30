import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/external_links.dart';
import '../../models/gym.dart';
import '../../providers/gym_provider.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/section_header.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class GymDetailsScreen extends StatefulWidget {
  const GymDetailsScreen({super.key, this.branchId});

  final int? branchId;

  @override
  State<GymDetailsScreen> createState() => _GymDetailsScreenState();
}

class _GymDetailsScreenState extends State<GymDetailsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final gym = context.read<GymProvider>();
    if (gym.branches.isEmpty) await gym.loadBranches();
    final id = widget.branchId ?? gym.selectedBranchId ?? (gym.branches.isNotEmpty ? gym.branches.first.id : null);
    if (id != null) await gym.loadBranchDetail(id, force: true);
  }

  Future<void> _copied(String label) async {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$label copied')));
  }

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    final id = widget.branchId ?? gym.selectedBranchId ?? (gym.branches.isNotEmpty ? gym.branches.first.id : null);
    final branch = gym.branchDetail?.id == id ? gym.branchDetail : null;

    return Scaffold(
      appBar: AppBar(title: Text(branch?.name ?? 'Gym details')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: _body(gym, branch, id),
      ),
    );
  }

  Widget _body(GymProvider gym, GymBranch? branch, int? id) {
    if ((gym.loadingDetail || gym.loadingBranches) && branch == null) {
      return const ShimmerList(itemCount: 5, height: 88);
    }
    if (gym.detailError != null && branch == null) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.25),
          ErrorState(message: gym.detailError!, onRetry: _load),
        ],
      );
    }
    if (id == null || branch == null) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 80),
          EmptyState(title: 'No branch selected', subtitle: 'Choose a branch to view gym details.'),
        ],
      );
    }

    final photos = branch.photos.map((p) => p.imageUrl).whereType<String>().where((u) => u.isNotEmpty).toList();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        if (photos.isNotEmpty) ...[
          SizedBox(
            height: 180,
            child: AppNetworkImage(
              url: photos.first,
              width: double.infinity,
              height: 180,
              borderRadius: BorderRadius.circular(AppTheme.radiusLg),
            ),
          ),
          const SizedBox(height: 16),
        ],
        Text(
          branch.name,
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
        ),
        if (branch.subtitle.isNotEmpty) ...[
          const SizedBox(height: 4),
          Text(branch.subtitle, style: const TextStyle(color: AppColors.muted)),
        ],
        if ((branch.description ?? '').trim().isNotEmpty) ...[
          const SizedBox(height: 12),
          Text(branch.description!.trim(), style: const TextStyle(height: 1.45)),
        ],
        const SizedBox(height: 16),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            if ((branch.phone ?? '').isNotEmpty)
              _ActionChip(
                icon: Icons.call,
                label: 'Call',
                onTap: () async {
                  final v = await ExternalLinks.tel(branch.phone);
                  if (v != null) await _copied('Phone');
                },
              ),
            if ((branch.whatsapp ?? '').isNotEmpty)
              _ActionChip(
                icon: Icons.chat,
                label: 'WhatsApp',
                onTap: () async {
                  final v = await ExternalLinks.whatsapp(branch.whatsapp);
                  if (v != null) await _copied('WhatsApp link');
                },
              ),
            if ((branch.email ?? '').isNotEmpty)
              _ActionChip(
                icon: Icons.email_outlined,
                label: 'Email',
                onTap: () async {
                  final v = await ExternalLinks.email(branch.email);
                  if (v != null) await _copied('Email');
                },
              ),
            _ActionChip(
              icon: Icons.directions,
              label: 'Directions',
              onTap: () async {
                final v = await ExternalLinks.maps(
                  lat: branch.lat,
                  lng: branch.lng,
                  query: [branch.address, branch.city, branch.name].where((e) => (e ?? '').isNotEmpty).join(', '),
                );
                if (v != null) await _copied('Maps link');
              },
            ),
          ],
        ),
        const SizedBox(height: 24),
        const SectionHeader(title: 'Timings'),
        const SizedBox(height: 12),
        InfoCard(
          child: Column(
            children: [
              for (var i = 0; i < branch.timings.length; i++) ...[
                if (i > 0) const Divider(height: 16),
                Row(
                  children: [
                    Expanded(child: Text(branch.timings[i].day)),
                    Text(
                      branch.timings[i].label,
                      style: TextStyle(
                        color: branch.timings[i].isClosed ? AppColors.destructive : AppColors.muted,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ],
              if (branch.timings.isEmpty)
                const Text('Timings not listed', style: TextStyle(color: AppColors.muted)),
            ],
          ),
        ),
        const SizedBox(height: 24),
        const SectionHeader(title: 'Facilities'),
        const SizedBox(height: 12),
        if (branch.facilities.isEmpty)
          const Text('No facilities listed', style: TextStyle(color: AppColors.muted))
        else
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (final f in branch.facilities) StatusChip(label: f.name, tone: StatusTone.info),
            ],
          ),
        const SizedBox(height: 24),
        const SectionHeader(title: 'Parking'),
        const SizedBox(height: 12),
        InfoCard(
          child: branch.parking == null || !branch.parking!.hasParking
              ? const Text('Parking details not listed', style: TextStyle(color: AppColors.muted))
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        if (branch.parking!.twoWheeler) const StatusChip(label: 'Two-wheeler', tone: StatusTone.success),
                        if (branch.parking!.fourWheeler) const StatusChip(label: 'Four-wheeler', tone: StatusTone.success),
                      ],
                    ),
                    if ((branch.parking!.notes ?? '').isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(branch.parking!.notes!, style: const TextStyle(color: AppColors.muted, height: 1.4)),
                    ],
                  ],
                ),
        ),
        if (photos.isNotEmpty) ...[
          const SizedBox(height: 24),
          const SectionHeader(title: 'Photos'),
          const SizedBox(height: 12),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: photos.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
            ),
            itemBuilder: (_, i) {
              return GestureDetector(
                onTap: () => context.push('/gym/photos', extra: {'urls': photos, 'index': i}),
                child: AppNetworkImage(
                  url: photos[i],
                  width: double.infinity,
                  height: double.infinity,
                ),
              );
            },
          ),
        ],
      ],
    );
  }
}

class _ActionChip extends StatelessWidget {
  const _ActionChip({required this.icon, required this.label, required this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      avatar: Icon(icon, size: 18, color: AppColors.primary),
      label: Text(label),
      onPressed: onTap,
      backgroundColor: AppColors.surfaceHigh,
      side: const BorderSide(color: AppColors.border),
    );
  }
}
