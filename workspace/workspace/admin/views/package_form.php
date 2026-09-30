<?php
$isEdit = !empty($package);
$action = $isEdit
    ? $base . '/packages/' . (int) $package['id']
    : $base . '/packages';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit package' : 'New package' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/packages">&larr; Back</a>
</div>
<div class="card-panel p-4" style="max-width: 640px;">
    <form method="post" action="<?= htmlspecialchars($action) ?>">
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars((string) ($package['name'] ?? '')) ?>" required>
        </div>
        <div class="row g-3">
            <div class="col-6">
                <label class="form-label">Duration (days)</label>
                <input class="form-control" type="number" min="1" name="duration_days" value="<?= (int) ($package['duration_days'] ?? 30) ?>" required>
            </div>
            <div class="col-6">
                <label class="form-label">Branch</label>
                <select class="form-select" name="branch_id">
                    <option value="">All branches</option>
                    <?php foreach (($branches ?? []) as $branch): ?>
                        <option value="<?= (int) $branch['id'] ?>" <?= (int) ($package['branch_id'] ?? 0) === (int) $branch['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $branch['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Price</label>
                <input class="form-control" type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars((string) ($package['price'] ?? '0')) ?>" required>
            </div>
            <div class="col-6">
                <label class="form-label">Discount</label>
                <input class="form-control" type="number" step="0.01" min="0" name="discount" value="<?= htmlspecialchars((string) ($package['discount'] ?? '0')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars((string) ($package['description'] ?? '')) ?></textarea>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !$isEdit || (int) $package['is_active'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>
        </div>
        <button class="btn btn-warning mt-3" type="submit"><?= $isEdit ? 'Save changes' : 'Create package' ?></button>
    </form>
</div>
