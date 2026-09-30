PHASE 3: Workout Plans + Diet Plans — COMPLETED

Build:
1. MySQL: workout_plans (title, image, category, level, description, is_active), workout_days, workout_exercises (name, sets, reps, rest, notes, image), diet_plans (title, image, category, calories, description), diet_meals (meal_type, items, calories, time).
2. Backend: GET workout-plans, GET workout-plans/{id}, GET diet-plans, GET diet-plans/{id} (with category filter).
3. Admin CRUD: Workout Plans (with days + exercises, dynamic add/remove rows, image upload) and Diet Plans (with meals), active/inactive toggle, search, pagination.
4. Flutter: Workouts bottom tab with two tabs "Workout Plans" and "Diet Plans": cards with image, category chips filter, detail screens (day-wise exercises expandable, meal-wise diet), shimmer/empty/error states.
Do not break earlier phases. End with test checklist.

## Phase 3 test checklist

- `GET /api/health` returns phase 3
- `GET /api/workout-plans` and `GET /api/diet-plans` return active plans plus categories; `?category=` filters the list
- `GET /api/workout-plans/{id}` returns days with expandable exercises (name, sets, reps, rest, notes, image)
- `GET /api/diet-plans/{id}` returns meals (meal_type, items, calories, time)
- Inactive plans are hidden from the app API but remain in admin
- Admin: create/edit/delete workout plans with cover image upload, dynamic days and exercises, search, pagination, active/inactive toggle
- Admin: create/edit/delete diet plans with cover image upload, dynamic meals, search, pagination, active/inactive toggle
- Flutter Workouts tab has "Workout Plans" and "Diet Plans" sub-tabs
- Plan cards show image, title, category, and meta; category chips filter the list
- Workout detail: day-wise expandable exercises; Diet detail: meal-wise cards
- Shimmer while loading, empty state when none, error state with retry
- Phase 1 and Phase 2 flows still work (auth, onboarding, membership, payments UI)
