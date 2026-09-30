class OtpService {
  const OtpService();

  bool isComplete(String value) =>
      value.length == 6 && int.tryParse(value) != null;
}
