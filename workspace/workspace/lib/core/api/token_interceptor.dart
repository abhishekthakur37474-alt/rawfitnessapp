import 'package:dio/dio.dart';

import '../constants/storage_keys.dart';
import '../storage/secure_store.dart';

class TokenInterceptor extends Interceptor {
  TokenInterceptor(this._store);

  final SecureStore _store;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await _store.read(StorageKeys.accessToken);
    if (token != null && token.isNotEmpty) {
      options.headers['Authorization'] = 'Bearer $token';
      options.headers['X-Auth-Token'] = token;
    }
    handler.next(options);
  }
}
