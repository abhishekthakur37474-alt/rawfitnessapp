<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Attendance</h1>
        <p class="text-secondary mb-0"><?= (int) ($attTotal ?? 0) ?> record(s)</p>
    </div>
</div>
<div class="card-panel p-3 mb-3">
    <form class="row g-2 align-items-end" method="get" action="<?= htmlspecialchars($base) ?>/attendance">
        <div class="col-md-4">
            <label class="form-label">Search member</label>
            <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Name / ID / mobile">
        </div>
        <div class="col-md-3">
            <label class="form-label">Branch</label>
            <select class="form-select" name="branch_id">
                <option value="">All</option>
                <?php foreach ($branches ?? [] as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= (int) ($branchFilter ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-light w-100" type="submit">Filter</button>
        </div>
    </form>
</div>
<div class="card-panel p-3 mb-3">
    <h2 class="h5">Manual entry</h2>
    <form class="row g-2 align-items-end" method="post" action="<?= htmlspecialchars($base) ?>/attendance">
        <?= Csrf::field() ?>
        <div class="col-md-3">
            <label class="form-label">Member</label>
            <select class="form-select" name="user_id" required>
                <option value="">Select</option>
                <?php foreach ($members ?? [] as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars((string) ($m['member_id'] . ' · ' . ($m['name'] ?? $m['mobile']))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Branch</label>
            <select class="form-select" name="branch_id">
                <option value="">—</option>
                <?php foreach ($branches ?? [] as $b): ?>
                    <option value="<?= (int) $b['id'] ?>"><?= htmlspecialchars((string) $b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Check-in</label>
            <input class="form-control" type="datetime-local" name="check_in" value="<?= htmlspecialchars(date('Y-m-d\TH:i')) ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Check-out</label>
            <input class="form-control" type="datetime-local" name="check_out">
        </div>
        <div class="col-md-1">
            <button class="btn btn-warning w-100" type="submit">Add</button>
        </div>
    </form>
</div>
<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Member</th>
                <th>Branch</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Source</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="5" class="text-center text-secondary py-4">No attendance yet.</td></tr>
        <?php else: foreach ($records as $row): ?>
            <tr>
                <td>
                    <a class="link-light" href="<?= htmlspecialchars($base) ?>/members/<?= (int) $row['user_id'] ?>"><?= htmlspecialchars((string) ($row['member_id'] ?? '')) ?></a>
                    <div class="text-secondary small"><?= htmlspecialchars((string) ($row['member_name'] ?? $row['mobile'] ?? '')) ?></div>
                </td>
                <td><?= htmlspecialchars((string) ($row['branch_name'] ?? '—')) ?></td>
                <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime((string) $row['check_in']))) ?></td>
                <td><?= !empty($row['check_out']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $row['check_out']))) : '—' ?></td>
                <td><?= Admin::badge((string) $row['source']) ?></td>
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
                    <a class="page-link" href="<?= htmlspecialchars($base) ?>/attendance?q=<?= urlencode((string) ($search ?? '')) ?>&branch_id=<?= urlencode((string) ($branchFilter ?? '')) ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
