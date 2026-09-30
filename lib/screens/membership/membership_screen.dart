import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../models/membership.dart';
import '../../providers/auth_provider.dart';
import '../../providers/membership_provider.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/membership_card.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/section_header.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class MembershipScreen extends StatefulWidget {
  const MembershipScreen({super.key});

  @override
  State<MembershipScreen> createState() => _MembershipScreenState();
}

class _MembershipScreenState extends State<MembershipScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final membership = context.read<MembershipProvider>();
    await Future.wait([
      membership.refreshCurrent(),
      membership.loadHistory(),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    final membership = context.watch<MembershipProvider>();
    final memberId = context.watch<AuthProvider>().user?.memberId ?? '';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Membership'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _load,
          child: _buildBody(context, membership, memberId),
        ),
      ),
    );
  }

  Widget _buildBody(
    BuildContext context,
    MembershipProvider membership,
    String memberId,
  ) {
    if (membership.loadingCurrent && membership.current == null) {
      return const ShimmerList(itemCount: 4, height: 96);
    }

    if (membership.currentError != null && membership.current == null) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
          ErrorState(
            message: membership.currentError!,
            onRetry: _load,
          ),
        ],
      );
    }

    final current = membership.current;
    if (current == null) {
      return ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          const SizedBox(height: 24),
          EmptyState(
            icon: Icons.card_membership_outlined,
            title: 'No membership yet',
            subtitle: 'Browse the packages and request your membership to get started.',
            actionLabel: 'Browse plans',
            onAction: () => context.push('/membership/plans'),
          ),
          const SizedBox(height: 8),
          if (membership.history.isNotEmpty) ...[
            const SectionHeader(title: 'Past memberships'),
            const SizedBox(height: 12),
            for (final item in membership.history)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _HistoryTile(item: item),
              ),
          ],
        ],
      );
    }

    final history = membership.history
        .where((item) => item.id != current.id)
        .toList(growable: false);

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        MembershipCard(membership: current, memberId: memberId),
        if (membership.hasPendingRequest) ...[
          const SizedBox(height: 12),
          const InfoCard(
            child: Row(
              children: [
                Icon(Icons.hourglass_top, color: AppColors.info),
                SizedBox(width: 12),
                Expanded(
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
        const SectionHeader(title: 'Fees & due amount'),
        const SizedBox(height: 12),
        InfoCard(
          child: Column(
            children: [
              _amountRow('Total amount', Formatters.rupees(current.amount)),
              _amountRow('Paid amount', Formatters.rupees(current.paidAmount)),
              const Divider(height: 24),
              _amountRow(
                'Due amount',
                Formatters.rupees(current.dueAmount),
                highlight: current.hasDue,
                strong: true,
              ),
              if (current.hasDue) ...[
                const SizedBox(height: 12),
                const Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Please clear the due amount at the gym reception.',
                    style: TextStyle(color: AppColors.warning, fontSize: 12, height: 1.4),
                  ),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 20),
        const SectionHeader(title: 'Validity & expiry'),
        const SizedBox(height: 12),
        InfoCard(
          child: Column(
            children: [
              _detailRow('Valid from', Formatters.date(current.startDate)),
              _detailRow('Valid till', Formatters.date(current.endDate)),
              _detailRow(
                'Days left',
                current.daysLeft < 0
                    ? 'Expired'
                    : '${current.daysLeft} day${current.daysLeft == 1 ? '' : 's'}',
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        PrimaryButton(
          label: current.isActive ? 'Renew membership' : 'Reactivate membership',
          icon: Icons.autorenew,
          onPressed: () => context.push('/membership/plans'),
        ),
        const SizedBox(height: 12),
        OutlinedButton(
          onPressed: () => context.push('/membership/history'),
          style: OutlinedButton.styleFrom(
            minimumSize: const Size.fromHeight(52),
            foregroundColor: AppColors.foreground,
            side: const BorderSide(color: AppColors.border),
          ),
          child: const Text('Payment history & receipts'),
        ),
        if (history.isNotEmpty) ...[
          const SizedBox(height: 24),
          const SectionHeader(title: 'Membership history'),
          const SizedBox(height: 12),
          for (final item in history)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: _HistoryTile(item: item),
            ),
        ],
      ],
    );
  }

  Widget _amountRow(
    String label,
    String value, {
    bool strong = false,
    bool highlight = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                color: strong ? AppColors.foreground : AppColors.muted,
                fontWeight: strong ? FontWeight.w600 : FontWeight.w400,
              ),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: strong ? 18 : 14,
              fontWeight: strong ? FontWeight.w700 : FontWeight.w600,
              color: highlight ? AppColors.warning : AppColors.foreground,
            ),
          ),
        ],
      ),
    );
  }

  Widget _detailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Expanded(
            child: Text(label, style: const TextStyle(color: AppColors.muted)),
          ),
          Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}

class _HistoryTile extends StatelessWidget {
  const _HistoryTile({required this.item});

  final Membership item;

  @override
  Widget build(BuildContext context) {
    return InfoCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  item.planName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
                ),
              ),
              const SizedBox(width: 8),
              StatusChip(
                label: item.statusLabel,
                tone: MembershipCard.toneFor(item.status),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            '${Formatters.date(item.startDate)}  -  ${Formatters.date(item.endDate)}',
            style: const TextStyle(color: AppColors.muted, fontSize: 12),
          ),
          const SizedBox(height: 4),
          Text(
            'Paid ${Formatters.rupees(item.paidAmount)}',
            style: const TextStyle(color: AppColors.muted, fontSize: 12),
          ),
        ],
      ),
    );
  }
}
