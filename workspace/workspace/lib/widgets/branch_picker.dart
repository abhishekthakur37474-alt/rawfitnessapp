import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../models/gym.dart';
import '../providers/gym_provider.dart';

class BranchPicker extends StatelessWidget {
  const BranchPicker({super.key, this.onChanged});

  final ValueChanged<GymBranch?>? onChanged;

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    if (gym.branches.isEmpty) return const SizedBox.shrink();
    return DropdownButtonFormField<int?>(
      initialValue: gym.selectedBranchId,
      decoration: const InputDecoration(
        labelText: 'Branch',
        isDense: true,
      ),
      items: [
        const DropdownMenuItem<int?>(
          value: null,
          child: Text('All branches'),
        ),
        for (final b in gym.branches)
          DropdownMenuItem<int?>(
            value: b.id,
            child: Text(b.name, overflow: TextOverflow.ellipsis),
          ),
      ],
      onChanged: (id) async {
        GymBranch? next;
        if (id != null) {
          for (final b in gym.branches) {
            if (b.id == id) {
              next = b;
              break;
            }
          }
        }
        await gym.selectBranch(next);
        onChanged?.call(next);
      },
    );
  }
}

class BranchChipBar extends StatelessWidget {
  const BranchChipBar({super.key});

  @override
  Widget build(BuildContext context) {
    final gym = context.watch<GymProvider>();
    if (gym.branches.isEmpty) return const SizedBox.shrink();
    return SizedBox(
      height: 40,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        children: [
          _Chip(
            label: 'All',
            selected: gym.selectedBranch == null,
            onTap: () => gym.selectBranch(null),
          ),
          const SizedBox(width: 8),
          for (final b in gym.branches) ...[
            _Chip(
              label: b.name,
              selected: gym.selectedBranchId == b.id,
              onTap: () => gym.selectBranch(b),
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
      side: BorderSide(color: selected ? AppColors.primary : AppColors.border),
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
