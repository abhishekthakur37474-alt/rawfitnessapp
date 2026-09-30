import 'package:connectivity_plus/connectivity_plus.dart';

class NetworkGuard {
  NetworkGuard({Connectivity? connectivity})
      : _connectivity = connectivity ?? Connectivity();

  final Connectivity _connectivity;

  Future<bool> get hasConnection async {
    final results = await _connectivity.checkConnectivity();
    return results.any((r) => r != ConnectivityResult.none);
  }
}
