import 'package:dio/dio.dart';

import '../constants/env.dart';
import '../storage/secure_store.dart';
import 'api_exception.dart';
import 'api_response.dart';
import 'token_interceptor.dart';

class ApiClient {
  ApiClient({
    required SecureStore store,
    Dio? dio,
  }) : _dio = dio ??
            Dio(
              BaseOptions(
                baseUrl: Env.apiBaseUrl,
                connectTimeout: const Duration(seconds: 20),
                receiveTimeout: const Duration(seconds: 20),
                sendTimeout: const Duration(seconds: 20),
                headers: const {'Accept': 'application/json'},
              ),
            ) {
    _dio.interceptors.add(TokenInterceptor(store));
  }

  final Dio _dio;

  Future<ApiResponse<T>> get<T>(
    String path, {
    Map<String, dynamic>? query,
    T Function(dynamic json)? parse,
  }) {
    return _send(
      () => _dio.get<dynamic>(
        path,
        queryParameters: query,
        options: Options(contentType: Headers.jsonContentType),
      ),
      parse,
    );
  }

  Future<ApiResponse<T>> post<T>(
    String path, {
    Object? body,
    T Function(dynamic json)? parse,
  }) {
    return _send(
      () => _dio.post<dynamic>(
        path,
        data: body,
        options: Options(contentType: Headers.jsonContentType),
      ),
      parse,
    );
  }

  Future<ApiResponse<T>> put<T>(
    String path, {
    Object? body,
    T Function(dynamic json)? parse,
  }) {
    return _send(
      () => _dio.put<dynamic>(
        path,
        data: body,
        options: Options(
          contentType: Headers.jsonContentType,
          headers: const {'X-HTTP-Method-Override': 'PUT'},
        ),
      ),
      parse,
    );
  }

  Future<ApiResponse<T>> upload<T>(
    String path, {
    required FormData data,
    T Function(dynamic json)? parse,
  }) {
    return _send(
      () => _dio.post<dynamic>(
        path,
        data: data,
        options: Options(headers: {'Accept': 'application/json'}),
      ),
      parse,
    );
  }

  Future<ApiResponse<T>> _send<T>(
    Future<Response<dynamic>> Function() request,
    T Function(dynamic json)? parse,
  ) async {
    try {
      final response = await request();
      final payload = response.data;
      if (payload is Map<String, dynamic>) {
        return ApiResponse.fromJson(payload, parse);
      }
      return ApiResponse<T>(status: true, message: 'ok', data: payload as T?);
    } on DioException catch (e) {
      throw _mapDio(e);
    }
  }

  ApiException _mapDio(DioException e) {
    final data = e.response?.data;
    String message = 'Something went wrong. Please try again.';
    if (e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.connectionTimeout) {
      message = 'No internet connection.';
    } else if (data is Map && data['message'] is String) {
      message = data['message'] as String;
    } else if (data is String && data.contains('<html')) {
      message = 'Server is unavailable. Try again.';
    } else if (e.message != null && e.message!.isNotEmpty) {
      message = e.message!;
    }
    return ApiException(
      message: message,
      statusCode: e.response?.statusCode,
      data: data,
    );
  }
}
