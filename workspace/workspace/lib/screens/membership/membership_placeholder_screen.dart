import 'package:flutter/material.dart';

import '../../widgets/empty_state.dart';

class MembershipPlaceholderScreen extends StatelessWidget {
  const MembershipPlaceholderScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Membership')),
      body: const SafeArea(
        child: EmptyState(
          icon: Icons.card_membership_outlined,
          title: 'No membership yet',
          subtitle: 'Packages and billing will be available in the next phase.',
        ),
      ),
    );
  }
}
