<?php
/**
 * Student Fees & Receipts
 * Student Management System
 */
$pageTitle = 'My Fees & Payments';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';

requireRole('student');

$currentUser = getCurrentUser();
$studentId = (int)($currentUser['ref_id'] ?? 0);

if ($studentId <= 0) {
    $stStmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stStmt->execute([$currentUser['id']]);
    $studentId = (int)$stStmt->fetchColumn();
}

// Fetch Fees
$fStmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY id DESC");
$fStmt->execute([$studentId]);
$fees = $fStmt->fetchAll();

$totalAmount = 0;
$paidAmount = 0;
$pendingAmount = 0;

foreach ($fees as $f) {
    $totalAmount += (float)$f['amount'];
    if ($f['status'] === 'Paid') {
        $paidAmount += (float)$f['amount'];
    } else {
        $pendingAmount += (float)$f['amount'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Tuition &amp; Fees Ledger</h3>
        <p class="text-muted small mb-0">Review billed institutional fees, payment history, and download official receipts.</p>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Total Invoiced</span>
            <h3 class="fw-bold text-dark mt-2 mb-0"><?php echo CURRENCY_SYMBOL . number_format($totalAmount, 2); ?></h3>
            <small class="text-muted">Total fee obligations</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Total Cleared / Paid</span>
            <h3 class="fw-bold text-success mt-2 mb-0"><?php echo CURRENCY_SYMBOL . number_format($paidAmount, 2); ?></h3>
            <small class="text-success fw-medium"><i class="bi bi-check-circle"></i> Successfully paid</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Outstanding Due</span>
            <h3 class="fw-bold <?php echo ($pendingAmount > 0) ? 'text-danger' : 'text-success'; ?> mt-2 mb-0">
                <?php echo CURRENCY_SYMBOL . number_format($pendingAmount, 2); ?>
            </h3>
            <small class="<?php echo ($pendingAmount > 0) ? 'text-danger' : 'text-muted'; ?>">
                <?php echo ($pendingAmount > 0) ? 'Payment pending' : 'No dues remaining'; ?>
            </small>
        </div>
    </div>
</div>

<!-- Fees Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800">Fee Invoices &amp; Receipts (<?php echo count($fees); ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Fee Description</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Payment Date</th>
                    <th>Status</th>
                    <th class="text-end">Official Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fees)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No fee invoices issued for your account.</td></tr>
                <?php else: ?>
                    <?php foreach ($fees as $f): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($f['title']); ?></strong>
                                <?php if (!empty($f['receipt_no'])): ?>
                                    <div class="small text-muted">Receipt Ref: <?php echo htmlspecialchars($f['receipt_no']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-dark"><?php echo CURRENCY_SYMBOL . number_format($f['amount'], 2); ?></td>
                            <td class="text-muted"><?php echo $f['due_date'] ? date('M d, Y', strtotime($f['due_date'])) : 'N/A'; ?></td>
                            <td class="text-muted"><?php echo $f['payment_date'] ? date('M d, Y', strtotime($f['payment_date'])) : '-'; ?></td>
                            <td>
                                <?php if ($f['status'] === 'Paid'): ?>
                                    <span class="badge badge-paid rounded-pill px-3 py-1"><i class="bi bi-check-circle me-1"></i>Paid</span>
                                <?php elseif ($f['status'] === 'Unpaid'): ?>
                                    <span class="badge badge-unpaid rounded-pill px-3 py-1"><i class="bi bi-exclamation-circle me-1"></i>Unpaid</span>
                                <?php else: ?>
                                    <span class="badge badge-pending rounded-pill px-3 py-1"><i class="bi bi-clock me-1"></i>Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($f['status'] === 'Paid'): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/fee_receipt.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-primary shadow-sm" target="_blank">
                                        <i class="bi bi-printer me-1"></i> View Receipt
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small italic">Available upon payment</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
