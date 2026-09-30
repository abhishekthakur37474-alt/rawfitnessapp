import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

class LegalScreen extends StatelessWidget {
  const LegalScreen({super.key, required this.kind});

  final String kind;

  bool get _terms => kind == 'terms';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_terms ? 'Terms of use' : 'Privacy policy')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
        children: [
          Text(
            _terms ? 'Raw Fitness Terms of Use' : 'Raw Fitness Privacy Policy',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          const Text(
            'Last updated: 30 Sep 2026',
            style: TextStyle(color: AppColors.muted),
          ),
          const SizedBox(height: 16),
          Text(_terms ? _termsBody : _privacyBody, style: const TextStyle(height: 1.5)),
        ],
      ),
    );
  }

  static const String _termsBody =
      'By using the Raw Fitness member app you agree to follow gym house rules, keep your login OTP private, and use membership only for yourself.\n\n'
      'Packages, workout plans, diet plans, trainers, events, and attendance records are provided by your gym and may change.\n\n'
      'Online payments and face check-in are marked Coming Soon. Until those go live, membership activation and attendance may be completed at reception.\n\n'
      'The gym may suspend access for unpaid dues, expired membership, or misuse of the app.';

  static const String _privacyBody =
      'We collect your mobile number, name, gender, height, government ID (for gym verification), membership and payment history, attendance, and device push subscription id so we can run the gym app.\n\n'
      'OTP is sent through the configured SMS provider. Push messages are delivered via OneSignal using your member ID as the external user id.\n\n'
      'Government ID images are stored on the gym server for verification. We do not sell your data.\n\n'
      'You can log out at any time. Logout clears the local session and OneSignal login on this device.';
}
