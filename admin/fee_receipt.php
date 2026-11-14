<?php
/**
 * Official Fee Payment Receipt (Printable)
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$feeId = (int)($_GET['id'] ?? 0);
$currentUser = getCurrentUser();

if ($feeId <= 0) {
    echo "Invalid fee invoice ID.";
    exit;
}

// Fetch Fee & Student
$stmt = $pdo->prepare("
    SELECT f.*, s.name as student_name, s.roll_no, s.email, s.phone, s.address,
           c.class_name, c.section
    FROM fees f
    JOIN students s ON f.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE f.id = ?
");
$stmt->execute([$feeId]);
$fee = $stmt->fetch();

if (!$fee) {
    echo "Fee invoice not found.";
    exit;
}

// If current user is student, ensure it belongs to them
if ($currentUser['role'] === 'student' && (int)$currentUser['ref_id'] !== (int)$fee['student_id']) {
    setFlashMessage('danger', 'Access denied to this fee receipt.');
    header('Location: ' . BASE_URL . 'student/fees.php');
    exit;
}

// Ensure receipt number exists
if (empty($fee['receipt_no'])) {
    $fee['receipt_no'] = 'REC-' . date('Y') . '-' . str_pad($fee['id'], 5, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt - <?php echo htmlspecialchars($fee['receipt_no']); ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            padding: 30px 15px;
        }
        .receipt-container {
            max-width: 780px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            border: 2px solid #e2e8f0;
            position: relative;
        }
        .receipt-stamp {
            display: inline-block;
            padding: 8px 18px;
            text-transform: uppercase;
            font-weight: 800;
            font-size: 1.1rem;
            color: #10b981;
            border: 3px solid #10b981;
            border-radius: 8px;
            transform: rotate(-8deg);
            letter-spacing: 0.1em;
        }
        .table thead th {
            background-color: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
            font-size: 0.82rem;
            text-transform: uppercase;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            width: 180px;
            margin: 50px 0 5px auto;
        }
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                padding: 25px !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="max-w-780 mx-auto mb-4 text-center no-print" style="max-width: 780px;">
    <div class="d-flex justify-content-between align-items-center">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="bi bi-printer-fill me-1"></i> Print / Download PDF
        </button>
    </div>
</div>

<div class="receipt-container">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between border-bottom pb-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div>
                <h4 class="fw-bold text-dark mb-0"><?php echo APP_NAME; ?></h4>
                <div class="text-muted small">Official Accounts &amp; Bursar Department</div>
                <div class="text-muted small">Campus Drive, Academic City &bull; +1 (555) 123-4567</div>
            </div>
        </div>
        <div class="text-end">
            <div class="receipt-stamp">
                <?php echo ($fee['status'] === 'Paid') ? 'PAID &bull; CLEARED' : strtoupper($fee['status']); ?>
            </div>
        </div>
    </div>

    <!-- Receipt Meta -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <h6 class="text-muted small text-uppercase fw-semibold mb-1">Receipt Issued To:</h6>
            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($fee['student_name']); ?></h5>
            <div class="text-muted small">Roll No: <strong><?php echo htmlspecialchars($fee['roll_no']); ?></strong></div>
            <div class="text-muted small">Class / Batch: <strong><?php echo htmlspecialchars($fee['class_name'] . ' (' . $fee['section'] . ')'); ?></strong></div>
            <?php if (!empty($fee['phone'])): ?>
                <div class="text-muted small">Phone: <?php echo htmlspecialchars($fee['phone']); ?></div>
            <?php endif; ?>
        </div>
        <div class="col-sm-6 text-sm-end">
            <h6 class="text-muted small text-uppercase fw-semibold mb-1">Receipt Details:</h6>
            <div class="mb-1"><strong class="text-dark">Receipt #:</strong> <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($fee['receipt_no']); ?></span></div>
            <div class="mb-1 text-muted small"><strong>Payment Date:</strong> <?php echo $fee['payment_date'] ? date('M d, Y', strtotime($fee['payment_date'])) : date('M d, Y'); ?></div>
            <div class="text-muted small"><strong>Academic Year:</strong> 2024 - 2025</div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Fee Description / Particulars</th>
                    <th class="text-center" style="width: 140px;">Status</th>
                    <th class="text-end" style="width: 160px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <strong class="text-dark"><?php echo htmlspecialchars($fee['title']); ?></strong>
                        <div class="small text-muted">Billed for standard academic curriculum and student services.</div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success px-2 py-1"><?php echo htmlspecialchars($fee['status']); ?></span>
                    </td>
                    <td class="text-end fw-bold"><?php echo CURRENCY_SYMBOL . number_format($fee['amount'], 2); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <td colspan="3" class="text-end fw-bold">Grand Total Paid:</td>
                    <td class="text-end fw-bold text-primary fs-5"><?php echo CURRENCY_SYMBOL . number_format($fee['amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Payment Note & Signatures -->
    <div class="row align-items-center mt-5 pt-3">
        <div class="col-sm-7 small text-muted">
            <p class="mb-1"><strong>Terms &amp; Notes:</strong></p>
            <p class="mb-0">This receipt acknowledges full receipt of payment for the specified line items. Fees paid are non-refundable according to institutional academic bylaws.</p>
        </div>
        <div class="col-sm-5 text-end">
            <div class="signature-line"></div>
            <span class="small text-muted fw-semibold">Authorized Finance Officer</span>
        </div>
    </div>

    <div class="text-center border-top mt-4 pt-3 text-muted small">
        Printed on <?php echo date('M d, Y - h:i A'); ?> &bull; Computer Generated Document &bull; Valid Without Stamp
    </div>
</div>

</body>
</html>
