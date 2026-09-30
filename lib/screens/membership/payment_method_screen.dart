import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../models/package.dart';
import '../../providers/membership_provider.dart';
import '../../services/payment_service.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/section_header.dart';
import '../../widgets/status_chip.dart';

class PaymentMethodScreen extends StatefulWidget {
  const PaymentMethodScreen({super.key, this.package});

  final GymPackage? package;

  @override
  State<PaymentMethodScreen> createState() => _PaymentMethodScreenState();
}

class _PaymentMethodScreenState extends State<PaymentMethodScreen> {
  final PaymentService _payments = const PaymentService();

  String _method = 'upi';
  bool _processing = false;

  static const List<_Method> _methods = [
    _Method('upi', 'UPI', 'Pay using any UPI app', Icons.qr_code_2),
    _Method('card', 'Credit / Debit Card', 'Visa, Mastercard, RuPay', Icons.credit_card),
    _Method('netbanking', 'Net Banking', 'All major banks supported', Icons.account_balance),
  ];

  Future<void> _pay(GymPackage plan) async {
    setState(() => _processing = true);
    try {
      final status = await _payments.initiate(
        method: _method,
        amount: plan.finalPrice,
      );
      if (!mounted) return;
      if (status == PaymentStatus.comingSoon) {
        await _showComingSoon();
      } else if (status == PaymentStatus.failed) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Payment could not be started. Please try again.')),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  Future<void> _showComingSoon() {
    return showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Coming Soon'),
        content: const Text(
          'Online payments are coming soon. Meanwhile, please complete the payment at the gym reception.',
          style: TextStyle(height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Got it'),
          ),
        ],
      ),
    );
  }

  Future<void> _submitRequest(GymPackage plan) async {
    final membership = context.read<MembershipProvider>();
    final ok = await membership.submitRenewal(plan.id);
    if (!mounted) return;
    if (ok) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Renewal request sent. The gym team will confirm shortly.')),
      );
      context.go('/membership');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(membership.actionError ?? 'Unable to submit request.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final plan = widget.package;
    if (plan == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Payment')),
        body: EmptyState(
          icon: Icons.payments_outlined,
          title: 'No plan selected',
          subtitle: 'Choose a package before making a payment.',
          actionLabel: 'Browse plans',
          onAction: () => context.go('/membership/plans'),
        ),
      );
    }

    final membership = context.watch<MembershipProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Payment')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
          children: [
            InfoCard(
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          plan.name,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          plan.durationLabel,
                          style: const TextStyle(color: AppColors.muted, fontSize: 12),
                        ),
                      ],
                    ),
                  ),
                  Text(
                    Formatters.rupees(plan.finalPrice),
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            const SectionHeader(title: 'Choose payment method'),
            const SizedBox(height: 12),
            for (final method in _methods) ...[
              _MethodTile(
                method: method,
                selected: _method == method.id,
                onTap: () => setState(() => _method = method.id),
              ),
              const SizedBox(height: 10),
            ],
            const SizedBox(height: 12),
            InfoCard(
              child: Row(
                children: [
                  const Icon(Icons.lock_outline, size: 18, color: AppColors.info),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Text(
                      'Payments are processed through a secure gateway once enabled.',
                      style: TextStyle(color: AppColors.muted, fontSize: 12, height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            PrimaryButton(
              label: 'Pay ${Formatters.rupees(plan.finalPrice)}',
              icon: Icons.lock,
              loading: _processing,
              onPressed: () => _pay(plan),
            ),
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: membership.submitting ? null : () => _submitRequest(plan),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(52),
                foregroundColor: AppColors.primary,
                side: const BorderSide(color: AppColors.primary),
              ),
              child: membership.submitting
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Request activation from gym'),
            ),
            const SizedBox(height: 8),
            const Center(
              child: Text(
                'Submitting a request lets the gym team confirm your plan manually.',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.muted, fontSize: 12, height: 1.4),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Method {
  const _Method(this.id, this.title, this.subtitle, this.icon);

  final String id;
  final String title;
  final String subtitle;
  final IconData icon;
}

class _MethodTile extends StatelessWidget {
  const _MethodTile({
    required this.method,
    required this.selected,
    required this.onTap,
  });

  final _Method method;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InfoCard(
      onTap: onTap,
      child: Row(
        children: [
          Icon(method.icon, color: selected ? AppColors.primary : AppColors.muted),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  method.title,
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                Text(
                  method.subtitle,
                  style: const TextStyle(color: AppColors.muted, fontSize: 12),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          if (selected)
            const StatusChip(label: 'Selected', tone: StatusTone.success)
          else
            const Icon(Icons.radio_button_unchecked, color: AppColors.border),
        ],
      ),
    );
  }
}
