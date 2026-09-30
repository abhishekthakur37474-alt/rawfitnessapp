<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Banners</h1>
        <p class="text-secondary mb-0"><?= count($banners ?? []) ?> banner(s)</p>
    </div>
    <a class="btn btn-warning" href="<?= htmlspecialchars($base) ?>/banners/new">New banner</a>
</div>
<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Title</th>
                <th>Branch</th>
                <th>Order</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($banners)): ?>
            <tr><td colspan="5" class="text-center text-secondary py-4">No banners yet.</td></tr>
        <?php else: foreach ($banners as $row): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) ($row['title'] ?? 'Banner')) ?></td>
                <td><?= htmlspecialchars((string) ($row['branch_name'] ?? 'All')) ?></td>
                <td><?= (int) $row['sort_order'] ?></td>
                <td><?= Admin::badge((int) $row['is_active'] === 1 ? 'active' : 'inactive') ?></td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base) ?>/banners/<?= (int) $row['id'] ?>/edit">Edit</a>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/banners/<?= (int) $row['id'] ?>/toggle">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-warning" type="submit"><?= (int) $row['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/banners/<?= (int) $row['id'] ?>/delete" onsubmit="return confirm('Delete this banner?');">
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
