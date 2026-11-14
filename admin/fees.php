<?php
ob_start();
/**
 * Fee Management
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$errors = [];

// Handle Form Actions (Add, Mark Paid, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $studentId = (int)($_POST['student_id'] ?? 0);
            $title = trim($_POST['title'] ?? 'Tuition Fee');
            $amount = (float)($_POST['amount'] ?? 0);
            $status = $_POST['status'] ?? 'Pending';
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            if ($studentId <= 0 || empty($title) || $amount <= 0) {
                $errors[] = 'Please select a student, provide a fee description, and enter a positive amount.';
            } else {
                $paymentDate = ($status === 'Paid') ? date('Y-m-d') : null;
                $receiptNo = ($status === 'Paid') ? 'REC-' . date('Y') . '-' . rand(10000, 99999) : null;

                $stmt = $pdo->prepare("
                    INSERT INTO fees (student_id, title, amount, status, payment_date, due_date, receipt_no)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$studentId, $title, $amount, $status, $paymentDate, $dueDate, $receiptNo]);
                setFlashMessage('success', "Fee invoice for {$title} created successfully!");
                header('Location: ' . BASE_URL . 'admin/fees.php');
                exit;
            }
        } elseif ($action === 'mark_paid') {
            $feeId = (int)($_POST['fee_id'] ?? 0);
            if ($feeId > 0) {
                $receiptNo = 'REC-' . date('Y') . '-' . rand(10000, 99999);
                $stmt = $pdo->prepare("
                    UPDATE fees 
                    SET status = 'Paid', payment_date = CURDATE(), receipt_no = ?
                    WHERE id = ?
                ");
                $stmt->execute([$receiptNo, $feeId]);
                setFlashMessage('success', 'Fee marked as Paid and receipt generated!');
                header('Location: ' . BASE_URL . 'admin/fees.php');
                exit;
            }
        } elseif ($action === 'delete') {
            $feeId = (int)($_POST['fee_id'] ?? 0);
            if ($feeId > 0) {
                $stmt = $pdo->prepare("DELETE FROM fees WHERE id = ?");
                $stmt->execute([$feeId]);
                setFlashMessage('success', 'Fee invoice deleted.');
                header('Location: ' . BASE_URL . 'admin/fees.php');
                exit;
            }
        }
    }
}

// Filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Metrics
$metricsStmt = $pdo->query("
    SELECT 
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) as paid_amount,
        COALESCE(SUM(CASE WHEN status != 'Paid' THEN amount ELSE 0 END), 0) as pending_amount
    FROM fees
");
$feeMetrics = $metricsStmt->fetch();

// Fetch Students for Dropdown
$studentsList = $pdo->query("SELECT id, name, roll_no FROM students ORDER BY name ASC")->fetchAll();

// Build Query
$where = [];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "f.status = ?";
    $params[] = $statusFilter;
}
if (!empty($search)) {
    $where[] = "(s.name LIKE ? OR s.roll_no LIKE ? OR f.receipt_no LIKE ?)";
    $sw = "%{$search}%";
    $params = array_merge($params, [$sw, $sw, $sw]);
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$feesSql = "
    SELECT f.*, s.name as student_name, s.roll_no, s.photo, c.class_name, c.section
    FROM fees f
    JOIN students s ON f.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    {$whereSql}
    ORDER BY f.id DESC
";
$feesStmt = $pdo->prepare($feesSql);
$feesStmt->execute($params);
$fees = $feesStmt->fetchAll();

$pageTitle = 'Fee Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Fee Management &amp; Invoicing</h3>
        <p class="text-muted small mb-0">Track tuition dues, issue receipts, and monitor institutional receivables.</p>
    </div>
    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#addFeeModal">
        <i class="bi bi-plus-circle"></i> Issue New Fee Invoice
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Financial Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Total Invoiced</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?php echo CURRENCY_SYMBOL . number_format($feeMetrics['total_amount'], 2); ?></h3>
                    <small class="text-secondary">Cumulative billings</small>
                </div>
                <div class="stat-icon bg-stat-primary">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Collected Revenue</span>
                    <h3 class="fw-bold text-success mt-2 mb-0"><?php echo CURRENCY_SYMBOL . number_format($feeMetrics['paid_amount'], 2); ?></h3>
                    <small class="text-success fw-medium"><i class="bi bi-check-circle"></i> Paid invoices</small>
                </div>
                <div class="stat-icon bg-stat-success">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Outstanding Receivables</span>
                    <h3 class="fw-bold text-danger mt-2 mb-0"><?php echo CURRENCY_SYMBOL . number_format($feeMetrics['pending_amount'], 2); ?></h3>
                    <small class="text-danger fw-medium"><i class="bi bi-exclamation-circle"></i> Pending / Unpaid</small>
                </div>
                <div class="stat-icon bg-stat-danger">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="fees.php" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Search student, roll number, receipt ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Paid" <?php echo ($statusFilter === 'Paid') ? 'selected' : ''; ?>>Paid Only</option>
                    <option value="Unpaid" <?php echo ($statusFilter === 'Unpaid') ? 'selected' : ''; ?>>Unpaid Only</option>
                    <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending Only</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="fees.php" class="btn btn-light btn-sm border"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Fee Invoices Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800">Invoices (<?php echo count($fees); ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Invoice / Student</th>
                    <th>Class</th>
                    <th>Fee Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Due Date / Paid</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fees)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No fee records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($fees as $f): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($f['photo'] ?? 'default-avatar.svg'); ?>" 
                                         alt="Avatar" class="student-avatar" style="width: 32px; height: 32px;">
                                    <div>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($f['student_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($f['roll_no']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($f['class_name'] . ' (' . $f['section'] . ')'); ?></span></td>
                            <td><strong class="text-slate-800"><?php echo htmlspecialchars($f['title']); ?></strong></td>
                            <td class="fw-bold text-dark"><?php echo CURRENCY_SYMBOL . number_format($f['amount'], 2); ?></td>
                            <td>
                                <?php if ($f['status'] === 'Paid'): ?>
                                    <span class="badge badge-paid rounded-pill px-2 py-1"><i class="bi bi-check-circle me-1"></i>Paid</span>
                                <?php elseif ($f['status'] === 'Unpaid'): ?>
                                    <span class="badge badge-unpaid rounded-pill px-2 py-1"><i class="bi bi-x-circle me-1"></i>Unpaid</span>
                                <?php else: ?>
                                    <span class="badge badge-pending rounded-pill px-2 py-1"><i class="bi bi-clock me-1"></i>Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?php if ($f['status'] === 'Paid'): ?>
                                    <div>Paid: <?php echo date('M d, Y', strtotime($f['payment_date'])); ?></div>
                                    <span class="text-success small fw-medium"><?php echo htmlspecialchars($f['receipt_no'] ?? ''); ?></span>
                                <?php else: ?>
                                    <div>Due: <?php echo $f['due_date'] ? date('M d, Y', strtotime($f['due_date'])) : 'Open'; ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($f['status'] === 'Paid'): ?>
                                        <a href="<?php echo BASE_URL; ?>admin/fee_receipt.php?id=<?php echo $f['id']; ?>" 
                                           class="btn btn-outline-primary" title="Print Official Receipt">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    <?php else: ?>
                                        <form method="POST" action="fees.php" class="d-inline">
                                            <input type="hidden" name="action" value="mark_paid">
                                            <input type="hidden" name="fee_id" value="<?php echo $f['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                            <button type="submit" class="btn btn-outline-success" title="Mark as Paid">
                                                <i class="bi bi-check2-circle"></i> Pay
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="fees.php" class="d-inline" onsubmit="return confirm('Delete this fee record?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="fee_id" value="<?php echo $f['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Fee Modal -->
<div class="modal fade" id="addFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="fees.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Issue New Fee Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach ($studentsList as $st): ?>
                                <option value="<?php echo $st['id']; ?>">
                                    <?php echo htmlspecialchars($st['name'] . ' (' . $st['roll_no'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a student.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fee Title / Description <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Fall Semester Tuition, Lab Fee" required>
                        <div class="invalid-feedback">Fee title is required.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Amount (<?php echo CURRENCY_SYMBOL; ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="1000.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Initial Status</label>
                            <select name="status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
