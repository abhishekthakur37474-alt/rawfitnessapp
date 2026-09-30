import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_theme.dart';
import '../../models/workout_plan.dart';
import '../../providers/plans_provider.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class WorkoutPlanDetailScreen extends StatefulWidget {
  const WorkoutPlanDetailScreen({super.key, required this.planId});

  final int planId;

  @override
  State<WorkoutPlanDetailScreen> createState() => _WorkoutPlanDetailScreenState();
}

class _WorkoutPlanDetailScreenState extends State<WorkoutPlanDetailScreen> {
  WorkoutPlan? _plan;
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
      final plan = await context.read<PlansProvider>().service.workoutPlan(widget.planId);
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
      appBar: AppBar(title: Text(_plan?.title ?? 'Workout plan')),
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
        icon: Icons.fitness_center_outlined,
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
            StatusChip(label: plan.levelLabel, tone: StatusTone.neutral),
            if ((plan.dayCount ?? plan.days.length) > 0)
              StatusChip(
                label: '${plan.dayCount ?? plan.days.length} days',
                tone: StatusTone.success,
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
          'Day-wise exercises',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
        ),
        const SizedBox(height: 8),
        if (plan.days.isEmpty)
          const Padding(
            padding: EdgeInsets.only(top: 24),
            child: EmptyState(
              icon: Icons.event_busy_outlined,
              title: 'No days yet',
              subtitle: 'This plan has no scheduled exercises.',
            ),
          )
        else
          ...plan.days.map(_dayTile),
      ],
    );
  }

  Widget _dayTile(WorkoutDay day) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          initiallyExpanded: day.dayNumber == 1,
          tilePadding: const EdgeInsets.symmetric(horizontal: 12),
          childrenPadding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
          collapsedShape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radiusMd),
            side: const BorderSide(color: AppColors.border),
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radiusMd),
            side: const BorderSide(color: AppColors.border),
          ),
          backgroundColor: AppColors.surface,
          collapsedBackgroundColor: AppColors.surface,
          title: Text(
            day.displayTitle,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          subtitle: Text(
            [
              if ((day.notes ?? '').trim().isNotEmpty) day.notes!.trim(),
              '${day.exercises.length} exercise${day.exercises.length == 1 ? '' : 's'}',
            ].join(' · '),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: AppColors.muted, fontSize: 12),
          ),
          children: [
            if (day.exercises.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  'No exercises on this day.',
                  style: TextStyle(color: AppColors.muted),
                ),
              )
            else
              ...day.exercises.map(_exerciseRow),
          ],
        ),
      ),
    );
  }

  Widget _exerciseRow(WorkoutExercise exercise) {
    final bits = <String>[];
    if ((exercise.sets ?? '').trim().isNotEmpty) bits.add('${exercise.sets} sets');
    if ((exercise.reps ?? '').trim().isNotEmpty) bits.add('${exercise.reps} reps');
    if ((exercise.rest ?? '').trim().isNotEmpty) bits.add('Rest ${exercise.rest}');

    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppNetworkImage(
            url: exercise.imageUrl,
            width: 52,
            height: 52,
            borderRadius: BorderRadius.circular(12),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  exercise.name,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                if (bits.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    bits.join(' · '),
                    style: const TextStyle(color: AppColors.muted, fontSize: 12),
                  ),
                ],
                if ((exercise.notes ?? '').trim().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    exercise.notes!,
                    style: const TextStyle(color: AppColors.muted, fontSize: 12),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
