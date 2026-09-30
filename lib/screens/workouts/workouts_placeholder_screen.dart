import 'package:flutter/material.dart';

import '../../widgets/empty_state.dart';

class WorkoutsPlaceholderScreen extends StatelessWidget {
  const WorkoutsPlaceholderScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Workouts')),
      body: const SafeArea(
        child: EmptyState(
          icon: Icons.fitness_center_outlined,
          title: 'Workout plans coming soon',
          subtitle: 'Plans will load from the gym admin panel.',
        ),
      ),
    );
  }
}
