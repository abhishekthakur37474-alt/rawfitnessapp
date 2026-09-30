import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../providers/plans_provider.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/plan_card.dart';
import '../../widgets/shimmer_list.dart';

class WorkoutsScreen extends StatefulWidget {
  const WorkoutsScreen({super.key});

  @override
  State<WorkoutsScreen> createState() => _WorkoutsScreenState();
}

class _WorkoutsScreenState extends State<WorkoutsScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabs;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final plans = context.read<PlansProvider>();
      plans.loadWorkouts();
      plans.loadDiets();
    });
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Workouts'),
        bottom: TabBar(
          controller: _tabs,
          tabs: const [
            Tab(text: 'Workout Plans'),
            Tab(text: 'Diet Plans'),
          ],
        ),
      ),
      body: SafeArea(
        child: TabBarView(
          controller: _tabs,
          children: const [
            _WorkoutPlansTab(),
            _DietPlansTab(),
          ],
        ),
      ),
    );
  }
}

class _WorkoutPlansTab extends StatelessWidget {
  const _WorkoutPlansTab();

  @override
  Widget build(BuildContext context) {
    final plans = context.watch<PlansProvider>();
    return Column(
      children: [
        const SizedBox(height: 12),
        CategoryChips(
          categories: plans.workoutCategories,
          selected: plans.workoutCategory,
          onSelected: plans.setWorkoutCategory,
        ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () => plans.loadWorkouts(force: true),
            child: _body(
              context,
              loading: plans.loadingWorkouts && plans.workouts.isEmpty,
              error: plans.workoutsError,
              empty: plans.filteredWorkouts.isEmpty,
              onRetry: () => plans.loadWorkouts(force: true),
              emptyTitle: 'No workout plans',
              emptySubtitle: plans.workoutCategory == null
                  ? 'Your gym has not published workout plans yet.'
                  : 'No plans in this category.',
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
                physics: const AlwaysScrollableScrollPhysics(),
                itemCount: plans.filteredWorkouts.length,
                separatorBuilder: (_, _) => const SizedBox(height: 12),
                itemBuilder: (context, index) {
                  final plan = plans.filteredWorkouts[index];
                  return PlanCard(
                    title: plan.title,
                    imageUrl: plan.imageUrl,
                    category: plan.category,
                    meta: plan.metaLabel,
                    description: plan.description,
                    onTap: () => context.push('/workouts/${plan.id}'),
                  );
                },
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _DietPlansTab extends StatelessWidget {
  const _DietPlansTab();

  @override
  Widget build(BuildContext context) {
    final plans = context.watch<PlansProvider>();
    return Column(
      children: [
        const SizedBox(height: 12),
        CategoryChips(
          categories: plans.dietCategories,
          selected: plans.dietCategory,
          onSelected: plans.setDietCategory,
        ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () => plans.loadDiets(force: true),
            child: _body(
              context,
              loading: plans.loadingDiets && plans.diets.isEmpty,
              error: plans.dietsError,
              empty: plans.filteredDiets.isEmpty,
              onRetry: () => plans.loadDiets(force: true),
              emptyTitle: 'No diet plans',
              emptySubtitle: plans.dietCategory == null
                  ? 'Your gym has not published diet plans yet.'
                  : 'No plans in this category.',
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
                physics: const AlwaysScrollableScrollPhysics(),
                itemCount: plans.filteredDiets.length,
                separatorBuilder: (_, _) => const SizedBox(height: 12),
                itemBuilder: (context, index) {
                  final plan = plans.filteredDiets[index];
                  return PlanCard(
                    title: plan.title,
                    imageUrl: plan.imageUrl,
                    category: plan.category,
                    meta: plan.metaLabel,
                    description: plan.description,
                    onTap: () => context.push('/diets/${plan.id}'),
                  );
                },
              ),
            ),
          ),
        ),
      ],
    );
  }
}

Widget _body(
  BuildContext context, {
  required bool loading,
  required String? error,
  required bool empty,
  required VoidCallback onRetry,
  required String emptyTitle,
  required String emptySubtitle,
  required Widget child,
}) {
  if (loading) {
    return const ShimmerList(itemCount: 4, height: 180);
  }
  if (error != null && empty) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        SizedBox(height: MediaQuery.of(context).size.height * 0.18),
        ErrorState(message: error, onRetry: onRetry),
      ],
    );
  }
  if (empty) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 80),
        EmptyState(
          icon: Icons.fitness_center_outlined,
          title: emptyTitle,
          subtitle: emptySubtitle,
        ),
      ],
    );
  }
  return child;
}
