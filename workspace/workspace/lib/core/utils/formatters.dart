import 'package:intl/intl.dart';

class Formatters {
  Formatters._();

  static final DateFormat _date = DateFormat('dd MMM yyyy');
  static final DateFormat _dateTime = DateFormat('dd MMM yyyy, hh:mm a');

  static String memberId(int id) => 'GYM${id.toString().padLeft(6, '0')}';

  static String rupees(num amount) => '₹${amount.toStringAsFixed(0)}';

  static String currency(num amount) => '₹${amount.toStringAsFixed(2)}';

  static String date(DateTime value) => _date.format(value);

  static String dateTime(DateTime value) => _dateTime.format(value);

  static String daysLeft(DateTime end) {
    final d = end.difference(DateTime.now()).inDays;
    if (d < 0) return 'Expired';
    if (d == 0) return 'Expires today';
    return '$d day${d == 1 ? '' : 's'} left';
  }
}
