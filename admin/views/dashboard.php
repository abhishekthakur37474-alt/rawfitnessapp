<h1 class="h3 mb-2">Dashboard</h1>
<p class="text-secondary">Welcome<?= !empty($_SESSION['admin_name']) ? ', ' . htmlspecialchars((string) $_SESSION['admin_name']) : '' ?>.</p>
<div class="row g-3 mt-1">
    <div class="col-md-3">
        <div class="stat-card">Total members<br><strong><?= (int) ($memberCount ?? 0) ?></strong></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">Active<br><strong>—</strong></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">Expiring (7d)<br><strong>—</strong></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">Expired<br><strong>—</strong></div>
    </div>
</div>
