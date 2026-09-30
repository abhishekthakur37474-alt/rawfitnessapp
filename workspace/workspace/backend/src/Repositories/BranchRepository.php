<?php

namespace App\Repositories;

use App\Support\Media;
use PDO;

final class BranchRepository
{
    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public function all(bool $activeOnly = true, ?string $search = null): array
    {
        $sql = 'SELECT * FROM branches';
        $where = [];
        $vals = [];
        if ($activeOnly) {
            $where[] = 'is_active = 1';
        }
        if ($search !== null && $search !== '') {
            $where[] = '(name LIKE ? OR city LIKE ? OR address LIKE ?)';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY is_active DESC, name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM branches WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO branches (name, address, city, phone, whatsapp, email, lat, lng, description, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['address'],
            $data['city'],
            $data['phone'],
            $data['whatsapp'],
            $data['email'],
            $data['lat'],
            $data['lng'],
            $data['description'] ?? null,
            $data['is_active'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE branches SET name = ?, address = ?, city = ?, phone = ?, whatsapp = ?,
             email = ?, lat = ?, lng = ?, description = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['address'],
            $data['city'],
            $data['phone'],
            $data['whatsapp'],
            $data['email'],
            $data['lat'],
            $data['lng'],
            $data['description'] ?? null,
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM branch_timings WHERE branch_id = ?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM branch_photos WHERE branch_id = ?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM branch_facilities WHERE branch_id = ?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM parking_info WHERE branch_id = ?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM branches WHERE id = ?')->execute([$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->pdo->prepare('UPDATE branches SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    }

    public function replaceTimings(int $branchId, array $timings): void
    {
        $this->pdo->prepare('DELETE FROM branch_timings WHERE branch_id = ?')->execute([$branchId]);
        $ins = $this->pdo->prepare(
            'INSERT INTO branch_timings (branch_id, day_of_week, open_time, close_time, is_closed)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($timings as $row) {
            $day = (int) ($row['day_of_week'] ?? -1);
            if ($day < 0 || $day > 6) {
                continue;
            }
            $closed = !empty($row['is_closed']);
            $ins->execute([
                $branchId,
                $day,
                $closed ? null : ($row['open_time'] ?: null),
                $closed ? null : ($row['close_time'] ?: null),
                $closed ? 1 : 0,
            ]);
        }
    }

    public function timings(int $branchId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM branch_timings WHERE branch_id = ? ORDER BY day_of_week ASC'
        );
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    public function photos(int $branchId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM branch_photos WHERE branch_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    public function addPhoto(int $branchId, string $image): void
    {
        $order = (int) $this->pdo->query(
            'SELECT COALESCE(MAX(sort_order), 0) FROM branch_photos WHERE branch_id = ' . (int) $branchId
        )->fetchColumn();
        $stmt = $this->pdo->prepare(
            'INSERT INTO branch_photos (branch_id, image, sort_order) VALUES (?, ?, ?)'
        );
        $stmt->execute([$branchId, $image, $order + 1]);
    }

    public function deletePhoto(int $id): void
    {
        $this->pdo->prepare('DELETE FROM branch_photos WHERE id = ?')->execute([$id]);
    }

    public function facilities(int $branchId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.* FROM facilities f
             INNER JOIN branch_facilities bf ON bf.facility_id = f.id
             WHERE bf.branch_id = ? AND f.is_active = 1
             ORDER BY f.name ASC'
        );
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    public function facilityIds(int $branchId): array
    {
        $stmt = $this->pdo->prepare('SELECT facility_id FROM branch_facilities WHERE branch_id = ?');
        $stmt->execute([$branchId]);
        return array_map(static fn (array $r): int => (int) $r['facility_id'], $stmt->fetchAll());
    }

    public function replaceFacilities(int $branchId, array $ids): void
    {
        $this->pdo->prepare('DELETE FROM branch_facilities WHERE branch_id = ?')->execute([$branchId]);
        $ins = $this->pdo->prepare(
            'INSERT INTO branch_facilities (branch_id, facility_id) VALUES (?, ?)'
        );
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ins->execute([$branchId, $id]);
            }
        }
    }

    public function parking(int $branchId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM parking_info WHERE branch_id = ? LIMIT 1');
        $stmt->execute([$branchId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsertParking(int $branchId, array $data): void
    {
        $existing = $this->parking($branchId);
        if ($existing) {
            $stmt = $this->pdo->prepare(
                'UPDATE parking_info SET has_parking = ?, two_wheeler = ?, four_wheeler = ?, notes = ?
                 WHERE branch_id = ?'
            );
            $stmt->execute([
                $data['has_parking'],
                $data['two_wheeler'],
                $data['four_wheeler'],
                $data['notes'],
                $branchId,
            ]);
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO parking_info (branch_id, has_parking, two_wheeler, four_wheeler, notes)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $branchId,
            $data['has_parking'],
            $data['two_wheeler'],
            $data['four_wheeler'],
            $data['notes'],
        ]);
    }

    public function public(array $row, string $baseUrl): array
    {
        $id = (int) $row['id'];
        $photos = $this->photos($id);
        $cover = $photos[0]['image'] ?? null;
        return [
            'id' => $id,
            'name' => $row['name'],
            'address' => $row['address'],
            'city' => $row['city'],
            'phone' => $row['phone'],
            'whatsapp' => $row['whatsapp'],
            'email' => $row['email'],
            'lat' => $row['lat'] !== null ? (float) $row['lat'] : null,
            'lng' => $row['lng'] !== null ? (float) $row['lng'] : null,
            'description' => $row['description'] ?? null,
            'is_active' => (int) $row['is_active'] === 1,
            'cover_url' => Media::url($cover, $baseUrl),
        ];
    }

    public function detail(array $row, string $baseUrl): array
    {
        $id = (int) $row['id'];
        $timings = [];
        $byDay = [];
        foreach ($this->timings($id) as $t) {
            $byDay[(int) $t['day_of_week']] = $t;
        }
        foreach (self::DAYS as $day => $label) {
            $t = $byDay[$day] ?? null;
            $timings[] = [
                'day_of_week' => $day,
                'day' => $label,
                'open_time' => $t['open_time'] ?? null,
                'close_time' => $t['close_time'] ?? null,
                'is_closed' => $t ? (int) $t['is_closed'] === 1 : false,
            ];
        }
        $parking = $this->parking($id);
        return array_merge($this->public($row, $baseUrl), [
            'timings' => $timings,
            'facilities' => array_map(
                static fn (array $f): array => [
                    'id' => (int) $f['id'],
                    'name' => $f['name'],
                    'icon' => $f['icon'],
                ],
                $this->facilities($id)
            ),
            'parking' => $parking ? [
                'has_parking' => (int) $parking['has_parking'] === 1,
                'two_wheeler' => (int) $parking['two_wheeler'] === 1,
                'four_wheeler' => (int) $parking['four_wheeler'] === 1,
                'notes' => $parking['notes'],
            ] : null,
            'photos' => array_map(
                static fn (array $p): array => [
                    'id' => (int) $p['id'],
                    'image_url' => Media::url($p['image'], $baseUrl),
                ],
                $this->photos($id)
            ),
        ]);
    }
}
