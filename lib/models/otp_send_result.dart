class OtpSendResult {
  const OtpSendResult({this.devOtp, this.expiresIn = 300});

  final String? devOtp;
  final int expiresIn;

  factory OtpSendResult.fromJson(dynamic raw) {
    if (raw is! Map) return const OtpSendResult();
    final json = raw.cast<String, dynamic>();
    return OtpSendResult(
      devOtp: json['dev_otp'] as String?,
      expiresIn: int.tryParse('${json['expires_in'] ?? 300}') ?? 300,
    );
  }
}
