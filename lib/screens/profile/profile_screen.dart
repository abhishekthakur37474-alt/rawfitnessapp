import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/validators.dart';
import '../../models/user.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/app_text_field.dart';
import '../../widgets/error_state.dart';
import '../../widgets/info_card.dart';
import '../../widgets/keyboard_safe.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/shimmer_list.dart';
import '../../widgets/status_chip.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _height;
  String _gender = 'male';
  bool _saving = false;
  bool _loading = true;
  String? _loadError;

  @override
  void initState() {
    super.initState();
    final user = context.read<AuthProvider>().user;
    _name = TextEditingController(text: user?.name ?? '');
    _height = TextEditingController(
      text: user?.heightCm == null ? '' : '${user!.heightCm}',
    );
    _gender = user?.gender ?? 'male';
    _reload();
  }

  @override
  void dispose() {
    _name.dispose();
    _height.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      await context.read<AuthProvider>().refreshProfile();
      if (!mounted) return;
      final user = context.read<AuthProvider>().user;
      _sync(user);
    } catch (e) {
      _loadError = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _sync(User? user) {
    if (user == null) return;
    _name.text = user.name ?? '';
    _height.text = user.heightCm == null ? '' : '${user.heightCm}';
    _gender = user.gender ?? 'male';
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final auth = context.read<AuthProvider>();
    try {
      final updated = await auth.authService.updateProfile(
        name: _name.text.trim(),
        gender: _gender,
        heightCm: double.parse(_height.text.trim()),
      );
      await auth.setUser(updated);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profile updated')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  StatusTone _tone(String? status) {
    return switch (status) {
      'verified' => StatusTone.success,
      'rejected' => StatusTone.danger,
      'pending' => StatusTone.warning,
      _ => StatusTone.neutral,
    };
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    if (_loading && user == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Profile')),
        body: const ShimmerList(itemCount: 4, height: 72),
      );
    }

    if (_loadError != null && user == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Profile')),
        body: ErrorState(message: _loadError!, onRetry: _reload),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: KeyboardSafe(
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                InfoCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        user?.memberId ?? 'GYM000000',
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w700,
                            ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        user?.mobile ?? '',
                        style: const TextStyle(color: AppColors.muted),
                      ),
                      const SizedBox(height: 12),
                      StatusChip(
                        label: (user?.govIdStatus ?? 'pending').toUpperCase(),
                        tone: _tone(user?.govIdStatus),
                      ),
                      if (user?.govId?.reason != null &&
                          user!.govId!.reason!.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                          user.govId!.reason!,
                          style: const TextStyle(color: AppColors.muted),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                AppTextField(
                  label: 'Full name',
                  controller: _name,
                  validator: (v) => Validators.required(v, label: 'Full name'),
                ),
                const SizedBox(height: 16),
                Text('Gender', style: Theme.of(context).textTheme.labelLarge),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: [
                    for (final g in ['male', 'female', 'other'])
                      ChoiceChip(
                        label: Text(g[0].toUpperCase() + g.substring(1)),
                        selected: _gender == g,
                        onSelected: (_) => setState(() => _gender = g),
                      ),
                  ],
                ),
                const SizedBox(height: 16),
                AppTextField(
                  label: 'Height (cm)',
                  controller: _height,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  validator: Validators.heightCm,
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
                  ],
                ),
                const SizedBox(height: 24),
                PrimaryButton(
                  label: 'Save changes',
                  loading: _saving,
                  onPressed: _save,
                ),
                const SizedBox(height: 12),
                OutlinedButton(
                  onPressed: auth.busy
                      ? null
                      : () async {
                          final yes = await showDialog<bool>(
                            context: context,
                            builder: (ctx) => AlertDialog(
                              title: const Text('Log out?'),
                              content: const Text('You will need to verify OTP again.'),
                              actions: [
                                TextButton(
                                  onPressed: () => Navigator.pop(ctx, false),
                                  child: const Text('Cancel'),
                                ),
                                TextButton(
                                  onPressed: () => Navigator.pop(ctx, true),
                                  child: const Text('Log out'),
                                ),
                              ],
                            ),
                          );
                          if (yes == true) await auth.logout();
                        },
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size.fromHeight(52),
                    foregroundColor: AppColors.destructive,
                    side: const BorderSide(color: AppColors.destructive),
                  ),
                  child: const Text('Log out'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
