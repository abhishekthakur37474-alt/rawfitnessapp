class WorkoutPlan {
  const WorkoutPlan({
    required this.id,
    required this.title,
    this.imageUrl,
    this.category,
    this.level = 'all',
    this.description,
    this.isActive = true,
    this.dayCount,
    this.exerciseCount,
    this.days = const [],
  });

  final int id;
  final String title;
  final String? imageUrl;
  final String? category;
  final String level;
  final String? description;
  final bool isActive;
  final int? dayCount;
  final int? exerciseCount;
  final List<WorkoutDay> days;

  String get levelLabel {
    if (level.isEmpty || level == 'all') return 'All levels';
    return '${level[0].toUpperCase()}${level.substring(1)}';
  }

  String get metaLabel {
    final days = dayCount ?? this.days.length;
    if (days <= 0) return levelLabel;
    return '$levelLabel · $days day${days == 1 ? '' : 's'}';
  }

  factory WorkoutPlan.fromJson(Map<String, dynamic> json) {
    final daysRaw = (json['days'] as List?) ?? const [];
    return WorkoutPlan(
      id: _asInt(json['id']),
      title: (json['title'] as String?) ?? '',
      imageUrl: json['image_url'] as String?,
      category: json['category'] as String?,
      level: (json['level'] as String?) ?? 'all',
      description: json['description'] as String?,
      isActive: json['is_active'] == true || json['is_active'] == 1,
      dayCount: json['day_count'] == null ? null : _asInt(json['day_count']),
      exerciseCount:
          json['exercise_count'] == null ? null : _asInt(json['exercise_count']),
      days: daysRaw
          .map((e) => WorkoutDay.fromJson((e as Map).cast<String, dynamic>()))
          .toList(),
    );
  }

  static int _asInt(dynamic v) {
    if (v is int) return v;
    return int.tryParse('$v') ?? 0;
  }
}

class WorkoutDay {
  const WorkoutDay({
    required this.id,
    required this.dayNumber,
    this.title,
    this.notes,
    this.exercises = const [],
  });

  final int id;
  final int dayNumber;
  final String? title;
  final String? notes;
  final List<WorkoutExercise> exercises;

  String get displayTitle {
    final name = (title ?? '').trim();
    if (name.isNotEmpty) return name;
    return 'Day $dayNumber';
  }

  factory WorkoutDay.fromJson(Map<String, dynamic> json) {
    final list = (json['exercises'] as List?) ?? const [];
    return WorkoutDay(
      id: WorkoutPlan._asInt(json['id']),
      dayNumber: WorkoutPlan._asInt(json['day_number'] ?? json['id']),
      title: json['title'] as String?,
      notes: json['notes'] as String?,
      exercises: list
          .map(
            (e) => WorkoutExercise.fromJson((e as Map).cast<String, dynamic>()),
          )
          .toList(),
    );
  }
}

class WorkoutExercise {
  const WorkoutExercise({
    required this.id,
    required this.name,
    this.sets,
    this.reps,
    this.rest,
    this.notes,
    this.imageUrl,
  });

  final int id;
  final String name;
  final String? sets;
  final String? reps;
  final String? rest;
  final String? notes;
  final String? imageUrl;

  factory WorkoutExercise.fromJson(Map<String, dynamic> json) {
    return WorkoutExercise(
      id: WorkoutPlan._asInt(json['id']),
      name: (json['name'] as String?) ?? '',
      sets: json['sets'] as String?,
      reps: json['reps'] as String?,
      rest: json['rest'] as String?,
      notes: json['notes'] as String?,
      imageUrl: json['image_url'] as String?,
    );
  }
}
