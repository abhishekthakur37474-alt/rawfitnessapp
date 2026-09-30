<?php
$isEdit = !empty($facility);
$action = $isEdit ? $base . '/facilities/' . (int) $facility['id'] : $base . '/facilities';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit facility' : 'New facility' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/facilities">&larr; Back</a>
</div>
<div class="card-panel p-4" style="max-width: 560px;">
    <form method="post" action="<?= htmlspecialchars($action) ?>">
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars((string) ($facility['name'] ?? '')) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Icon</label>
            <input class="form-control" name="icon" value="<?= htmlspecialchars((string) ($facility['icon'] ?? '')) ?>" placeholder="fitness_center">
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !$isEdit || (int) $facility['is_active'] === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActive">Active</label>
        </div>
        <button class="btn btn-warning" type="submit"><?= $isEdit ? 'Save changes' : 'Create facility' ?></button>
    </form>
</div>
