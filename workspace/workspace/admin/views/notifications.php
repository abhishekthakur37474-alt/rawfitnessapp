<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Notifications</h1>
        <p class="text-secondary mb-0"><?= (int) ($notifTotal ?? 0) ?> logged</p>
    </div>
    <form class="d-flex gap-2" method="get" action="<?= htmlspecialchars($base) ?>/notifications">
        <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search title / member">
        <button class="btn btn-outline-light" type="submit">Search</button>
    </form>
</div>

<div class="card-panel p-4 mb-4">
    <h2 class="h5 mb-3">Send notification</h2>
    <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($base) ?>/notifications">
        <?= Csrf::field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Audience</label>
                <select class="form-select" name="audience" id="audience">
                    <option value="all">All members</option>
                    <option value="member">Single member</option>
                    <option value="branch">Branch</option>
                </select>
            </div>
            <div class="col-md-4" id="memberWrap">
                <label class="form-label">Member</label>
                <select class="form-select" name="user_id">
                    <option value="">Select member</option>
                    <?php foreach ($members ?? [] as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars((string) ($m['member_id'] . ' · ' . ($m['name'] ?: $m['mobile']))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4" id="branchWrap">
                <label class="form-label">Branch</label>
                <select class="form-select" name="branch_id">
                    <option value="">Select branch</option>
                    <?php foreach ($branches ?? [] as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"><?= htmlspecialchars((string) $b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select class="form-select" name="type">
                    <option value="general">General</option>
                    <option value="membership">Membership</option>
                    <option value="event">Event</option>
                    <option value="announcement">Announcement</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Link value (optional ID)</label>
                <input class="form-control" name="link_value" placeholder="event/announcement id">
            </div>
            <div class="col-12">
                <label class="form-label">Title</label>
                <input class="form-control" name="title" required maxlength="190">
            </div>
            <div class="col-12">
                <label class="form-label">Body</label>
                <textarea class="form-control" name="body" rows="3" required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Image (optional)</label>
                <input class="form-control" type="file" name="image" accept="image/jpeg,image/png">
            </div>
        </div>
        <button class="btn btn-warning mt-3" type="submit">Send</button>
    </form>
</div>

<div class="table-responsive card-panel">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>When</th>
                <th>Title</th>
                <th>Audience</th>
                <th>Type</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($notifications)): ?>
            <tr><td colspan="5" class="text-center text-secondary py-4">No notifications yet.</td></tr>
        <?php else: foreach ($notifications as $n): ?>
            <tr>
                <td><?= htmlspecialchars(date('d M Y, h:i a', strtotime((string) $n['created_at']))) ?></td>
                <td>
                    <div class="fw-semibold"><?= htmlspecialchars((string) $n['title']) ?></div>
                    <div class="text-secondary small"><?= htmlspecialchars(strlen((string) ($n['body'] ?? '')) > 80 ? substr((string) $n['body'], 0, 77) . '...' : (string) ($n['body'] ?? '')) ?></div>
                </td>
                <td>
                    <?= htmlspecialchars((string) ($n['audience'] ?? 'all')) ?>
                    <?php if (!empty($n['member_id'])): ?>
                        <div class="small text-secondary"><?= htmlspecialchars((string) $n['member_id']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) ($n['type'] ?? 'general')) ?></td>
                <td><?= Admin::badge((string) ($n['onesignal_status'] ?? 'pending')) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php if (($pageCount ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination">
        <?php for ($i = 1; $i <= $pageCount; $i++): ?>
            <li class="page-item <?= $i === ($pageNo ?? 1) ? 'active' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($base) ?>/notifications?page=<?= $i ?>&q=<?= urlencode((string) ($search ?? '')) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
