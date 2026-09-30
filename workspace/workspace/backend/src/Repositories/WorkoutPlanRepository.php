<?php

namespace App\Repositories;

use App\Support\Media;
use PDO;

final class WorkoutPlanRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(
        bool $activeOnly = true,
        ?string $category = null,
        ?string $search = null,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $sql = 'SELECT wp.*,
                   (SELECT COUNT(*) FROM workout_days d WHERE d.plan_id = wp.id) AS day_count,
                   (SELECT COUNT(*) FROM workout_exercises e
                      JOIN workout_days d2 ON d2.id = e.day_id
                      WHERE d2.plan_id = wp.id) AS exercise_count
                FROM workout_plans wp';
        [$where, $vals] = $this->filters($activeOnly, $category, $search);
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY wp.is_active DESC, wp.updated_at DESC, wp.id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function countAll(bool $activeOnly = true, ?string $category = null, ?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM workout_plans wp';
        [$where, $vals] = $this->filters($activeOnly, $category, $search);
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return (int) $stmt->fetchColumn();
    }

    public function categories(bool $activeOnly = true): array
    {
        $sql = "SELECT DISTINCT category FROM workout_plans
                WHERE category IS NOT NULL AND category <> ''";
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY category ASC';
        return array_values(array_filter(array_map(
            static fn (array $r): string => (string) $r['category'],
            $this->pdo->query($sql)->fetchAll()
        )));
    }

    public function schedule(int $planId): array
    {
        $days = $this->daysFor($planId);
        $dayIds = array_map(static fn (array $d): int => (int) $d['id'], $days);
        $grouped = $this->exercisesByDay($dayIds);

        $out = [];
        foreach ($days as $day) {
            $exercises = [];
            foreach ($grouped[(int) $day['id']] ?? [] as $exercise) {
                $exercises[] = [
                    'name' => $exercise['name'],
                    'sets' => $exercise['sets'],
                    'reps' => $exercise['reps'],
                    'rest' => $exercise['rest'],
                    'notes' => $exercise['notes'],
                    'image' => $exercise['image'],
                ];
            }
            $out[] = [
                'title' => $day['title'],
                'notes' => $day['notes'],
                'exercises' => $exercises,
            ];
        }
        return $out;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM workout_plans WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO workout_plans (title, image, category, level, description, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['image'],
            $data['category'],
            $data['level'],
            $data['description'],
            $data['is_active'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE workout_plans SET title = ?, image = ?, category = ?, level = ?,
             description = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['image'],
            $data['category'],
            $data['level'],
            $data['description'],
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->deleteDays($id);
        $stmt = $this->pdo->prepare('DELETE FROM workout_plans WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE workout_plans SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    public function replaceSchedule(int $planId, array $days): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->deleteDays($planId);
            $insertDay = $this->pdo->prepare(
                'INSERT INTO workout_days (plan_id, day_number, title, notes) VALUES (?, ?, ?, ?)'
            );
            $insertEx = $this->pdo->prepare(
                'INSERT INTO workout_exercises (day_id, name, sets, reps, rest, notes, image, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $dayNumber = 0;
            foreach ($days as $day) {
                $dayNumber++;
                $insertDay->execute([
                    $planId,
                    max(1, (int) ($day['day_number'] ?? $dayNumber)),
                    ($day['title'] ?? '') !== '' ? $day['title'] : null,
                    ($day['notes'] ?? '') !== '' ? $day['notes'] : null,
                ]);
                $dayId = (int) $this->pdo->lastInsertId();
                $order = 0;
                foreach (($day['exercises'] ?? []) as $exercise) {
                    $insertEx->execute([
                        $dayId,
                        $exercise['name'],
                        $exercise['sets'] ?? null,
                        $exercise['reps'] ?? null,
                        $exercise['rest'] ?? null,
                        $exercise['notes'] ?? null,
                        ($exercise['image'] ?? '') !== '' ? $exercise['image'] : null,
                        $order++,
                    ]);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function public(array $row, string $baseUrl): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'image_url' => Media::url($row['image'] ?? null, $baseUrl),
            'category' => $row['category'],
            'level' => $row['level'],
            'description' => $row['description'],
            'is_active' => (int) $row['is_active'] === 1,
            'day_count' => isset($row['day_count']) ? (int) $row['day_count'] : null,
            'exercise_count' => isset($row['exercise_count']) ? (int) $row['exercise_count'] : null,
        ];
    }

    public function detail(array $row, string $baseUrl): array
    {
        $days = $this->daysFor((int) $row['id']);
        $dayIds = array_map(static fn (array $d): int => (int) $d['id'], $days);
        $grouped = $this->exercisesByDay($dayIds);

        $out = [];
        foreach ($days as $day) {
            $out[] = [
                'id' => (int) $day['id'],
                'day_number' => (int) $day['day_number'],
                'title' => $day['title'],
                'notes' => $day['notes'],
                'exercises' => array_map(
                    fn (array $e): array => $this->publicExercise($e, $baseUrl),
                    $grouped[(int) $day['id']] ?? []
                ),
            ];
        }

        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'image_url' => Media::url($row['image'] ?? null, $baseUrl),
            'category' => $row['category'],
            'level' => $row['level'],
            'description' => $row['description'],
            'is_active' => (int) $row['is_active'] === 1,
            'day_count' => count($out),
            'days' => $out,
        ];
    }

    private function publicExercise(array $row, string $baseUrl): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'sets' => $row['sets'],
            'reps' => $row['reps'],
            'rest' => $row['rest'],
            'notes' => $row['notes'],
            'image_url' => Media::url($row['image'] ?? null, $baseUrl),
        ];
    }

    private function daysFor(int $planId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM workout_days WHERE plan_id = ? ORDER BY day_number ASC, id ASC'
        );
        $stmt->execute([$planId]);
        return $stmt->fetchAll();
    }

    private function exercisesByDay(array $dayIds): array
    {
        if ($dayIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($dayIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM workout_exercises WHERE day_id IN ($in) ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute($dayIds);
        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['day_id']][] = $row;
        }
        return $grouped;
    }

    private function deleteDays(int $planId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM workout_days WHERE plan_id = ?');
        $stmt->execute([$planId]);
        $ids = array_map(static fn (array $r): int => (int) $r['id'], $stmt->fetchAll());
        if ($ids === []) {
            return;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $del = $this->pdo->prepare("DELETE FROM workout_exercises WHERE day_id IN ($in)");
        $del->execute($ids);
        $delDays = $this->pdo->prepare('DELETE FROM workout_days WHERE plan_id = ?');
        $delDays->execute([$planId]);
    }

    private function filters(bool $activeOnly, ?string $category, ?string $search): array
    {
        $where = [];
        $vals = [];
        if ($activeOnly) {
            $where[] = 'wp.is_active = 1';
        }
        if ($category !== null && $category !== '') {
            $where[] = 'wp.category = ?';
            $vals[] = $category;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(wp.title LIKE ? OR wp.category LIKE ?)';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
        }
        return [$where, $vals];
    }
}
