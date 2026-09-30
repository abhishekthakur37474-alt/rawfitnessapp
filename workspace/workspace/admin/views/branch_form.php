<?php
$isEdit = !empty($branch);
$action = $isEdit ? $base . '/branches/' . (int) $branch['id'] : $base . '/branches';
$days = App\Repositories\BranchRepository::DAYS;
$timingsByDay = [];
foreach ($timings ?? [] as $t) {
    $timingsByDay[(int) $t['day_of_week']] = $t;
}
$selectedFacilities = $selectedFacilities ?? [];
$parking = $parking ?? null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit branch' : 'New branch' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/branches">&larr; Back</a>
</div>
<form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($action) ?>">
    <?= Csrf::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-panel p-4">
                <h2 class="h5 mb-3">Details</h2>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input class="form-control" name="name" value="<?= htmlspecialchars((string) ($branch['name'] ?? '')) ?>" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Address</label>
                        <input class="form-control" name="address" value="<?= htmlspecialchars((string) ($branch['address'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input class="form-control" name="city" value="<?= htmlspecialchars((string) ($branch['city'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" value="<?= htmlspecialchars((string) ($branch['phone'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">WhatsApp</label>
                        <input class="form-control" name="whatsapp" value="<?= htmlspecialchars((string) ($branch['whatsapp'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?= htmlspecialchars((string) ($branch['email'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Latitude</label>
                        <input class="form-control" name="lat" value="<?= htmlspecialchars((string) ($branch['lat'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitude</label>
                        <input class="form-control" name="lng" value="<?= htmlspecialchars((string) ($branch['lng'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars((string) ($branch['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !$isEdit || (int) $branch['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-panel p-4 mt-3">
                <h2 class="h5 mb-3">Timings</h2>
                <?php foreach ($days as $d => $label): $t = $timingsByDay[$d] ?? null; ?>
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-md-3"><label class="form-label"><?= htmlspecialchars($label) ?></label></div>
                        <div class="col-md-3">
                            <input class="form-control" type="time" name="timings[<?= (int) $d ?>][open_time]" value="<?= htmlspecialchars(substr((string) ($t['open_time'] ?? '06:00:00'), 0, 5)) ?>">
                        </div>
                        <div class="col-md-3">
                            <input class="form-control" type="time" name="timings[<?= (int) $d ?>][close_time]" value="<?= htmlspecialchars(substr((string) ($t['close_time'] ?? '22:00:00'), 0, 5)) ?>">
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="timings[<?= (int) $d ?>][is_closed]" value="1" id="closed<?= (int) $d ?>" <?= $t && (int) $t['is_closed'] === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="closed<?= (int) $d ?>">Closed</label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-panel p-4 mt-3">
                <h2 class="h5 mb-3">Facilities</h2>
                <div class="row">
                    <?php foreach ($facilities ?? [] as $f): ?>
                        <div class="col-md-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="facility_ids[]" value="<?= (int) $f['id'] ?>" id="fac<?= (int) $f['id'] ?>" <?= in_array((int) $f['id'], $selectedFacilities, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fac<?= (int) $f['id'] ?>"><?= htmlspecialchars((string) $f['name']) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($facilities)): ?>
                        <p class="text-secondary mb-0">Create facilities first.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-panel p-4">
                <h2 class="h5 mb-3">Parking</h2>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="has_parking" value="1" id="hasParking" <?= !empty($parking['has_parking']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="hasParking">Has parking</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="two_wheeler" value="1" id="twoW" <?= !empty($parking['two_wheeler']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="twoW">Two-wheeler</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="four_wheeler" value="1" id="fourW" <?= !empty($parking['four_wheeler']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="fourW">Four-wheeler</label>
                </div>
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="parking_notes" rows="3"><?= htmlspecialchars((string) ($parking['notes'] ?? '')) ?></textarea>
            </div>
            <?php if ($isEdit): ?>
                <div class="card-panel p-4 mt-3">
                    <h2 class="h5 mb-3">Photos</h2>
                    <?php foreach ($photos ?? [] as $photo): ?>
                        <div class="mb-3">
                            <img class="gov-thumb" src="<?= htmlspecialchars((string) App\Support\Media::url($photo['image'], (string) $config['base_url'])) ?>" alt="">
                            <form class="mt-2" method="post" action="<?= htmlspecialchars($base) ?>/branches/<?= (int) $branch['id'] ?>/photos/<?= (int) $photo['id'] ?>/delete" onsubmit="return confirm('Remove this photo?');">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    <label class="form-label">Add photo</label>
                    <input class="form-control" type="file" name="photo" accept="image/jpeg,image/png">
                </div>
            <?php endif; ?>
        </div>
    </div>
    <button class="btn btn-warning mt-3" type="submit"><?= $isEdit ? 'Save changes' : 'Create branch' ?></button>
</form>
