<?php
$isEdit = !empty($event);
$action = $isEdit ? $base . '/events/' . (int) $event['id'] : $base . '/events';
$toLocal = static function (?string $v): string {
    if (!$v) {
        return '';
    }
    try {
        return (new DateTimeImmutable($v))->format('Y-m-d\TH:i');
    } catch (Throwable $e) {
        return '';
    }
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit event' : 'New event' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/events">&larr; Back</a>
</div>
<form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($action) ?>">
    <?= Csrf::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-panel p-4">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input class="form-control" name="title" value="<?= htmlspecialchars((string) ($event['title'] ?? '')) ?>" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <select class="form-select" name="branch_id">
                            <option value="">All branches</option>
                            <?php foreach ($branches ?? [] as $b): ?>
                                <option value="<?= (int) $b['id'] ?>" <?= (int) ($event['branch_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input class="form-control" name="location" value="<?= htmlspecialchars((string) ($event['location'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Starts</label>
                        <input class="form-control" type="datetime-local" name="starts_at" value="<?= htmlspecialchars($toLocal($event['starts_at'] ?? null)) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ends</label>
                        <input class="form-control" type="datetime-local" name="ends_at" value="<?= htmlspecialchars($toLocal($event['ends_at'] ?? null)) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars((string) ($event['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !$isEdit || (int) $event['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-panel p-4">
                <h2 class="h5 mb-3">Image</h2>
                <?php if (!empty($imageUrl)): ?>
                    <img class="gov-thumb mb-2" src="<?= htmlspecialchars((string) $imageUrl) ?>" alt="">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage">
                        <label class="form-check-label" for="removeImage">Remove current image</label>
                    </div>
                <?php endif; ?>
                <input class="form-control" type="file" name="image" accept="image/jpeg,image/png">
            </div>
        </div>
    </div>
    <button class="btn btn-warning mt-3" type="submit"><?= $isEdit ? 'Save changes' : 'Create event' ?></button>
</form>
