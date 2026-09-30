import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../providers/membership_provider.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/package_card.dart';
import '../../widgets/shimmer_list.dart';

class MembershipPlansScreen extends StatefulWidget {
  const MembershipPlansScreen({super.key});

  @override
  State<MembershipPlansScreen> createState() => _MembershipPlansScreenState();
}

class _MembershipPlansScreenState extends State<MembershipPlansScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MembershipProvider>().loadPackages(force: true);
    });
  }

  @override
  Widget build(BuildContext context) {
    final membership = context.watch<MembershipProvider>();
    final renewing = membership.hasMembership;

    return Scaffold(
      appBar: AppBar(
        title: Text(renewing ? 'Renew membership' : 'Choose a plan'),
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () => membership.loadPackages(force: true),
          child: _buildBody(context, membership, renewing),
        ),
      ),
    );
  }

  Widget _buildBody(
    BuildContext context,
    MembershipProvider membership,
    bool renewing,
  ) {
    if (membership.loadingPackages && membership.packages.isEmpty) {
      return const ShimmerList(itemCount: 4, height: 150);
    }

    if (membership.packagesError != null && membership.packages.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
          ErrorState(
            message: membership.packagesError!,
            onRetry: () => membership.loadPackages(force: true),
          ),
        ],
      );
    }

    if (membership.packages.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 80),
          EmptyState(
            icon: Icons.card_membership_outlined,
            title: 'No plans available',
            subtitle: 'Your gym has not published any packages yet. Please check back later.',
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        Text(
          renewing
              ? 'Pick a plan to renew. Our team confirms the payment and activates it.'
              : 'Pick a plan that fits your goals. Our team confirms the payment and activates it.',
          style: const TextStyle(color: AppColors.muted, height: 1.4),
        ),
        const SizedBox(height: 16),
        for (final package in membership.packages) ...[
          PackageCard(
            package: package,
            ctaLabel: renewing ? 'Renew' : 'Select',
            onSelect: () => context.push('/membership/summary', extra: package),
          ),
          const SizedBox(height: 12),
        ],
      ],
    );
  }
}
