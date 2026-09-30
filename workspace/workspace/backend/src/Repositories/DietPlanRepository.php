<?php

namespace App\Repositories;

use App\Support\Media;
use PDO;

final class DietPlanRepository
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
        $sql = 'SELECT dp.*,
                   (SELECT COUNT(*) FROM diet_meals m WHERE m.plan_id = dp.id) AS meal_count
                FROM diet_plans dp';
        [$where, $vals] = $this->filters($activeOnly, $category, $search);
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY dp.is_active DESC, dp.updated_at DESC, dp.id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function countAll(bool $activeOnly = true, ?string $category = null, ?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM diet_plans dp';
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
        $sql = "SELECT DISTINCT category FROM diet_plans
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

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM diet_plans WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO diet_plans (title, image, category, calories, description, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['image'],
            $data['category'],
            $data['calories'],
            $data['description'],
            $data['is_active'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE diet_plans SET title = ?, image = ?, category = ?, calories = ?,
             description = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['image'],
            $data['category'],
            $data['calories'],
            $data['description'],
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $del = $this->pdo->prepare('DELETE FROM diet_meals WHERE plan_id = ?');
        $del->execute([$id]);
        $stmt = $this->pdo->prepare('DELETE FROM diet_plans WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE diet_plans SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    public function replaceMeals(int $planId, array $meals): void
    {
        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare('DELETE FROM diet_meals WHERE plan_id = ?');
            $del->execute([$planId]);
            $insert = $this->pdo->prepare(
                'INSERT INTO diet_meals (plan_id, meal_type, items, calories, meal_time, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $order = 0;
            foreach ($meals as $meal) {
                $insert->execute([
                    $planId,
                    $meal['meal_type'],
                    $meal['items'] ?? null,
                    $meal['calories'] ?? null,
                    ($meal['time'] ?? '') !== '' ? $meal['time'] : null,
                    $order++,
                ]);
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
            'calories' => $row['calories'] !== null ? (int) $row['calories'] : null,
            'description' => $row['description'],
            'is_active' => (int) $row['is_active'] === 1,
            'meal_count' => isset($row['meal_count']) ? (int) $row['meal_count'] : null,
        ];
    }

    public function detail(array $row, string $baseUrl): array
    {
        $meals = $this->mealsFor((int) $row['id']);
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'image_url' => Media::url($row['image'] ?? null, $baseUrl),
            'category' => $row['category'],
            'calories' => $row['calories'] !== null ? (int) $row['calories'] : null,
            'description' => $row['description'],
            'is_active' => (int) $row['is_active'] === 1,
            'meal_count' => count($meals),
            'meals' => array_map(fn (array $m): array => $this->publicMeal($m), $meals),
        ];
    }

    public function mealsFor(int $planId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM diet_meals WHERE plan_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$planId]);
        return $stmt->fetchAll();
    }

    private function publicMeal(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'meal_type' => $row['meal_type'],
            'items' => $row['items'],
            'calories' => $row['calories'] !== null ? (int) $row['calories'] : null,
            'time' => $row['meal_time'],
        ];
    }

    private function filters(bool $activeOnly, ?string $category, ?string $search): array
    {
        $where = [];
        $vals = [];
        if ($activeOnly) {
            $where[] = 'dp.is_active = 1';
        }
        if ($category !== null && $category !== '') {
            $where[] = 'dp.category = ?';
            $vals[] = $category;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(dp.title LIKE ? OR dp.category LIKE ?)';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
        }
        return [$where, $vals];
    }
}
