<?php
$isEdit = !empty($trainer);
$action = $isEdit ? $base . '/trainers/' . (int) $trainer['id'] : $base . '/trainers';
$certs = $certs ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Edit trainer' : 'New trainer' ?></h1>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/trainers">&larr; Back</a>
</div>
<form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($action) ?>">
    <?= Csrf::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-panel p-4">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input class="form-control" name="name" value="<?= htmlspecialchars((string) ($trainer['name'] ?? '')) ?>" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input class="form-control" name="role" value="<?= htmlspecialchars((string) ($trainer['role'] ?? '')) ?>" placeholder="Strength coach">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" value="<?= htmlspecialchars((string) ($trainer['phone'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <select class="form-select" name="branch_id">
                            <option value="">All branches</option>
                            <?php foreach ($branches ?? [] as $b): ?>
                                <option value="<?= (int) $b['id'] ?>" <?= (int) ($trainer['branch_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="3"><?= htmlspecialchars((string) ($trainer['bio'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= !$isEdit || (int) $trainer['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h2 class="h5 mb-0">Certifications</h2>
                <button class="btn btn-outline-light" type="button" id="addCert">+ Add</button>
            </div>
            <div id="certsWrap">
                <?php foreach ($certs as $i => $c): ?>
                    <div class="card-panel p-3 mb-2 cert-row">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Title</label>
                                <input class="form-control" name="certs[<?= (int) $i ?>][title]" value="<?= htmlspecialchars((string) ($c['title'] ?? '')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Issuer</label>
                                <input class="form-control" name="certs[<?= (int) $i ?>][issuer]" value="<?= htmlspecialchars((string) ($c['issuer'] ?? '')) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Year</label>
                                <input class="form-control" name="certs[<?= (int) $i ?>][year]" value="<?= htmlspecialchars((string) ($c['year'] ?? '')) ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-cert w-100">x</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-panel p-4">
                <h2 class="h5 mb-3">Photo</h2>
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
    <button class="btn btn-warning mt-3" type="submit"><?= $isEdit ? 'Save changes' : 'Create trainer' ?></button>
</form>
<template id="certTemplate">
    <div class="card-panel p-3 mb-2 cert-row">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Title</label>
                <input class="form-control" name="certs[__I__][title]">
            </div>
            <div class="col-md-4">
                <label class="form-label">Issuer</label>
                <input class="form-control" name="certs[__I__][issuer]">
            </div>
            <div class="col-md-2">
                <label class="form-label">Year</label>
                <input class="form-control" name="certs[__I__][year]">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger remove-cert w-100">x</button>
            </div>
        </div>
    </div>
</template>
<script>
(function () {
    var uid = 0, wrap = document.getElementById('certsWrap'), tpl = document.getElementById('certTemplate').innerHTML;
    function bind(row) { row.querySelector('.remove-cert').addEventListener('click', function () { row.remove(); }); }
    document.getElementById('addCert').addEventListener('click', function () {
        var holder = document.createElement('div');
        holder.innerHTML = tpl.split('__I__').join('c' + (uid++)).trim();
        var row = holder.firstElementChild;
        wrap.appendChild(row);
        bind(row);
    });
    Array.prototype.forEach.call(document.querySelectorAll('.cert-row'), bind);
})();
</script>
