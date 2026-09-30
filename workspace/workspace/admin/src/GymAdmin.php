<?php

final class GymAdmin
{
    public static function parseTimings(): array
    {
        $input = $_POST['timings'] ?? [];
        $out = [];
        if (!is_array($input)) {
            return $out;
        }
        foreach ($input as $day => $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'day_of_week' => (int) $day,
                'open_time' => trim((string) ($row['open_time'] ?? '')),
                'close_time' => trim((string) ($row['close_time'] ?? '')),
                'is_closed' => !empty($row['is_closed']),
            ];
        }
        return $out;
    }

    public static function parseCerts(): array
    {
        $input = $_POST['certs'] ?? [];
        $out = [];
        if (!is_array($input)) {
            return $out;
        }
        foreach ($input as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $title = trim((string) ($raw['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'issuer' => trim((string) ($raw['issuer'] ?? '')),
                'year' => trim((string) ($raw['year'] ?? '')),
            ];
        }
        return $out;
    }

    public static function branchPayload(): array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $lat = trim((string) ($_POST['lat'] ?? ''));
        $lng = trim((string) ($_POST['lng'] ?? ''));
        return [
            'name' => $name,
            'address' => trim((string) ($_POST['address'] ?? '')) ?: null,
            'city' => trim((string) ($_POST['city'] ?? '')) ?: null,
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'whatsapp' => trim((string) ($_POST['whatsapp'] ?? '')) ?: null,
            'email' => trim((string) ($_POST['email'] ?? '')) ?: null,
            'lat' => $lat !== '' && is_numeric($lat) ? $lat : null,
            'lng' => $lng !== '' && is_numeric($lng) ? $lng : null,
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ];
    }

    public static function parkingPayload(): array
    {
        return [
            'has_parking' => (int) ($_POST['has_parking'] ?? 0) === 1 ? 1 : 0,
            'two_wheeler' => (int) ($_POST['two_wheeler'] ?? 0) === 1 ? 1 : 0,
            'four_wheeler' => (int) ($_POST['four_wheeler'] ?? 0) === 1 ? 1 : 0,
            'notes' => trim((string) ($_POST['parking_notes'] ?? '')) ?: null,
        ];
    }

    public static function nullableBranchId(): ?int
    {
        $v = $_POST['branch_id'] ?? '';
        return $v !== '' ? (int) $v : null;
    }

    public static function datetimeOrNull(string $key): ?string
    {
        $v = trim((string) ($_POST[$key] ?? ''));
        if ($v === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($v))->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }
}
