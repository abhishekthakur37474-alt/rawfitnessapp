<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Members</h1>
        <p class="text-secondary mb-0"><?= (int) ($memberTotal ?? 0) ?> member(s)</p>
    </div>
    <form class="d-flex gap-2" method="get" action="<?= htmlspecialchars($base) ?>/members">
        <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search name / mobile / ID">
        <button class="btn btn-warning" type="submit">Search</button>
    </form>
</div>
<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Member ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Membership</th>
                <th>Valid till</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($members)): ?>
            <tr><td colspan="6" class="text-center text-secondary py-4">No members found.</td></tr>
        <?php else: foreach ($members as $m): ?>
            <?php $status = Admin::memberStatus($m['m_status'] ?? null, $m['m_end'] ?? null); ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars((string) $m['member_id']) ?></td>
                <td><?= htmlspecialchars((string) ($m['name'] ?? '—')) ?></td>
                <td><?= htmlspecialchars((string) $m['mobile']) ?></td>
                <td><?= Admin::badge($status) ?></td>
                <td><?= !empty($m['m_end']) ? htmlspecialchars(date('d M Y', strtotime((string) $m['m_end']))) : '—' ?></td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base) ?>/members/<?= (int) $m['id'] ?>">View</a>
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
                    <a class="page-link" href="<?= htmlspecialchars($base) ?>/members?q=<?= urlencode((string) ($search ?? '')) ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
