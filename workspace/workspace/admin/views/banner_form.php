<?php
$isEdit = !empty($banner);
$action = $isEdit ? $base . '/banners/' . (int) $banner['id'] : $base . '/banners';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit banner' : 'New banner' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/banners">&larr; Back</a>
</div>
<form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($action) ?>">
    <?= Csrf::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-panel p-4">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input class="form-control" name="title" value="<?= htmlspecialchars((string) ($banner['title'] ?? '')) ?>">
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <select class="form-select" name="branch_id">
                            <option value="">All branches</option>
                            <?php foreach ($branches ?? [] as $b): ?>
                                <option value="<?= (int) $b['id'] ?>" <?= (int) ($banner['branch_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Link type</label>
                        <select class="form-select" name="link_type">
                            <?php foreach (['' => 'None', 'event' => 'Event', 'announcement' => 'Announcement', 'url' => 'URL'] as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>" <?= (string) ($banner['link_type'] ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sort</label>
                        <input class="form-control" type="number" min="0" name="sort_order" value="<?= (int) ($banner['sort_order'] ?? 0) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Link value</label>
                        <input class="form-control" name="link_value" value="<?= htmlspecialchars((string) ($banner['link_value'] ?? '')) ?>" placeholder="ID or URL">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !$isEdit || (int) $banner['is_active'] === 1 ? 'checked' : '' ?>>
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
                    <?php if ($isEdit): ?>
                        <p class="text-secondary small">Uploading a new file replaces the current image.</p>
                    <?php endif; ?>
                <?php endif; ?>
                <input class="form-control" type="file" name="image" accept="image/jpeg,image/png" <?= $isEdit ? '' : 'required' ?>>
            </div>
        </div>
    </div>
    <button class="btn btn-warning mt-3" type="submit"><?= $isEdit ? 'Save changes' : 'Create banner' ?></button>
</form>
