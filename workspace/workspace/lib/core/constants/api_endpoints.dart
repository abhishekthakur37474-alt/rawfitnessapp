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
  static const String packages = '/packages';
  static const String membershipCurrent = '/membership/current';
  static const String membershipHistory = '/membership/history';
  static const String membershipRenew = '/membership/renew';
  static const String paymentsHistory = '/payments/history';
  static const String paymentsInitiate = '/payments/initiate';

  static String receipt(int id) => '/receipts/$id';

  static const String workoutPlans = '/workout-plans';
  static const String dietPlans = '/diet-plans';

  static String workoutPlan(int id) => '/workout-plans/$id';
  static String dietPlan(int id) => '/diet-plans/$id';

  static const String branches = '/branches';
  static const String trainers = '/trainers';
  static const String events = '/events';
  static const String announcements = '/announcements';
  static const String banners = '/banners';
  static const String attendance = '/attendance';
  static const String attendanceCheckin = '/attendance/checkin';

  static String branch(int id) => '/branches/$id';
  static String trainer(int id) => '/trainers/$id';

  static const String notifications = '/notifications';
  static const String notificationsUnread = '/notifications/unread-count';
  static const String notificationsReadAll = '/notifications/read-all';

  static String notificationRead(int id) => '/notifications/$id/read';
}
