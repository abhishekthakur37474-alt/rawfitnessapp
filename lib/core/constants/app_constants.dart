class AppConstants {
  AppConstants._();

  static const String appName = 'Raw Fitness';
  static const String countryCode = '+91';
  static const int otpLength = 6;
  static const int otpResendSeconds = 30;
  static const int otpExpiryMinutes = 5;
  static const int govIdMaxBytes = 5 * 1024 * 1024;
  static const Duration splashDuration = Duration(milliseconds: 1600);
  static const Duration animationFast = Duration(milliseconds: 180);
  static const Duration animationNormal = Duration(milliseconds: 240);
  static const List<String> govIdTypes = [
    'aadhaar',
    'pan',
    'passport',
    'driving_license',
  ];
}
