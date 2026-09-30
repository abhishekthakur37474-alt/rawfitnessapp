import 'package:flutter_test/flutter_test.dart';
import 'package:rawfitnessapp/core/utils/formatters.dart';
import 'package:rawfitnessapp/core/utils/validators.dart';

void main() {
  test('mobile validator', () {
    expect(Validators.mobile(null), isNotNull);
    expect(Validators.mobile('123'), isNotNull);
    expect(Validators.mobile('9876543210'), isNull);
  });

  test('otp validator', () {
    expect(Validators.otp('12'), isNotNull);
    expect(Validators.otp('123456'), isNull);
  });

  test('gov id formats', () {
    expect(Validators.govIdNumber('aadhaar', '123412341234'), isNull);
    expect(Validators.govIdNumber('pan', 'ABCDE1234F'), isNull);
    expect(Validators.govIdNumber('passport', 'A1234567'), isNull);
    expect(Validators.govIdNumber('aadhaar', '12'), isNotNull);
  });

  test('member id formatter', () {
    expect(Formatters.memberId(123), 'GYM000123');
  });
}
