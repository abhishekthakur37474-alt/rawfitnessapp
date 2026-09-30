class ApiEndpoints {
  ApiEndpoints._();

  static const String health = '/health';
  static const String sendOtp = '/auth/send-otp';
  static const String verifyOtp = '/auth/verify-otp';
  static const String profile = '/profile';
  static const String profileUpdate = '/profile/update';
  static const String onboarding = '/profile/onboarding';
  static const String govId = '/profile/gov-id';
  static const String onesignalSubscription = '/device/onesignal-subscription';
}
