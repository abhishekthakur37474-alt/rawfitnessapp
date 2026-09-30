import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/external_links.dart';
import '../../models/gym.dart';
import '../../providers/gym_provider.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/section_header.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class TrainerDetailScreen extends StatefulWidget {
  const TrainerDetailScreen({super.key, required this.trainerId});

  final int trainerId;

  @override
  State<TrainerDetailScreen> createState() => _TrainerDetailScreenState();
}

class _TrainerDetailScreenState extends State<TrainerDetailScreen> {
  Trainer? _trainer;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final trainer = await context.read<GymProvider>().service.trainer(widget.trainerId);
      if (!mounted) return;
      setState(() => _trainer = trainer);
    } catch (e) {
      _error = e.toString().replaceFirst('Exception: ', '');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = _trainer;
    return Scaffold(
      appBar: AppBar(title: Text(t?.name ?? 'Trainer')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: _body(t),
      ),
    );
  }

  Widget _body(Trainer? t) {
    if (_loading && t == null) return const ShimmerList(itemCount: 4, height: 88);
    if (_error != null && t == null) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.25),
          ErrorState(message: _error!, onRetry: _load),
        ],
      );
    }
    if (t == null) return const SizedBox.shrink();
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        Center(
          child: CircleAvatar(
            radius: 48,
            backgroundColor: AppColors.surfaceHigh,
            backgroundImage: (t.imageUrl ?? '').isNotEmpty ? NetworkImage(t.imageUrl!) : null,
            child: (t.imageUrl ?? '').isEmpty
                ? Text(t.name.isNotEmpty ? t.name[0].toUpperCase() : 'T', style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w700))
                : null,
          ),
        ),
        const SizedBox(height: 12),
        Text(t.name, textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
        if ((t.role ?? '').isNotEmpty) ...[
          const SizedBox(height: 4),
          Text(t.role!, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.muted)),
        ],
        if ((t.branchName ?? '').isNotEmpty) ...[
          const SizedBox(height: 8),
          Center(child: StatusChip(label: t.branchName!, tone: StatusTone.info)),
        ],
        if ((t.phone ?? '').isNotEmpty) ...[
          const SizedBox(height: 16),
          OutlinedButton.icon(
            onPressed: () async {
              final v = await ExternalLinks.tel(t.phone);
              if (v != null && mounted) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Phone copied')));
              }
            },
            icon: const Icon(Icons.call),
            label: const Text('Call trainer'),
          ),
        ],
        if ((t.bio ?? '').trim().isNotEmpty) ...[
          const SizedBox(height: 20),
          const SectionHeader(title: 'About'),
          const SizedBox(height: 8),
          Text(t.bio!.trim(), style: const TextStyle(height: 1.45)),
        ],
        const SizedBox(height: 20),
        const SectionHeader(title: 'Certifications'),
        const SizedBox(height: 12),
        if (t.certifications.isEmpty)
          const Text('No certifications listed', style: TextStyle(color: AppColors.muted))
        else
          ...t.certifications.map(
            (c) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: InfoCard(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(c.title, style: const TextStyle(fontWeight: FontWeight.w700)),
                    if ((c.issuer ?? '').isNotEmpty || (c.year ?? '').isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        [c.issuer, c.year].where((e) => (e ?? '').isNotEmpty).join(' · '),
                        style: const TextStyle(color: AppColors.muted, fontSize: 13),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }
}
