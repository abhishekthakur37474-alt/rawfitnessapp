<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Facilities</h1>
    <a class="btn btn-warning" href="<?= htmlspecialchars($base) ?>/facilities/new">New facility</a>
</div>
<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Icon</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($facilities)): ?>
            <tr><td colspan="4" class="text-center text-secondary py-4">No facilities yet.</td></tr>
        <?php else: foreach ($facilities as $row): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) $row['name']) ?></td>
                <td><?= htmlspecialchars((string) ($row['icon'] ?? '—')) ?></td>
                <td><?= Admin::badge((int) $row['is_active'] === 1 ? 'active' : 'inactive') ?></td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base) ?>/facilities/<?= (int) $row['id'] ?>/edit">Edit</a>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/facilities/<?= (int) $row['id'] ?>/delete" onsubmit="return confirm('Delete this facility?');">
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
