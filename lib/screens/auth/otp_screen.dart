import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/constants/app_constants.dart';
import '../../core/theme/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/keyboard_safe.dart';
import '../../widgets/primary_button.dart';

class OtpScreen extends StatefulWidget {
  const OtpScreen({super.key, required this.mobile});

  final String mobile;

  @override
  State<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends State<OtpScreen> {
  final _controllers = List.generate(6, (_) => TextEditingController());
  final _nodes = List.generate(6, (_) => FocusNode());
  Timer? _timer;
  int _seconds = AppConstants.otpResendSeconds;
  bool _autoTried = false;

  @override
  void initState() {
    super.initState();
    _startTimer();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_nodes.first.canRequestFocus) _nodes.first.requestFocus();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    for (final c in _controllers) {
      c.dispose();
    }
    for (final n in _nodes) {
      n.dispose();
    }
    super.dispose();
  }

  void _startTimer() {
    _timer?.cancel();
    setState(() => _seconds = AppConstants.otpResendSeconds);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_seconds <= 1) {
        t.cancel();
        setState(() => _seconds = 0);
        return;
      }
      setState(() => _seconds -= 1);
    });
  }

  String get _code => _controllers.map((c) => c.text).join();

  Future<void> _verify({bool auto = false}) async {
    if (_code.length != 6) return;
    if (auto && _autoTried) return;
    if (auto) _autoTried = true;
    final auth = context.read<AuthProvider>();
    final ok = await auth.verifyOtp(widget.mobile, _code);
    if (!mounted) return;
    if (!ok) {
      _autoTried = false;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(auth.error ?? 'Invalid OTP')),
      );
    }
  }

  Future<void> _resend() async {
    if (_seconds > 0) return;
    final auth = context.read<AuthProvider>();
    final ok = await auth.sendOtp(widget.mobile);
    if (!mounted) return;
    if (!ok) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(auth.error ?? 'Unable to resend OTP')),
      );
      return;
    }
    _autoTried = false;
    for (final c in _controllers) {
      c.clear();
    }
    _nodes.first.requestFocus();
    _startTimer();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final masked = widget.mobile.length == 10
        ? '${widget.mobile.substring(0, 2)}******${widget.mobile.substring(8)}'
        : widget.mobile;

    return Scaffold(
      appBar: AppBar(title: const Text('Verify OTP')),
      body: KeyboardSafe(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Enter the 6-digit code sent to ${AppConstants.countryCode} $masked',
              style: const TextStyle(color: AppColors.muted, height: 1.5),
            ),
            if (auth.lastDevOtp != null) ...[
              const SizedBox(height: 12),
              Text(
                'Dev OTP: ${auth.lastDevOtp}',
                style: const TextStyle(color: AppColors.warning, fontWeight: FontWeight.w600),
              ),
            ],
            const SizedBox(height: 28),
            Row(
              children: List.generate(6, (i) {
                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 4),
                    child: SizedBox(
                      height: 56,
                      child: TextField(
                        controller: _controllers[i],
                        focusNode: _nodes[i],
                        textAlign: TextAlign.center,
                        keyboardType: TextInputType.number,
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          LengthLimitingTextInputFormatter(1),
                        ],
                        decoration: const InputDecoration(counterText: ''),
                        onChanged: (v) {
                          if (v.isNotEmpty && i < 5) {
                            _nodes[i + 1].requestFocus();
                          } else if (v.isEmpty && i > 0) {
                            _nodes[i - 1].requestFocus();
                          }
                          if (_code.length == 6) {
                            _verify(auto: true);
                          }
                        },
                      ),
                    ),
                  ),
                );
              }),
            ),
            const SizedBox(height: 24),
            PrimaryButton(
              label: 'Verify',
              loading: auth.busy,
              onPressed: () => _verify(),
            ),
            const SizedBox(height: 16),
            Center(
              child: TextButton(
                onPressed: _seconds == 0 && !auth.busy ? _resend : null,
                child: Text(
                  _seconds == 0 ? 'Resend OTP' : 'Resend in ${_seconds}s',
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
