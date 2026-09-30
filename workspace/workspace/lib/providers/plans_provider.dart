import 'package:flutter/foundation.dart';

import '../models/diet_plan.dart';
import '../models/workout_plan.dart';
import '../services/plans_service.dart';

class PlansProvider extends ChangeNotifier {
  PlansProvider(this._service);

  final PlansService _service;

  List<WorkoutPlan> workouts = [];
  List<DietPlan> diets = [];
  List<String> workoutCategories = [];
  List<String> dietCategories = [];

  String? workoutCategory;
  String? dietCategory;

  bool loadingWorkouts = false;
  bool loadingDiets = false;

  String? workoutsError;
  String? dietsError;

  PlansService get service => _service;

  List<WorkoutPlan> get filteredWorkouts {
    final cat = workoutCategory;
    if (cat == null || cat.isEmpty) return workouts;
    return workouts.where((p) => p.category == cat).toList();
  }

  List<DietPlan> get filteredDiets {
    final cat = dietCategory;
    if (cat == null || cat.isEmpty) return diets;
    return diets.where((p) => p.category == cat).toList();
  }

  Future<void> loadWorkouts({bool force = false}) async {
    if (workouts.isNotEmpty && !force) return;
    loadingWorkouts = true;
    workoutsError = null;
    notifyListeners();
    try {
      final result = await _service.workoutPlans();
      workouts = result.plans;
      workoutCategories = result.categories;
    } catch (e) {
      workoutsError = _clean(e);
    } finally {
      loadingWorkouts = false;
      notifyListeners();
    }
  }

  Future<void> loadDiets({bool force = false}) async {
    if (diets.isNotEmpty && !force) return;
    loadingDiets = true;
    dietsError = null;
    notifyListeners();
    try {
      final result = await _service.dietPlans();
      diets = result.plans;
      dietCategories = result.categories;
    } catch (e) {
      dietsError = _clean(e);
    } finally {
      loadingDiets = false;
      notifyListeners();
    }
  }

  void setWorkoutCategory(String? category) {
    final next = (category == null || category.isEmpty) ? null : category;
    if (workoutCategory == next) return;
    workoutCategory = next;
    notifyListeners();
  }

  void setDietCategory(String? category) {
    final next = (category == null || category.isEmpty) ? null : category;
    if (dietCategory == next) return;
    dietCategory = next;
    notifyListeners();
  }

  void reset() {
    workouts = [];
    diets = [];
    workoutCategories = [];
    dietCategories = [];
    workoutCategory = null;
    dietCategory = null;
    workoutsError = null;
    dietsError = null;
    notifyListeners();
  }

  String _clean(Object e) => e.toString().replaceFirst('Exception: ', '');
}
