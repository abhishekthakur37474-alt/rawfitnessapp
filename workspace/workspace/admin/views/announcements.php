<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Announcements</h1>
        <p class="text-secondary mb-0"><?= count($announcements ?? []) ?> item(s)</p>
    </div>
    <div class="d-flex gap-2">
        <form class="d-flex gap-2" method="get" action="<?= htmlspecialchars($base) ?>/announcements">
            <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search">
            <button class="btn btn-outline-light" type="submit">Search</button>
        </form>
        <a class="btn btn-warning" href="<?= htmlspecialchars($base) ?>/announcements/new">New announcement</a>
    </div>
</div>
<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Title</th>
                <th>Branch</th>
                <th>Published</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($announcements)): ?>
            <tr><td colspan="5" class="text-center text-secondary py-4">No announcements yet.</td></tr>
        <?php else: foreach ($announcements as $row): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) $row['title']) ?></td>
                <td><?= htmlspecialchars((string) ($row['branch_name'] ?? 'All')) ?></td>
                <td><?= !empty($row['published_at']) ? htmlspecialchars(date('d M Y', strtotime((string) $row['published_at']))) : '—' ?></td>
                <td><?= Admin::badge((int) $row['is_active'] === 1 ? 'active' : 'inactive') ?></td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base) ?>/announcements/<?= (int) $row['id'] ?>/edit">Edit</a>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/announcements/<?= (int) $row['id'] ?>/toggle">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-warning" type="submit"><?= (int) $row['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/announcements/<?= (int) $row['id'] ?>/delete" onsubmit="return confirm('Delete this announcement?');">
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
