import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../models/package.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/section_header.dart';
import '../../widgets/status_chip.dart';

class PlanSummaryScreen extends StatelessWidget {
  const PlanSummaryScreen({super.key, this.package});

  final GymPackage? package;

  @override
  Widget build(BuildContext context) {
    final plan = package;
    if (plan == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Plan summary')),
        body: EmptyState(
          icon: Icons.card_membership_outlined,
          title: 'No plan selected',
          subtitle: 'Choose a package to see its summary.',
          actionLabel: 'Browse plans',
          onAction: () => context.go('/membership/plans'),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Plan summary')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
          children: [
            InfoCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          plan.name,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.w700,
                              ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      StatusChip(
                        label: plan.durationLabel,
                        tone: StatusTone.info,
                      ),
                    ],
                  ),
                  if ((plan.description ?? '').trim().isNotEmpty) ...[
                    const SizedBox(height: 10),
                    Text(
                      plan.description!,
                      style: const TextStyle(color: AppColors.muted, height: 1.4),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 20),
            const SectionHeader(title: 'Price details'),
            const SizedBox(height: 12),
            InfoCard(
              child: Column(
                children: [
                  _priceRow('Package price', Formatters.rupees(plan.price)),
                  if (plan.hasDiscount)
                    _priceRow(
                      'Discount',
                      '- ${Formatters.rupees(plan.discount)}',
                      highlight: true,
                    ),
                  const Divider(height: 24),
                  _priceRow(
                    'Payable now',
                    Formatters.rupees(plan.finalPrice),
                    strong: true,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            const SectionHeader(title: 'What happens next'),
            const SizedBox(height: 12),
            const InfoCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _Bullet(text: 'Choose your preferred payment method on the next screen.'),
                  SizedBox(height: 10),
                  _Bullet(text: 'Online payments are coming soon. Until then the gym team can record the payment for you.'),
                  SizedBox(height: 10),
                  _Bullet(text: 'Your membership activates once the payment is confirmed.'),
                ],
              ),
            ),
            const SizedBox(height: 24),
            PrimaryButton(
              label: 'Continue to payment',
              icon: Icons.arrow_forward,
              onPressed: () => context.push('/membership/payment', extra: plan),
            ),
          ],
        ),
      ),
    );
  }

  Widget _priceRow(
    String label,
    String value, {
    bool highlight = false,
    bool strong = false,
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
              color: strong
                  ? AppColors.primary
                  : (highlight ? AppColors.accent : AppColors.foreground),
            ),
          ),
        ],
      ),
    );
  }
}

class _Bullet extends StatelessWidget {
  const _Bullet({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Icon(Icons.check_circle_outline, size: 18, color: AppColors.accent),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(color: AppColors.muted, height: 1.4),
          ),
        ),
      ],
    );
  }
}
