import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class AppTextField extends StatelessWidget {
  const AppTextField({
    super.key,
    required this.label,
    this.controller,
    this.hint,
    this.keyboardType,
    this.textInputAction,
    this.validator,
    this.maxLength,
    this.obscureText = false,
    this.enabled = true,
    this.prefix,
    this.suffix,
    this.onChanged,
    this.autofocus = false,
    this.inputFormatters,
    this.maxLines = 1,
  });

  final String label;
  final TextEditingController? controller;
  final String? hint;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final String? Function(String?)? validator;
  final int? maxLength;
  final bool obscureText;
  final bool enabled;
  final Widget? prefix;
  final Widget? suffix;
  final ValueChanged<String>? onChanged;
  final bool autofocus;
  final List<TextInputFormatter>? inputFormatters;
  final int maxLines;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: Theme.of(context).textTheme.labelLarge,
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          textInputAction: textInputAction,
          validator: validator,
          maxLength: maxLength,
          obscureText: obscureText,
          enabled: enabled,
          onChanged: onChanged,
          autofocus: autofocus,
          inputFormatters: inputFormatters,
          maxLines: maxLines,
          autovalidateMode: AutovalidateMode.onUserInteraction,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: prefix is Icon ? prefix : null,
            prefix: prefix is Icon ? null : prefix,
            suffixIcon: suffix,
            counterText: '',
          ),
        ),
      ],
    );
  }
}
