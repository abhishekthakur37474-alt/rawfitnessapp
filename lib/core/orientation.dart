import 'package:flutter/services.dart';

Future<void> lockPortrait() {
  return SystemChrome.setPreferredOrientations(const [
    DeviceOrientation.portraitUp,
  ]);
}
