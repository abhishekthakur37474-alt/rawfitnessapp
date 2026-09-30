import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_theme.dart';
import 'shimmer_list.dart';
import 'status_chip.dart';

class PlanCard extends StatelessWidget {
  const PlanCard({
    super.key,
    required this.title,
    required this.onTap,
    this.imageUrl,
    this.category,
    this.meta,
    this.description,
  });

  final String title;
  final VoidCallback onTap;
  final String? imageUrl;
  final String? category;
  final String? meta;
  final String? description;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppTheme.radiusLg),
        child: Ink(
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppTheme.radiusLg),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              AppNetworkImage(
                url: imageUrl,
                height: 140,
                width: double.infinity,
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(AppTheme.radiusLg),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if ((category ?? '').trim().isNotEmpty) ...[
                      StatusChip(label: category!.trim(), tone: StatusTone.info),
                      const SizedBox(height: 8),
                    ],
                    Text(
                      title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    if ((meta ?? '').trim().isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(
                        meta!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.muted,
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                    if ((description ?? '').trim().isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        description!,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.muted,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class CategoryChips extends StatelessWidget {
  const CategoryChips({
    super.key,
    required this.categories,
    required this.selected,
    required this.onSelected,
  });

  final List<String> categories;
  final String? selected;
  final ValueChanged<String?> onSelected;

  @override
  Widget build(BuildContext context) {
    if (categories.isEmpty) return const SizedBox.shrink();
    return SizedBox(
      height: 40,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        children: [
          _Chip(
            label: 'All',
            selected: selected == null,
            onTap: () => onSelected(null),
          ),
          const SizedBox(width: 8),
          for (final cat in categories) ...[
            _Chip(
              label: cat,
              selected: selected == cat,
              onTap: () => onSelected(cat),
            ),
            const SizedBox(width: 8),
          ],
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onTap(),
      selectedColor: AppColors.primary.withValues(alpha: 0.22),
      backgroundColor: AppColors.surfaceHigh,
      side: BorderSide(
        color: selected ? AppColors.primary : AppColors.border,
      ),
      labelStyle: TextStyle(
        color: selected ? AppColors.primary : AppColors.foreground,
        fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
        fontSize: 13,
      ),
      showCheckmark: false,
      visualDensity: VisualDensity.compact,
    );
  }
}
