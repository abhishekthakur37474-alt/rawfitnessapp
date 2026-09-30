import 'package:flutter_test/flutter_test.dart';
import 'package:rawfitnessapp/models/diet_plan.dart';
import 'package:rawfitnessapp/models/workout_plan.dart';

void main() {
  test('WorkoutPlan parses list and detail payloads', () {
    final plan = WorkoutPlan.fromJson({
      'id': 1,
      'title': 'Full Body',
      'image_url': 'https://example.com/w.jpg',
      'category': 'Strength',
      'level': 'beginner',
      'description': 'Three day split',
      'is_active': true,
      'day_count': 2,
      'exercise_count': 2,
      'days': [
        {
          'id': 10,
          'day_number': 1,
          'title': 'Push',
          'notes': 'Warm up first',
          'exercises': [
            {
              'id': 100,
              'name': 'Bench press',
              'sets': '4',
              'reps': '8',
              'rest': '90s',
              'notes': 'Keep scapula packed',
              'image_url': null,
            },
          ],
        },
      ],
    });

    expect(plan.title, 'Full Body');
    expect(plan.levelLabel, 'Beginner');
    expect(plan.days, hasLength(1));
    expect(plan.days.first.displayTitle, 'Push');
    expect(plan.days.first.exercises.first.name, 'Bench press');
  });

  test('DietPlan parses meals and calorie labels', () {
    final plan = DietPlan.fromJson({
      'id': 3,
      'title': 'Cut',
      'category': 'Weight Loss',
      'calories': 1800,
      'meal_count': 1,
      'meals': [
        {
          'id': 1,
          'meal_type': 'Breakfast',
          'items': 'Oats, eggs',
          'calories': 420,
          'time': '08:00',
        },
      ],
    });

    expect(plan.caloriesLabel, '1800 kcal');
    expect(plan.meals.first.mealType, 'Breakfast');
    expect(plan.meals.first.time, '08:00');
  });
}
