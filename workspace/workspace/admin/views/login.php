<div class="auth-card">
    <h1 class="h4 mb-1">Admin login</h1>
    <p class="text-secondary mb-4">Raw Fitness control panel</p>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($base ?? '') ?>/login">
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" required autocomplete="username">
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-warning w-100" type="submit">Sign in</button>
    </form>
</div>
