import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import 'app.dart';
import 'core/orientation.dart';
import 'providers/app_providers.dart';
import 'services/onesignal_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await lockPortrait();
  SystemChrome.setSystemUIOverlayStyle(SystemUiOverlayStyle.light);

  final providers = buildAppProviders();

  runApp(
    MultiProvider(
      providers: providers,
      child: const _Bootstrap(child: RawFitnessApp()),
    ),
  );
}

class _Bootstrap extends StatefulWidget {
  const _Bootstrap({required this.child});

  final Widget child;

  @override
  State<_Bootstrap> createState() => _BootstrapState();
}

class _BootstrapState extends State<_Bootstrap> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<OneSignalService>().init();
    });
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
