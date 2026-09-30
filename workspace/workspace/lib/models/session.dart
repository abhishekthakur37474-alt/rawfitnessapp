import 'user.dart';

class Session {
  const Session({
    required this.token,
    required this.user,
  });

  final String token;
  final User user;

  factory Session.fromJson(Map<String, dynamic> json) {
    final userMap = Map<String, dynamic>.from(
      (json['user'] as Map?)?.cast<String, dynamic>() ?? json,
    );
    userMap['is_onboarded'] ??= json['is_onboarded'];
    return Session(
      token: (json['token'] as String?) ?? '',
      user: User.fromJson(userMap),
    );
  }
}
