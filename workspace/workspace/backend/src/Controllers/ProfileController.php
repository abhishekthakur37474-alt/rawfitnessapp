<?php

namespace App\Controllers;

use App\Repositories\GovIdRepository;
use App\Repositories\UserRepository;
use App\Support\Json;
use App\Support\Validator;
use PDO;

final class ProfileController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private int $userId,
    ) {
    }

    public function show(): never
    {
        Json::ok('ok', $this->payload());
    }

    public function update(): never
    {
        $body = Json::body();
        $fields = [];
        if (array_key_exists('name', $body)) {
            $fields['name'] = Validator::name($body['name']);
        }
        if (array_key_exists('gender', $body)) {
            $fields['gender'] = Validator::gender($body['gender']);
        }
        if (array_key_exists('height_cm', $body)) {
            $fields['height_cm'] = Validator::heightCm($body['height_cm']);
        }
        if ($fields === []) {
            Json::fail('No profile fields to update', 422);
        }
        (new UserRepository($this->pdo))->updateProfile($this->userId, $fields);
        Json::ok('Profile updated', $this->payload());
    }

    public function onboarding(): never
    {
        $body = Json::body();
        $fields = [
            'name' => Validator::name($body['name'] ?? ''),
            'gender' => Validator::gender($body['gender'] ?? ''),
            'height_cm' => Validator::heightCm($body['height_cm'] ?? null),
        ];
        (new UserRepository($this->pdo))->updateProfile($this->userId, $fields);
        $this->refreshOnboarded();
        Json::ok('Onboarding saved', $this->payload());
    }

    public function govIdShow(): never
    {
        $gov = (new GovIdRepository($this->pdo))->latestForUser($this->userId);
        if (!$gov) {
            Json::ok('No government ID uploaded', null);
        }
        Json::ok('ok', (new GovIdRepository($this->pdo))->public($gov, $this->config['base_url']));
    }

    public function govIdStore(): never
    {
        $type = Validator::govType($_POST['type'] ?? '');
        $number = Validator::govNumber($type, $_POST['number'] ?? '');
        if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
            Json::fail('ID photo is required', 422);
        }
        $file = $_FILES['image'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            Json::fail('Unable to upload image', 422);
        }
        if (($file['size'] ?? 0) > (int) $this->config['uploads']['max_bytes']) {
            Json::fail('Image must be 5MB or smaller', 422);
        }
        $mime = null;
        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: null;
        }
        $origExt = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($mime === null) {
            $mime = match ($origExt) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                default => '',
            };
        }
        if (!in_array($mime, $this->config['uploads']['allowed_mime'], true)) {
            Json::fail('Only JPG and PNG images are allowed', 422);
        }
        $ext = $mime === 'image/png' ? 'png' : 'jpg';
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = rtrim($this->config['uploads']['dir'], '/') . '/gov-ids';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            Json::fail('Unable to store image', 500);
        }
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Json::fail('Unable to store image', 500);
        }
        $public = rtrim($this->config['uploads']['public_path'], '/') . '/gov-ids/' . $name;
        $repo = new GovIdRepository($this->pdo);
        $row = $repo->create($this->userId, $type, $number, $public);
        $this->refreshOnboarded();
        Json::ok('Government ID submitted', $repo->public($row, $this->config['base_url']), 201);
    }

    private function refreshOnboarded(): void
    {
        $users = new UserRepository($this->pdo);
        $user = $users->findById($this->userId);
        $gov = (new GovIdRepository($this->pdo))->latestForUser($this->userId);
        $ready = $user
            && !empty($user['name'])
            && !empty($user['gender'])
            && $user['height_cm'] !== null
            && $gov;
        $users->updateProfile($this->userId, ['is_onboarded' => $ready ? 1 : 0]);
    }

    private function payload(): array
    {
        $users = new UserRepository($this->pdo);
        $user = $users->findById($this->userId);
        if (!$user) {
            Json::fail('User not found', 404);
        }
        $govRepo = new GovIdRepository($this->pdo);
        $gov = $govRepo->latestForUser($this->userId);
        $govPublic = $gov ? $govRepo->public($gov, $this->config['base_url']) : null;
        return $users->publicUser($user, $govPublic);
    }
}
