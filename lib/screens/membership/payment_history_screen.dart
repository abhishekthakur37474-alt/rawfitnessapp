import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../models/payment.dart';
import '../../providers/membership_provider.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class PaymentHistoryScreen extends StatefulWidget {
  const PaymentHistoryScreen({super.key});

  @override
  State<PaymentHistoryScreen> createState() => _PaymentHistoryScreenState();
}

class _PaymentHistoryScreenState extends State<PaymentHistoryScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MembershipProvider>().loadPayments();
    });
  }

  @override
  Widget build(BuildContext context) {
    final membership = context.watch<MembershipProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Payment history')),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: membership.loadPayments,
          child: _buildBody(context, membership),
        ),
      ),
    );
  }

  Widget _buildBody(BuildContext context, MembershipProvider membership) {
    if (membership.loadingPayments && membership.payments.isEmpty) {
      return const ShimmerList(itemCount: 5, height: 88);
    }

    if (membership.paymentsError != null && membership.payments.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
          ErrorState(
            message: membership.paymentsError!,
            onRetry: membership.loadPayments,
          ),
        ],
      );
    }

    if (membership.payments.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 80),
          EmptyState(
            icon: Icons.receipt_long_outlined,
            title: 'No payments yet',
            subtitle: 'Your payment receipts will appear here once recorded by the gym.',
          ),
        ],
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
      physics: const AlwaysScrollableScrollPhysics(),
      itemCount: membership.payments.length,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (_, index) {
        final payment = membership.payments[index];
        return _PaymentTile(
          payment: payment,
          onTap: () => context.push('/membership/receipt/${payment.id}'),
        );
      },
    );
  }
}

class _PaymentTile extends StatelessWidget {
  const _PaymentTile({required this.payment, required this.onTap});

  final PaymentRecord payment;
  final VoidCallback onTap;

  StatusTone get _tone {
    return switch (payment.status) {
      'success' => StatusTone.success,
      'failed' => StatusTone.danger,
      'pending' => StatusTone.warning,
      _ => StatusTone.neutral,
    };
  }

  @override
  Widget build(BuildContext context) {
    final date = payment.createdAt == null
        ? ''
        : Formatters.dateTime(payment.createdAt!);

    return InfoCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  (payment.packageName == null || payment.packageName!.isEmpty)
                      ? 'Membership payment'
                      : payment.packageName!,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
                ),
              ),
              const SizedBox(width: 8),
              Text(
                Formatters.rupees(payment.amount),
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  color: AppColors.primary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: Text(
                  '${payment.mode.toUpperCase()}  ·  $date',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: AppColors.muted, fontSize: 12),
                ),
              ),
              if ((payment.receiptNo ?? '').isNotEmpty)
                Text(
                  payment.receiptNo!,
                  style: const TextStyle(color: AppColors.muted, fontSize: 12),
                ),
            ],
          ),
          const SizedBox(height: 10),
          StatusChip(label: payment.status.toUpperCase(), tone: _tone),
        ],
      ),
    );
  }
}
