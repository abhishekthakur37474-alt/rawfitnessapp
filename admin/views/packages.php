<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Packages</h1>
    <div class="d-flex gap-2">
        <form class="d-flex gap-2" method="get" action="<?= htmlspecialchars($base) ?>/packages">
            <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search packages">
            <button class="btn btn-outline-light" type="submit">Search</button>
        </form>
        <a class="btn btn-warning" href="<?= htmlspecialchars($base) ?>/packages/new">New package</a>
    </div>
</div>
<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Duration</th>
                <th>Price</th>
                <th>Discount</th>
                <th>Final</th>
                <th>Branch</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($packages)): ?>
            <tr><td colspan="8" class="text-center text-secondary py-4">No packages yet.</td></tr>
        <?php else: foreach ($packages as $pkg): ?>
            <?php $final = max(0, (float) $pkg['price'] - (float) $pkg['discount']); ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) $pkg['name']) ?></td>
                <td><?= (int) $pkg['duration_days'] ?> days</td>
                <td><?= htmlspecialchars(Admin::money($pkg['price'])) ?></td>
                <td><?= htmlspecialchars(Admin::money($pkg['discount'])) ?></td>
                <td><?= htmlspecialchars(Admin::money($final)) ?></td>
                <td><?= htmlspecialchars((string) ($pkg['branch_name'] ?? 'All')) ?></td>
                <td><?= Admin::badge((int) $pkg['is_active'] === 1 ? 'active' : 'inactive') ?></td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base) ?>/packages/<?= (int) $pkg['id'] ?>/edit">Edit</a>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/packages/<?= (int) $pkg['id'] ?>/toggle">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-warning" type="submit"><?= (int) $pkg['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/packages/<?= (int) $pkg['id'] ?>/delete" onsubmit="return confirm('Delete this package?');">
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
