class Validators {
  Validators._();

  static String? mobile(String? value) {
    final v = (value ?? '').trim();
    if (v.isEmpty) return 'Mobile number is required';
    if (!RegExp(r'^[6-9]\d{9}$').hasMatch(v)) {
      return 'Enter a valid 10-digit mobile number';
    }
    return null;
  }

  static String? otp(String? value) {
    final v = (value ?? '').trim();
    if (v.length != 6 || int.tryParse(v) == null) {
      return 'Enter the 6-digit OTP';
    }
    return null;
  }

  static String? required(String? value, {String label = 'This field'}) {
    if ((value ?? '').trim().isEmpty) return '$label is required';
    return null;
  }

  static String? heightCm(String? value) {
    final n = double.tryParse((value ?? '').trim());
    if (n == null || n < 80 || n > 250) return 'Enter height between 80 and 250 cm';
    return null;
  }

  static String? govIdNumber(String type, String? value) {
    final v = (value ?? '').trim().toUpperCase();
    if (v.isEmpty) return 'ID number is required';
    switch (type) {
      case 'aadhaar':
        if (!RegExp(r'^\d{12}$').hasMatch(v)) return 'Aadhaar must be 12 digits';
        break;
      case 'pan':
        if (!RegExp(r'^[A-Z]{5}\d{4}[A-Z]$').hasMatch(v)) {
          return 'Enter a valid PAN (ABCDE1234F)';
        }
        break;
      case 'passport':
        if (!RegExp(r'^[A-Z][0-9]{7}$').hasMatch(v)) {
          return 'Enter a valid passport number';
        }
        break;
      case 'driving_license':
        if (v.length < 8 || v.length > 20) {
          return 'Enter a valid driving license number';
        }
        break;
    }
    return null;
  }
}
