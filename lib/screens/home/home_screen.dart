import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../models/package.dart';
import '../../providers/auth_provider.dart';
import '../../providers/membership_provider.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/membership_card.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/section_header.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool _bootstrapped = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _bootstrap());
  }

  Future<void> _bootstrap() async {
    if (_bootstrapped) return;
    _bootstrapped = true;
    final membership = context.read<MembershipProvider>();
    await membership.refreshCurrent();
    if (!mounted) return;
    if (!membership.hasMembership && !membership.popupShown) {
      await _showGetMembershipSheet();
    }
  }

  Future<void> _showGetMembershipSheet() async {
    final membership = context.read<MembershipProvider>();
    membership.markPopupShown();
    await membership.loadPackages();
    if (!mounted) return;
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      isDismissible: true,
      enableDrag: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (sheetContext) => _GetMembershipSheet(
        onSelect: (package) {
          Navigator.pop(sheetContext);
          context.push('/membership/summary', extra: package);
        },
        onViewAll: () {
          Navigator.pop(sheetContext);
          context.push('/membership/plans');
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final membership = context.watch<MembershipProvider>();
    final user = auth.user;
    final name = (user?.name?.trim().isNotEmpty ?? false)
        ? user!.name!
        : 'Athlete';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Home'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: membership.refreshCurrent,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: membership.refreshCurrent,
          child: _buildBody(context, membership, name),
        ),
      ),
    );
  }

  Widget _buildBody(
    BuildContext context,
    MembershipProvider membership,
    String name,
  ) {
    final memberId = context.watch<AuthProvider>().user?.memberId ?? '';

    if (membership.loadingCurrent && membership.current == null) {
      return const ShimmerList(itemCount: 4, height: 96);
    }

    if (membership.currentError != null && membership.current == null) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.25),
          ErrorState(
            message: membership.currentError!,
            onRetry: membership.refreshCurrent,
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        Text(
          'Hey, $name',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.w700,
              ),
        ),
        const SizedBox(height: 4),
        Text(
          membership.hasMembership
              ? 'Here is your membership at a glance.'
              : 'Activate your membership to start training.',
          style: const TextStyle(color: AppColors.muted),
        ),
        const SizedBox(height: 20),
        if (membership.hasMembership) ...[
          MembershipCard(
            membership: membership.current!,
            memberId: memberId,
            onTap: () => context.go('/membership'),
          ),
          if (membership.hasDue) ...[
            const SizedBox(height: 12),
            InfoCard(
              child: Row(
                children: [
                  const Icon(Icons.info_outline, color: AppColors.warning),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'You have a pending due amount. Clear it at the gym reception.',
                      style: TextStyle(height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ],
          if (membership.hasPendingRequest) ...[
            const SizedBox(height: 12),
            InfoCard(
              child: Row(
                children: [
                  const Icon(Icons.hourglass_top, color: AppColors.info),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Your renewal request is pending approval by the gym team.',
                      style: TextStyle(height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 20),
          const SectionHeader(title: 'Quick actions'),
          const SizedBox(height: 12),
          PrimaryButton(
            label: 'Renew membership',
            icon: Icons.autorenew,
            onPressed: () => context.push('/membership/plans'),
          ),
        ] else ...[
          InfoCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Expanded(
                      child: Text(
                        'No active membership',
                        style: TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    const StatusChip(label: 'GET STARTED', tone: StatusTone.info),
                  ],
                ),
                const SizedBox(height: 8),
                const Text(
                  'Choose a plan that fits your goals. Packages are managed by your gym.',
                  style: TextStyle(color: AppColors.muted, height: 1.4),
                ),
                const SizedBox(height: 16),
                PrimaryButton(
                  label: 'Get your membership',
                  icon: Icons.card_membership,
                  onPressed: _showGetMembershipSheet,
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  String user_memberId(BuildContext context) {
    return context.read<AuthProvider>().user?.memberId ?? '';
  }
}

class _GetMembershipSheet extends StatelessWidget {
  const _GetMembershipSheet({
    required this.onSelect,
    required this.onViewAll,
  });

  final ValueChanged<GymPackage> onSelect;
  final VoidCallback onViewAll;

  @override
  Widget build(BuildContext context) {
    final membership = context.watch<MembershipProvider>();
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Expanded(
                  child: Text(
                    'Get your membership',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                  ),
                ),
                IconButton(
                  tooltip: 'Close',
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
            const Text(
              'Pick a plan to continue.',
              style: TextStyle(color: AppColors.muted),
            ),
            const SizedBox(height: 12),
            ConstrainedBox(
              constraints: BoxConstraints(
                maxHeight: MediaQuery.of(context).size.height * 0.55,
              ),
              child: _buildPlans(context, membership),
            ),
            const SizedBox(height: 8),
            Center(
              child: TextButton(
                onPressed: onViewAll,
                child: const Text('See all plans'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPlans(BuildContext context, MembershipProvider membership) {
    if (membership.loadingPackages && membership.packages.isEmpty) {
      return const SizedBox(
        height: 180,
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (membership.packagesError != null && membership.packages.isEmpty) {
      return SizedBox(
        height: 180,
        child: ErrorState(
          message: membership.packagesError!,
          onRetry: () => membership.loadPackages(force: true),
        ),
      );
    }
    if (membership.packages.isEmpty) {
      return const SizedBox(
        height: 140,
        child: Center(
          child: Text(
            'No packages available yet.',
            style: TextStyle(color: AppColors.muted),
          ),
        ),
      );
    }
    return ListView.separated(
      shrinkWrap: true,
      itemCount: membership.packages.length,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (_, index) {
        final package = membership.packages[index];
        return InfoCard(
          onTap: () => onSelect(package),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      package.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      package.durationLabel,
                      style: const TextStyle(
                        color: AppColors.muted,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Text(
                '₹${package.finalPrice.toStringAsFixed(0)}',
                style: const TextStyle(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  String user_memberId(BuildContext context) {
    return context.read<AuthProvider>().user?.memberId ?? '';
  }
}
