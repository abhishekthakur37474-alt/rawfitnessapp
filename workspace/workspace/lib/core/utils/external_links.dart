import 'package:flutter/services.dart';

class ExternalLinks {
  ExternalLinks._();

  static Future<String?> tel(String? phone) async {
    final n = _digits(phone);
    if (n.isEmpty) return null;
    return _copy('tel:+$n');
  }

  static Future<String?> whatsapp(String? phone) async {
    final n = _digits(phone);
    if (n.isEmpty) return null;
    return _copy('https://wa.me/$n');
  }

  static Future<String?> email(String? address) async {
    final v = (address ?? '').trim();
    if (v.isEmpty) return null;
    return _copy('mailto:$v');
  }

  static Future<String?> maps({double? lat, double? lng, String? query}) async {
    if (lat != null && lng != null) {
      return _copy(
        'https://www.google.com/maps/dir/?api=1&destination=$lat,$lng',
      );
    }
    final q = Uri.encodeComponent((query ?? '').trim());
    if (q.isEmpty) return null;
    return _copy('https://www.google.com/maps/search/?api=1&query=$q');
  }

  static Future<String?> url(String? value) async {
    final v = (value ?? '').trim();
    if (v.isEmpty) return null;
    return _copy(v);
  }

  static Future<String> _copy(String value) async {
    await Clipboard.setData(ClipboardData(text: value));
    return value;
  }

  static String _digits(String? v) => (v ?? '').replaceAll(RegExp(r'[^0-9]'), '');
}
