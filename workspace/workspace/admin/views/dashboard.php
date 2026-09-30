<h1 class="h3 mb-2">Dashboard</h1>
<p class="text-secondary">Welcome<?= !empty($_SESSION['admin_name']) ? ', ' . htmlspecialchars((string) $_SESSION['admin_name']) : '' ?>.</p>
<div class="row g-3 mt-1">
    <div class="col-6 col-md-3">
        <div class="stat-card">Total members<br><strong><?= (int) ($stats['total'] ?? 0) ?></strong></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">Active<br><strong><?= (int) ($stats['active'] ?? 0) ?></strong></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">Expiring (7d)<br><strong><?= (int) ($stats['expiring'] ?? 0) ?></strong></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">Expired<br><strong><?= (int) ($stats['expired'] ?? 0) ?></strong></div>
    </div>
</div>
<div class="row g-3 mt-3">
    <div class="col-6 col-md-4">
        <div class="stat-card">Today's revenue<br><strong><?= htmlspecialchars(Admin::money($stats['todayRevenue'] ?? 0)) ?></strong></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">Monthly revenue<br><strong><?= htmlspecialchars(Admin::money($stats['monthRevenue'] ?? 0)) ?></strong></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">Today's attendance<br><strong><?= (int) ($stats['todayAttendance'] ?? 0) ?></strong></div>
    </div>
</div>
<div class="mt-4 d-flex gap-2 flex-wrap">
    <a class="btn btn-warning" href="<?= htmlspecialchars($base) ?>/members">Manage members</a>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/packages">Manage packages</a>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/notifications">Send notification</a>
</div>
