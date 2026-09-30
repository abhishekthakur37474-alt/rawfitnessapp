import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../core/utils/formatters.dart';
import '../models/package.dart';
import 'info_card.dart';
import 'status_chip.dart';

class PackageCard extends StatelessWidget {
  const PackageCard({
    super.key,
    required this.package,
    this.onSelect,
    this.selected = false,
    this.ctaLabel,
  });

  final GymPackage package;
  final VoidCallback? onSelect;
  final bool selected;
  final String? ctaLabel;

  @override
  Widget build(BuildContext context) {
    return InfoCard(
      onTap: onSelect,
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(20),
          border: selected
              ? Border.all(color: AppColors.primary, width: 1.5)
              : null,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        package.name,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w700,
                            ),
                      ),
                      const SizedBox(height: 8),
                      StatusChip(
                        label: package.durationLabel,
                        tone: StatusTone.info,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    if (package.hasDiscount)
                      Text(
                        Formatters.rupees(package.price),
                        style: const TextStyle(
                          color: AppColors.muted,
                          fontSize: 12,
                          decoration: TextDecoration.lineThrough,
                        ),
                      ),
                    Text(
                      Formatters.rupees(package.finalPrice),
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                    ),
                    if (package.hasDiscount)
                      Text(
                        'Save ${Formatters.rupees(package.discount)}',
                        style: const TextStyle(
                          color: AppColors.accent,
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                  ],
                ),
              ],
            ),
            if ((package.description ?? '').trim().isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(
                package.description!,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: AppColors.muted, height: 1.4),
              ),
            ],
            if (ctaLabel != null && onSelect != null) ...[
              const SizedBox(height: 14),
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: onSelect,
                  style: TextButton.styleFrom(
                    foregroundColor: AppColors.primary,
                  ),
                  child: Text('$ctaLabel  ›'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
