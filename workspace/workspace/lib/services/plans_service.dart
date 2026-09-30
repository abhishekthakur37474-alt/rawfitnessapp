import '../core/api/api_client.dart';
import '../core/constants/api_endpoints.dart';
import '../models/diet_plan.dart';
import '../models/workout_plan.dart';

class PlansListResult<T> {
  const PlansListResult({required this.plans, required this.categories});

  final List<T> plans;
  final List<String> categories;
}

class PlansService {
  PlansService(this._api);

  final ApiClient _api;

  Future<PlansListResult<WorkoutPlan>> workoutPlans({String? category}) {
    return _list(
      ApiEndpoints.workoutPlans,
      category: category,
      key: 'plans',
      parseItem: (json) => WorkoutPlan.fromJson(json),
    );
  }

  Future<WorkoutPlan> workoutPlan(int id) async {
    final res = await _api.get<WorkoutPlan>(
      ApiEndpoints.workoutPlan(id),
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        final plan = map['plan'];
        return WorkoutPlan.fromJson(
          plan is Map ? plan.cast<String, dynamic>() : map,
        );
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load workout plan' : res.message);
    }
    return res.data!;
  }

  Future<PlansListResult<DietPlan>> dietPlans({String? category}) {
    return _list(
      ApiEndpoints.dietPlans,
      category: category,
      key: 'plans',
      parseItem: (json) => DietPlan.fromJson(json),
    );
  }

  Future<DietPlan> dietPlan(int id) async {
    final res = await _api.get<DietPlan>(
      ApiEndpoints.dietPlan(id),
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        final plan = map['plan'];
        return DietPlan.fromJson(
          plan is Map ? plan.cast<String, dynamic>() : map,
        );
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load diet plan' : res.message);
    }
    return res.data!;
  }

  Future<PlansListResult<T>> _list<T>(
    String path, {
    String? category,
    required String key,
    required T Function(Map<String, dynamic> json) parseItem,
  }) async {
    final res = await _api.get<PlansListResult<T>>(
      path,
      query: {
        if (category != null && category.trim().isNotEmpty) 'category': category.trim(),
      },
      parse: (raw) {
        final map = (raw as Map).cast<String, dynamic>();
        final list = (map[key] as List?) ?? const [];
        final cats = (map['categories'] as List?) ?? const [];
        return PlansListResult<T>(
          plans: list
              .map((e) => parseItem((e as Map).cast<String, dynamic>()))
              .toList(),
          categories: cats.map((e) => '$e').where((e) => e.isNotEmpty).toList(),
        );
      },
    );
    if (!res.status || res.data == null) {
      throw Exception(res.message.isEmpty ? 'Unable to load plans' : res.message);
    }
    return res.data!;
  }
}
