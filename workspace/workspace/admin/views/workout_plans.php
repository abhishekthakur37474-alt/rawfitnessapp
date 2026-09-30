<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Workout plans</h1>
        <p class="text-secondary mb-0"><?= (int) ($planTotal ?? 0) ?> plan(s)</p>
    </div>
    <div class="d-flex gap-2">
        <form class="d-flex gap-2" method="get" action="<?= htmlspecialchars($base) ?>/workout-plans">
            <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search plans">
            <button class="btn btn-outline-light" type="submit">Search</button>
        </form>
        <a class="btn btn-warning" href="<?= htmlspecialchars($base) ?>/workout-plans/new">New plan</a>
    </div>
</div>
<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Level</th>
                <th>Days</th>
                <th>Exercises</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($plans)): ?>
            <tr><td colspan="7" class="text-center text-secondary py-4">No workout plans yet.</td></tr>
        <?php else: foreach ($plans as $plan): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) $plan['title']) ?></td>
                <td><?= htmlspecialchars((string) ($plan['category'] ?? '—')) ?></td>
                <td><?= htmlspecialchars(ucfirst((string) $plan['level'])) ?></td>
                <td><?= (int) ($plan['day_count'] ?? 0) ?></td>
                <td><?= (int) ($plan['exercise_count'] ?? 0) ?></td>
                <td><?= Admin::badge((int) $plan['is_active'] === 1 ? 'active' : 'inactive') ?></td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base) ?>/workout-plans/<?= (int) $plan['id'] ?>/edit">Edit</a>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/workout-plans/<?= (int) $plan['id'] ?>/toggle">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-warning" type="submit"><?= (int) $plan['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="post" action="<?= htmlspecialchars($base) ?>/workout-plans/<?= (int) $plan['id'] ?>/delete" onsubmit="return confirm('Delete this workout plan?');">
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
<?php if (($pageCount ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($p = 1; $p <= (int) $pageCount; $p++): ?>
                <li class="page-item <?= $p === (int) ($pageNo ?? 1) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($base) ?>/workout-plans?q=<?= urlencode((string) ($search ?? '')) ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
