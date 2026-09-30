<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Branches</h1>
        <p class="text-secondary mb-0"><?= count($branches ?? []) ?> branch(es)</p>
    </div>
    <div class="d-flex gap-2">
        <form class="d-flex gap-2" method="get" action="<?= htmlspecialchars($base) ?>/branches">
            <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search branches">
            <button class="btn btn-outline-light" type="submit">Search</button>
        </form>
        <a class="btn btn-warning" href="<?= htmlspecialchars($base) ?>/branches/new">New branch</a>
    </div>
</div>
<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>City</th>
                <th>Phone</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($branches)): ?>
            <tr><td colspan="5" class="text-center text-secondary py-4">No branches yet.</td></tr>
        <?php else: foreach ($branches as $row): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) $row['name']) ?></td>
                <td><?= htmlspecialchars((string) ($row['city'] ?? '—')) ?></td>
                <td><?= htmlspecialchars((string) ($row['phone'] ?? '—')) ?></td>
                <td><?= Admin::badge((int) $row['is_active'] === 1 ? 'active' : 'inactive') ?></td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base) ?>/branches/<?= (int) $row['id'] ?>/edit">Edit</a>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/branches/<?= (int) $row['id'] ?>/toggle">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-warning" type="submit"><?= (int) $row['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/branches/<?= (int) $row['id'] ?>/delete" onsubmit="return confirm('Delete this branch?');">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
