<?php
$govTypeLabels = [
    'aadhaar' => 'Aadhaar',
    'pan' => 'PAN',
    'passport' => 'Passport',
    'driving_license' => 'Driving License',
];
$govStatus = $govId['status'] ?? 'none';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0"><?= htmlspecialchars((string) ($member['name'] ?? 'Member')) ?></h1>
        <p class="text-secondary mb-0">
            <?= htmlspecialchars((string) $member['member_id']) ?> · <?= htmlspecialchars((string) $member['mobile']) ?>
        </p>
    </div>
    <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/members">&larr; All members</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-panel p-3 h-100">
            <h2 class="h5">Government ID</h2>
            <?php if (!$govId): ?>
                <p class="text-secondary mb-0">No government ID submitted.</p>
            <?php else: ?>
                <p class="mb-1">Type: <strong><?= htmlspecialchars($govTypeLabels[$govId['type']] ?? $govId['type']) ?></strong></p>
                <p class="mb-1">Number: <strong><?= htmlspecialchars((string) $govId['id_number']) ?></strong></p>
                <p class="mb-2">Status: <?= Admin::badge((string) $govId['status']) ?></p>
                <?php if (!empty($govId['reason'])): ?>
                    <p class="text-secondary">Reason: <?= htmlspecialchars((string) $govId['reason']) ?></p>
                <?php endif; ?>
                <a href="<?= htmlspecialchars((string) $govId['image_url']) ?>" target="_blank" rel="noopener">
                    <img class="gov-thumb" src="<?= htmlspecialchars((string) $govId['image_url']) ?>" alt="Government ID">
                </a>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <form method="post" action="<?= htmlspecialchars($base) ?>/members/<?= (int) $member['id'] ?>/gov-id">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-success btn-sm" type="submit">Approve ID</button>
                    </form>
                    <form class="d-flex gap-2" method="post" action="<?= htmlspecialchars($base) ?>/members/<?= (int) $member['id'] ?>/gov-id">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="reject">
                        <input class="form-control form-control-sm" name="reason" placeholder="Rejection reason" required>
                        <button class="btn btn-danger btn-sm" type="submit">Reject</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-panel p-3 h-100">
            <h2 class="h5">Current membership</h2>
            <?php if (!$current): ?>
                <p class="text-secondary">No membership yet.</p>
            <?php else: ?>
                <p class="mb-1">Plan: <strong><?= htmlspecialchars((string) ($current['package_name'] ?? 'Custom')) ?></strong></p>
                <p class="mb-1">Status: <?= Admin::badge((string) $current['status']) ?></p>
                <p class="mb-1">Valid: <?= htmlspecialchars(date('d M Y', strtotime((string) $current['start_date']))) ?> – <?= htmlspecialchars(date('d M Y', strtotime((string) $current['end_date']))) ?></p>
                <p class="mb-1">Amount: <?= htmlspecialchars(Admin::money($current['amount'])) ?></p>
                <p class="mb-1">Paid: <?= htmlspecialchars(Admin::money($current['paid_amount'])) ?> · Due: <?= htmlspecialchars(Admin::money($current['due_amount'])) ?></p>
                <p class="mb-0 text-secondary"><?= (int) $current['days_left'] ?> day(s) remaining</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-panel p-3">
            <h2 class="h5">Assign / renew membership</h2>
            <?php if (empty($packages)): ?>
                <p class="text-secondary mb-0">Create a package first.</p>
            <?php else: ?>
                <form method="post" action="<?= htmlspecialchars($base) ?>/members/<?= (int) $member['id'] ?>/membership">
                    <?= Csrf::field() ?>
                    <div class="mb-2">
                        <label class="form-label">Package</label>
                        <select class="form-select" name="package_id" required>
                            <?php foreach ($packages as $pkg): ?>
                                <option value="<?= (int) $pkg['id'] ?>">
                                    <?= htmlspecialchars($pkg['name']) ?> · <?= (int) $pkg['duration_days'] ?> days · <?= htmlspecialchars(Admin::money($pkg['final_price'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Start date</label>
                            <input class="form-control" type="date" name="start_date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Amount override</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="amount" placeholder="Optional">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Paid now</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="paid_amount" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Mode</label>
                            <select class="form-select" name="mode">
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reference (optional)</label>
                            <input class="form-control" name="txn_ref" placeholder="Txn / cheque no.">
                        </div>
                    </div>
                    <button class="btn btn-warning mt-3" type="submit">Save membership</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-panel p-3">
            <h2 class="h5">Record offline payment</h2>
            <?php if (!$current): ?>
                <p class="text-secondary mb-0">Assign a membership before recording payments.</p>
            <?php else: ?>
                <form method="post" action="<?= htmlspecialchars($base) ?>/members/<?= (int) $member['id'] ?>/payment">
                    <?= Csrf::field() ?>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Amount</label>
                            <input class="form-control" type="number" step="0.01" min="1" name="amount" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Mode</label>
                            <select class="form-select" name="mode">
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reference (optional)</label>
                            <input class="form-control" name="txn_ref">
                        </div>
                    </div>
                    <button class="btn btn-warning mt-3" type="submit">Record payment</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12">
        <div class="card-panel p-3">
            <h2 class="h5">Payment history</h2>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Receipt</th><th>Date</th><th>Amount</th><th>Mode</th><th>Status</th><th>Reference</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-3">No payments yet.</td></tr>
                    <?php else: foreach ($payments as $pay): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $pay['receipt_no']) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime((string) $pay['created_at']))) ?></td>
                            <td><?= htmlspecialchars(Admin::money($pay['amount'])) ?></td>
                            <td><?= htmlspecialchars(strtoupper((string) $pay['mode'])) ?></td>
                            <td><?= Admin::badge((string) $pay['status']) ?></td>
                            <td><?= htmlspecialchars((string) ($pay['txn_ref'] ?? '—')) ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base) ?>/receipts/<?= (int) $pay['id'] ?>">Receipt</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card-panel p-3">
            <h2 class="h5">Membership history</h2>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Plan</th><th>Start</th><th>End</th><th>Amount</th><th>Paid</th><th>Due</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($membershipHistory)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-3">No membership records.</td></tr>
                    <?php else: foreach ($membershipHistory as $mem): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($mem['package_name'] ?? 'Custom')) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime((string) $mem['start_date']))) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime((string) $mem['end_date']))) ?></td>
                            <td><?= htmlspecialchars(Admin::money($mem['amount'])) ?></td>
                            <td><?= htmlspecialchars(Admin::money($mem['paid_amount'])) ?></td>
                            <td><?= htmlspecialchars(Admin::money($mem['due_amount'])) ?></td>
                            <td><?= Admin::badge((string) $mem['status']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card-panel p-3">
            <h2 class="h5">Attendance</h2>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Check-in</th><th>Check-out</th><th>Branch</th><th>Source</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($attendance)): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-3">No attendance yet.</td></tr>
                    <?php else: foreach ($attendance as $att): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime((string) $att['check_in']))) ?></td>
                            <td><?= !empty($att['check_out']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $att['check_out']))) : '—' ?></td>
                            <td><?= htmlspecialchars((string) ($att['branch_name'] ?? '—')) ?></td>
                            <td><?= Admin::badge((string) $att['source']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
