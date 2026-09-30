import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../core/theme/app_colors.dart';
import '../core/utils/formatters.dart';
import '../models/membership.dart';
import 'info_card.dart';
import 'qr_view.dart';
import 'status_chip.dart';

class MembershipCard extends StatelessWidget {
  const MembershipCard({
    super.key,
    required this.membership,
    required this.memberId,
    this.onTap,
  });

  final Membership membership;
  final String memberId;
  final VoidCallback? onTap;

  static final DateFormat _date = DateFormat('dd MMM yyyy');

  @override
  Widget build(BuildContext context) {
    final daysLabel = membership.daysLeft < 0
        ? 'Expired'
        : '${membership.daysLeft} day${membership.daysLeft == 1 ? '' : 's'}';

    return InfoCard(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  membership.planName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                ),
              ),
              const SizedBox(width: 8),
              StatusChip(
                label: membership.statusLabel,
                tone: MembershipCard.toneFor(membership.status),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _row('Member ID', memberId.isEmpty ? '—' : memberId),
                    _row('Valid from', _date.format(membership.startDate)),
                    _row('Valid till', _date.format(membership.endDate)),
                    _row('Days left', daysLabel),
                    _row(
                      'Due amount',
                      Formatters.rupees(membership.dueAmount),
                      highlight: membership.hasDue,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Column(
                children: [
                  QrView(data: memberId.isEmpty ? 'GYM000000' : memberId, size: 104),
                  const SizedBox(height: 6),
                  const Text(
                    'Scan at gym',
                    style: TextStyle(color: AppColors.muted, fontSize: 11),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _row(String label, String value, {bool highlight = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(color: AppColors.muted, fontSize: 12),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.right,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: highlight ? AppColors.warning : AppColors.foreground,
              ),
            ),
          ),
        ],
      ),
    );
  }

  static StatusTone toneFor(MembershipStatus status) {
    return switch (status) {
      MembershipStatus.active => StatusTone.success,
      MembershipStatus.expiring => StatusTone.warning,
      MembershipStatus.expired => StatusTone.danger,
      MembershipStatus.cancelled => StatusTone.neutral,
      MembershipStatus.none => StatusTone.neutral,
    };
  }
}
