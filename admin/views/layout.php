<?php
$isLogin = ($path ?? '') === '/login';
$base = $base ?? '';
$path = $path ?? '/';
$flashes = $flashes ?? [];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Admin') ?> · Raw Fitness</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/assets/css/admin.css">
</head>
<body class="<?= $isLogin ? 'auth-body' : 'app-body' ?>">
<?php if ($isLogin): ?>
    <?php require $view; ?>
<?php else: ?>
    <div class="d-flex min-vh-100">
        <aside class="sidebar p-3">
            <div class="brand mb-4">RAW FITNESS</div>
            <nav class="nav flex-column gap-1">
                <?php
                $navItems = [
                    '/' => 'Dashboard',
                    '/members' => 'Members',
                    '/packages' => 'Packages',
                    '/workout-plans' => 'Workout Plans',
                    '/diet-plans' => 'Diet Plans',
                ];
                foreach ($navItems as $href => $label):
                    $active = $href === '/' ? ($path === '/') : str_starts_with($path, $href);
                ?>
                    <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= htmlspecialchars($base . $href) ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
                <a class="nav-link disabled" href="#">Branches</a>
                <a class="nav-link disabled" href="#">Notifications</a>
                <a class="nav-link disabled" href="#">Settings</a>
                <a class="nav-link text-warning" href="<?= htmlspecialchars($base) ?>/logout">Logout</a>
            </nav>
        </aside>
        <main class="flex-grow-1 p-4">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
            <?php endforeach; ?>
            <?php require $view; ?>
        </main>
    </div>
<?php endif; ?>
</body>
</html>
