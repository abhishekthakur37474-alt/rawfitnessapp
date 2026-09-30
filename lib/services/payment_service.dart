enum PaymentStatus { comingSoon, pending, success, failed }

class PaymentService {
  const PaymentService();

  Future<PaymentStatus> initiate({
    required String method,
    required num amount,
  }) async {
    return PaymentStatus.comingSoon;
  }
}
