<?php
$isEdit = !empty($announcement);
$action = $isEdit ? $base . '/announcements/' . (int) $announcement['id'] : $base . '/announcements';
$published = '';
if (!empty($announcement['published_at'])) {
    try {
        $published = (new DateTimeImmutable((string) $announcement['published_at']))->format('Y-m-d\TH:i');
    } catch (Throwable $e) {
        $published = '';
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit announcement' : 'New announcement' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/announcements">&larr; Back</a>
</div>
<div class="card-panel p-4" style="max-width: 720px;">
    <form method="post" action="<?= htmlspecialchars($action) ?>">
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" value="<?= htmlspecialchars((string) ($announcement['title'] ?? '')) ?>" required>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Branch</label>
                <select class="form-select" name="branch_id">
                    <option value="">All branches</option>
                    <?php foreach ($branches ?? [] as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= (int) ($announcement['branch_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Published at</label>
                <input class="form-control" type="datetime-local" name="published_at" value="<?= htmlspecialchars($published) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Body</label>
                <textarea class="form-control" name="body" rows="5"><?= htmlspecialchars((string) ($announcement['body'] ?? '')) ?></textarea>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !$isEdit || (int) $announcement['is_active'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>
        </div>
        <button class="btn btn-warning mt-3" type="submit"><?= $isEdit ? 'Save changes' : 'Create announcement' ?></button>
    </form>
</div>
