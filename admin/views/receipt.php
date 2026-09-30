<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h1 class="h3 mb-0">Receipt <?= htmlspecialchars((string) ($payment['receipt_no'] ?? '')) ?></h1>
    <div class="d-flex gap-2">
        <button class="btn btn-warning" type="button" onclick="window.print()">Print</button>
        <a class="btn btn-outline-light" href="<?= htmlspecialchars($base) ?>/members/<?= (int) $payment['user_id'] ?>">Back to member</a>
    </div>
</div>
<div class="receipt card-panel p-4">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
        <div>
            <div class="brand fs-4"><?= htmlspecialchars((string) $gymName) ?></div>
            <div class="text-secondary">Payment receipt</div>
        </div>
        <div class="text-end">
            <div><strong><?= htmlspecialchars((string) ($payment['receipt_no'] ?? '')) ?></strong></div>
            <div class="text-secondary"><?= htmlspecialchars(date('d M Y, h:i A', strtotime((string) $payment['created_at']))) ?></div>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-6">
            <div class="text-secondary small">Member</div>
            <div><?= htmlspecialchars((string) ($member['name'] ?? '—')) ?></div>
            <div class="text-secondary"><?= htmlspecialchars((string) ($member['member_id'] ?? '')) ?></div>
        </div>
        <div class="col-6 text-end">
            <div class="text-secondary small">Mobile</div>
            <div><?= htmlspecialchars((string) ($member['mobile'] ?? '—')) ?></div>
        </div>
    </div>
    <table class="table table-dark align-middle mb-0">
        <thead>
            <tr><th>Plan</th><th>Validity</th><th>Mode</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars((string) ($payment['package_name'] ?? 'Custom')) ?></td>
                <td>
                    <?php if (!empty($payment['start_date']) && !empty($payment['end_date'])): ?>
                        <?= htmlspecialchars(date('d M Y', strtotime((string) $payment['start_date']))) ?> – <?= htmlspecialchars(date('d M Y', strtotime((string) $payment['end_date']))) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(strtoupper((string) $payment['mode'])) ?><?= !empty($payment['txn_ref']) ? ' · ' . htmlspecialchars((string) $payment['txn_ref']) : '' ?></td>
                <td class="text-end fw-semibold"><?= htmlspecialchars(Admin::money($payment['amount'])) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total paid</th>
                <th class="text-end"><?= htmlspecialchars(Admin::money($payment['amount'])) ?></th>
            </tr>
        </tfoot>
    </table>
    <p class="text-secondary small mt-3 mb-0">Status: <?= Admin::badge((string) $payment['status']) ?> · This is a computer generated receipt.</p>
</div>
