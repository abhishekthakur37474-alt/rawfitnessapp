import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:rawfitnessapp/core/theme/app_theme.dart';
import 'package:rawfitnessapp/widgets/empty_state.dart';
import 'package:rawfitnessapp/widgets/error_state.dart';
import 'package:rawfitnessapp/widgets/primary_button.dart';
import 'package:rawfitnessapp/widgets/status_chip.dart';

void main() {
  testWidgets('PrimaryButton renders label', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.dark,
        home: const Scaffold(
          body: PrimaryButton(label: 'Continue'),
        ),
      ),
    );
    expect(find.text('Continue'), findsOneWidget);
  });

  testWidgets('EmptyState and ErrorState render', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.dark,
        home: const Scaffold(
          body: Column(
            children: [
              EmptyState(title: 'Nothing here'),
              ErrorState(message: 'Offline'),
              StatusChip(label: 'Active', tone: StatusTone.success),
            ],
          ),
        ),
      ),
    );
    expect(find.text('Nothing here'), findsOneWidget);
    expect(find.text('Offline'), findsOneWidget);
    expect(find.text('Active'), findsOneWidget);
  });
}
