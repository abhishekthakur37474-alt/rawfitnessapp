import 'package:dio/dio.dart';

import '../core/api/api_client.dart';
import '../core/constants/api_endpoints.dart';
import '../models/gov_id.dart';
import '../models/otp_send_result.dart';
import '../models/session.dart';
import '../models/user.dart';

class AuthService {
  AuthService(this._api);

  final ApiClient _api;

  Future<OtpSendResult> sendOtp(String mobile) async {
    final res = await _api.post<OtpSendResult>(
      ApiEndpoints.sendOtp,
      body: {'mobile': mobile},
      parse: OtpSendResult.fromJson,
    );
    if (!res.status) throw Exception(res.message);
    return res.data ?? const OtpSendResult();
  }

  Future<Session> verifyOtp(String mobile, String otp) async {
    final res = await _api.post<Session>(
      ApiEndpoints.verifyOtp,
      body: {'mobile': mobile, 'otp': otp},
      parse: (raw) => Session.fromJson((raw as Map).cast<String, dynamic>()),
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'OTP verification failed' : res.message);
    }
    return res.data!;
  }

  Future<User> getProfile() async {
    final res = await _api.get<User>(
      ApiEndpoints.profile,
      parse: (raw) => User.fromJson((raw as Map).cast<String, dynamic>()),
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load profile' : res.message);
    }
    return res.data!;
  }

  Future<User> updateProfile({
    String? name,
    String? gender,
    double? heightCm,
  }) async {
    final body = <String, dynamic>{};
    if (name != null) body['name'] = name;
    if (gender != null) body['gender'] = gender;
    if (heightCm != null) body['height_cm'] = heightCm;
    final res = await _api.post<User>(
      ApiEndpoints.profileUpdate,
      body: body,
      parse: (raw) => User.fromJson((raw as Map).cast<String, dynamic>()),
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to update profile' : res.message);
    }
    return res.data!;
  }

  Future<User> submitOnboarding({
    required String name,
    required String gender,
    required double heightCm,
  }) async {
    final res = await _api.post<User>(
      ApiEndpoints.onboarding,
      body: {
        'name': name,
        'gender': gender,
        'height_cm': heightCm,
      },
      parse: (raw) => User.fromJson((raw as Map).cast<String, dynamic>()),
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to save onboarding' : res.message);
    }
    return res.data!;
  }

  Future<GovId> uploadGovId({
    required String type,
    required String number,
    required String imagePath,
  }) async {
    final data = FormData.fromMap({
      'type': type,
      'number': number,
      'image': await MultipartFile.fromFile(imagePath, filename: 'gov-id.jpg'),
    });
    final res = await _api.upload<GovId>(
      ApiEndpoints.govId,
      data: data,
      parse: (raw) => GovId.fromJson((raw as Map).cast<String, dynamic>()),
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to upload ID' : res.message);
    }
    return res.data!;
  }
}
