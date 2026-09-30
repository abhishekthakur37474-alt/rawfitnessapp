import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/constants/app_constants.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/validators.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/app_text_field.dart';
import '../../widgets/keyboard_safe.dart';
import '../../widgets/primary_button.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _height = TextEditingController();
  final _idNumber = TextEditingController();
  String _gender = 'male';
  String _idType = 'aadhaar';
  XFile? _image;
  bool _submitting = false;

  static const _labels = {
    'aadhaar': 'Aadhaar',
    'pan': 'PAN',
    'passport': 'Passport',
    'driving_license': 'Driving License',
  };

  @override
  void initState() {
    super.initState();
    final user = context.read<AuthProvider>().user;
    if (user?.name != null) _name.text = user!.name!;
    if (user?.heightCm != null) _height.text = '${user!.heightCm}';
    if (user?.gender != null) _gender = user!.gender!;
  }

  @override
  void dispose() {
    _name.dispose();
    _height.dispose();
    _idNumber.dispose();
    super.dispose();
  }

  Future<void> _pick(ImageSource source) async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: source, imageQuality: 85, maxWidth: 1600);
    if (file == null) return;
    final bytes = await file.length();
    if (bytes > AppConstants.govIdMaxBytes) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Image must be 5MB or smaller')),
      );
      return;
    }
    setState(() => _image = file);
  }

  Future<void> _showPicker() async {
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.photo_camera_outlined),
                title: const Text('Camera'),
                onTap: () {
                  Navigator.pop(ctx);
                  _pick(ImageSource.camera);
                },
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_outlined),
                title: const Text('Gallery'),
                onTap: () {
                  Navigator.pop(ctx);
                  _pick(ImageSource.gallery);
                },
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_image == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Upload a photo of your ID')),
      );
      return;
    }
    setState(() => _submitting = true);
    final auth = context.read<AuthProvider>();
    try {
      await auth.authService.submitOnboarding(
        name: _name.text.trim(),
        gender: _gender,
        heightCm: double.parse(_height.text.trim()),
      );
      await auth.authService.uploadGovId(
        type: _idType,
        number: _idNumber.text.trim(),
        imagePath: _image!.path,
      );
      final refreshed = await auth.authService.getProfile();
      if (!refreshed.isOnboarded) {
        await auth.setUser(refreshed);
        throw Exception('Add your government ID to finish setup.');
      }
      await auth.completeOnboarding(refreshed);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Complete profile')),
      body: KeyboardSafe(
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Tell us about you so we can activate your membership ID.',
                style: TextStyle(color: AppColors.muted, height: 1.5),
              ),
              const SizedBox(height: 20),
              AppTextField(
                label: 'Full name',
                controller: _name,
                textInputAction: TextInputAction.next,
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
                textInputAction: TextInputAction.next,
                validator: Validators.heightCm,
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
                ],
              ),
              const SizedBox(height: 16),
              Text('Government ID', style: Theme.of(context).textTheme.labelLarge),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _idType,
                items: [
                  for (final t in AppConstants.govIdTypes)
                    DropdownMenuItem(value: t, child: Text(_labels[t] ?? t)),
                ],
                onChanged: (v) {
                  if (v == null) return;
                  setState(() => _idType = v);
                },
              ),
              const SizedBox(height: 16),
              AppTextField(
                label: 'ID number',
                controller: _idNumber,
                textInputAction: TextInputAction.done,
                validator: (v) => Validators.govIdNumber(_idType, v),
              ),
              const SizedBox(height: 16),
              GestureDetector(
                onTap: _showPicker,
                child: Container(
                  width: double.infinity,
                  height: 160,
                  decoration: BoxDecoration(
                    color: AppColors.surfaceHigh,
                    borderRadius: BorderRadius.circular(AppTheme.radiusMd),
                    border: Border.all(color: AppColors.border),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: _image == null
                      ? const Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.add_a_photo_outlined, color: AppColors.muted),
                            SizedBox(height: 8),
                            Text('Upload ID photo', style: TextStyle(color: AppColors.muted)),
                          ],
                        )
                      : Image.file(File(_image!.path), fit: BoxFit.cover),
                ),
              ),
              const SizedBox(height: 24),
              PrimaryButton(
                label: 'Submit',
                loading: _submitting,
                onPressed: _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
