class DietPlan {
  const DietPlan({
    required this.id,
    required this.title,
    this.imageUrl,
    this.category,
    this.calories,
    this.description,
    this.isActive = true,
    this.mealCount,
    this.meals = const [],
  });

  final int id;
  final String title;
  final String? imageUrl;
  final String? category;
  final int? calories;
  final String? description;
  final bool isActive;
  final int? mealCount;
  final List<DietMeal> meals;

  String get caloriesLabel {
    if (calories == null || calories! <= 0) return '';
    return '$calories kcal';
  }

  String get metaLabel {
    final meals = mealCount ?? this.meals.length;
    final parts = <String>[];
    if (caloriesLabel.isNotEmpty) parts.add(caloriesLabel);
    if (meals > 0) parts.add('$meals meal${meals == 1 ? '' : 's'}');
    return parts.join(' · ');
  }

  factory DietPlan.fromJson(Map<String, dynamic> json) {
    final mealsRaw = (json['meals'] as List?) ?? const [];
    return DietPlan(
      id: _asInt(json['id']),
      title: (json['title'] as String?) ?? '',
      imageUrl: json['image_url'] as String?,
      category: json['category'] as String?,
      calories: json['calories'] == null ? null : _asInt(json['calories']),
      description: json['description'] as String?,
      isActive: json['is_active'] == true || json['is_active'] == 1,
      mealCount: json['meal_count'] == null ? null : _asInt(json['meal_count']),
      meals: mealsRaw
          .map((e) => DietMeal.fromJson((e as Map).cast<String, dynamic>()))
          .toList(),
    );
  }

  static int _asInt(dynamic v) {
    if (v is int) return v;
    return int.tryParse('$v') ?? 0;
  }
}

class DietMeal {
  const DietMeal({
    required this.id,
    required this.mealType,
    this.items,
    this.calories,
    this.time,
  });

  final int id;
  final String mealType;
  final String? items;
  final int? calories;
  final String? time;

  factory DietMeal.fromJson(Map<String, dynamic> json) {
    return DietMeal(
      id: DietPlan._asInt(json['id']),
      mealType: (json['meal_type'] as String?) ?? 'Meal',
      items: json['items'] as String?,
      calories: json['calories'] == null ? null : DietPlan._asInt(json['calories']),
      time: json['time'] as String?,
    );
  }
}
