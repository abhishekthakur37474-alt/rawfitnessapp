import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/info_card.dart';
import '../../widgets/section_header.dart';

class HomePlaceholderScreen extends StatelessWidget {
  const HomePlaceholderScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    final name = (user?.name?.trim().isNotEmpty ?? false)
        ? user!.name!
        : 'Athlete';

    return Scaffold(
      appBar: AppBar(title: const Text('Home')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
          children: [
            Text(
              'Hey, $name',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
            ),
            const SizedBox(height: 4),
            const Text(
              'Your training hub is almost ready.',
              style: TextStyle(color: AppColors.muted),
            ),
            const SizedBox(height: 20),
            const SectionHeader(title: 'Coming next'),
            const SizedBox(height: 12),
            InfoCard(
              child: Text(
                user?.memberId.isNotEmpty == true
                    ? 'Member ${user!.memberId}. Membership packages arrive in the next phase.'
                    : 'Membership, packages, and gym updates will appear here from the admin panel.',
              ),
            ),
          ],
        ),
      ),
    );
  }
}
