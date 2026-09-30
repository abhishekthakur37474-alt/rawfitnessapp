<?php
$isLogin = ($path ?? '') === '/login';
$base = $base ?? '';
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
                <a class="nav-link active" href="<?= htmlspecialchars($base) ?>/">Dashboard</a>
                <a class="nav-link disabled" href="#">Members</a>
                <a class="nav-link disabled" href="#">Packages</a>
                <a class="nav-link disabled" href="#">Workouts</a>
                <a class="nav-link disabled" href="#">Diet</a>
                <a class="nav-link disabled" href="#">Branches</a>
                <a class="nav-link disabled" href="#">Notifications</a>
                <a class="nav-link disabled" href="#">Settings</a>
                <a class="nav-link text-warning" href="<?= htmlspecialchars($base) ?>/logout">Logout</a>
            </nav>
        </aside>
        <main class="flex-grow-1 p-4">
            <?php require $view; ?>
        </main>
    </div>
<?php endif; ?>
</body>
</html>
