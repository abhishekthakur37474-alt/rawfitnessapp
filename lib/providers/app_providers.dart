import 'package:provider/provider.dart';
import 'package:provider/single_child_widget.dart';

import '../core/api/api_client.dart';
import '../core/storage/secure_store.dart';
import '../services/auth_service.dart';
import '../services/onesignal_service.dart';
import 'auth_provider.dart';

List<SingleChildWidget> buildAppProviders() {
  final store = SecureStore();
  final api = ApiClient(store: store);
  final oneSignal = OneSignalService(api, store);
  final auth = AuthService(api);

  return [
    Provider<SecureStore>.value(value: store),
    Provider<ApiClient>.value(value: api),
    Provider<OneSignalService>.value(value: oneSignal),
    Provider<AuthService>.value(value: auth),
    ChangeNotifierProvider<AuthProvider>(
      create: (_) => AuthProvider(
        authService: auth,
        store: store,
        oneSignal: oneSignal,
      )..bootstrap(),
    ),
  ];
}
