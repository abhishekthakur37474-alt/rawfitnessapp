class Formatters {
  Formatters._();

  static String memberId(int id) =>
      'GYM${id.toString().padLeft(6, '0')}';

  static String rupees(num amount) => '₹${amount.toStringAsFixed(0)}';

  static String daysLeft(DateTime end) {
    final d = end.difference(DateTime.now()).inDays;
    if (d < 0) return 'Expired';
    if (d == 0) return 'Expires today';
    return '$d day${d == 1 ? '' : 's'} left';
  }
}
