PHASE 3: Workout Plans + Diet Plans

Build:
1. MySQL: workout_plans (title, image, category, level, description, is_active), workout_days, workout_exercises (name, sets, reps, rest, notes, image), diet_plans (title, image, category, calories, description), diet_meals (meal_type, items, calories, time).
2. Backend: GET workout-plans, GET workout-plans/{id}, GET diet-plans, GET diet-plans/{id} (with category filter).
3. Admin CRUD: Workout Plans (with days + exercises, dynamic add/remove rows, image upload) and Diet Plans (with meals), active/inactive toggle, search, pagination.
4. Flutter: Workouts bottom tab with two tabs "Workout Plans" and "Diet Plans": cards with image, category chips filter, detail screens (day-wise exercises expandable, meal-wise diet), shimmer/empty/error states.
Do not break earlier phases. End with test checklist.