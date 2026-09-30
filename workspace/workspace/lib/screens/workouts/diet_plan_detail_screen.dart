import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_theme.dart';
import '../../models/diet_plan.dart';
import '../../providers/plans_provider.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class DietPlanDetailScreen extends StatefulWidget {
  const DietPlanDetailScreen({super.key, required this.planId});

  final int planId;

  @override
  State<DietPlanDetailScreen> createState() => _DietPlanDetailScreenState();
}

class _DietPlanDetailScreenState extends State<DietPlanDetailScreen> {
  DietPlan? _plan;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final plan = await context.read<PlansProvider>().service.dietPlan(widget.planId);
      if (!mounted) return;
      setState(() {
        _plan = plan;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_plan?.title ?? 'Diet plan')),
      body: SafeArea(child: _body()),
    );
  }

  Widget _body() {
    if (_loading) {
      return const ShimmerList(itemCount: 5, height: 88);
    }
    if (_error != null) {
      return ErrorState(message: _error!, onRetry: _load);
    }
    final plan = _plan;
    if (plan == null) {
      return const EmptyState(
        icon: Icons.restaurant_outlined,
        title: 'Plan not found',
      );
    }
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
      children: [
        if ((plan.imageUrl ?? '').isNotEmpty) ...[
          AppNetworkImage(
            url: plan.imageUrl,
            height: 180,
            width: double.infinity,
            borderRadius: BorderRadius.circular(AppTheme.radiusLg),
          ),
          const SizedBox(height: 16),
        ],
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            if ((plan.category ?? '').trim().isNotEmpty)
              StatusChip(label: plan.category!.trim(), tone: StatusTone.info),
            if (plan.caloriesLabel.isNotEmpty)
              StatusChip(label: plan.caloriesLabel, tone: StatusTone.success),
            if ((plan.mealCount ?? plan.meals.length) > 0)
              StatusChip(
                label: '${plan.mealCount ?? plan.meals.length} meals',
                tone: StatusTone.neutral,
              ),
          ],
        ),
        if ((plan.description ?? '').trim().isNotEmpty) ...[
          const SizedBox(height: 14),
          Text(
            plan.description!,
            style: const TextStyle(color: AppColors.muted, height: 1.45),
          ),
        ],
        const SizedBox(height: 20),
        Text(
          'Meals',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
        ),
        const SizedBox(height: 8),
        if (plan.meals.isEmpty)
          const Padding(
            padding: EdgeInsets.only(top: 24),
            child: EmptyState(
              icon: Icons.no_meals_outlined,
              title: 'No meals yet',
              subtitle: 'This plan has no meals listed.',
            ),
          )
        else
          ...plan.meals.map(_mealCard),
      ],
    );
  }

  Widget _mealCard(DietMeal meal) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: InfoCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    meal.mealType,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
                if ((meal.time ?? '').trim().isNotEmpty)
                  StatusChip(label: meal.time!.trim(), tone: StatusTone.info),
              ],
            ),
            if ((meal.items ?? '').trim().isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                meal.items!,
                style: const TextStyle(color: AppColors.muted, height: 1.4),
              ),
            ],
            if (meal.calories != null && meal.calories! > 0) ...[
              const SizedBox(height: 8),
              Text(
                '${meal.calories} kcal',
                style: const TextStyle(
                  color: AppColors.accent,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
