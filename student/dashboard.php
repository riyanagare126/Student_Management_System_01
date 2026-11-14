<?php
/**
 * Student Dashboard
 * Student Management System
 */
$pageTitle = 'Student Portal';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';

requireRole('student');

$currentUser = getCurrentUser();
$studentId = (int)($currentUser['ref_id'] ?? 0);

// If ref_id not loaded, find student by user_id
if ($studentId <= 0) {
    $stStmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stStmt->execute([$currentUser['id']]);
    $studentId = (int)$stStmt->fetchColumn();
}

// Fetch Full Student Info
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section, c.room_no
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div class='alert alert-danger'>Student profile link not found. Please contact administration.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Attendance Stats
$attStmt = $pdo->prepare("
    SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status
");
$attStmt->execute([$studentId]);
$attCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
while ($row = $attStmt->fetch()) {
    $attCounts[$row['status']] = (int)$row['count'];
}
$totalDays = array_sum($attCounts);
$attPercent = ($totalDays > 0) ? round((($attCounts['Present'] + $attCounts['Late']) / $totalDays) * 100, 1) : 100;

// Marks Stats
$mStmt = $pdo->prepare("SELECT * FROM marks WHERE student_id = ? ORDER BY id DESC");
$mStmt->execute([$studentId]);
$marks = $mStmt->fetchAll();

$totalObtained = 0;
$totalMax = 0;
foreach ($marks as $m) {
    $totalObtained += (float)$m['marks_obtained'];
    $totalMax += (float)$m['total_marks'];
}
$gradeData = calculateGrade($totalObtained, $totalMax > 0 ? $totalMax : 100);

// Fees Stats
$fStmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY id DESC");
$fStmt->execute([$studentId]);
$fees = $fStmt->fetchAll();

$dueAmount = 0;
foreach ($fees as $f) {
    if ($f['status'] !== 'Paid') {
        $dueAmount += (float)$f['amount'];
    }
}
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm text-white mb-4" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #4338ca 100%); border-radius: 16px;">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($student['photo'] ?? 'default-avatar.svg'); ?>" 
                     alt="Avatar" class="avatar-lg border-2 border-white">
            </div>
            <div class="col">
                <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-1 mb-2">Student Portal</span>
                <h3 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($student['name']); ?>!</h3>
                <p class="mb-0 text-white-50 small">
                    Roll No: <strong><?php echo htmlspecialchars($student['roll_no']); ?></strong> &bull; 
                    Class: <strong><?php echo htmlspecialchars($student['class_name'] . ' (' . $student['section'] . ')'); ?></strong> &bull; 
                    Room: <strong><?php echo htmlspecialchars($student['room_no'] ?? 'Main'); ?></strong>
                </p>
            </div>
            <div class="col-auto text-end d-none d-md-block">
                <a href="<?php echo BASE_URL; ?>admin/report_card.php?id=<?php echo $studentId; ?>" class="btn btn-light btn-sm shadow-sm" target="_blank">
                    <i class="bi bi-award-fill text-primary me-1"></i> View Report Card
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 3 Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Attendance -->
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">My Attendance</span>
                    <h3 class="fw-bold text-success mt-2 mb-0"><?php echo $attPercent; ?>%</h3>
                    <small class="text-muted"><?php echo $attCounts['Present']; ?> Present / <?php echo $totalDays; ?> Sessions</small>
                </div>
                <div class="stat-icon bg-stat-success">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- GPA & Grade -->
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Academic Standing</span>
                    <h3 class="fw-bold text-primary mt-2 mb-0">GPA <?php echo number_format($gradeData['gpa'], 2); ?></h3>
                    <small class="text-muted">Grade <strong><?php echo $gradeData['grade']; ?></strong> (<?php echo $gradeData['percentage']; ?>% Avg)</small>
                </div>
                <div class="stat-icon bg-stat-primary">
                    <i class="bi bi-mortarboard"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Due -->
    <div class="col-md-4">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Fee Due Status</span>
                    <h3 class="fw-bold <?php echo ($dueAmount > 0) ? 'text-danger' : 'text-success'; ?> mt-2 mb-0">
                        <?php echo CURRENCY_SYMBOL . number_format($dueAmount, 2); ?>
                    </h3>
                    <small class="text-muted"><?php echo ($dueAmount > 0) ? 'Action Required' : 'All Clear'; ?></small>
                </div>
                <div class="stat-icon bg-stat-warning">
                    <i class="bi bi-credit-card"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Marks & Fees Grid -->
<div class="row g-4">
    <!-- Academic Marks -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0 text-slate-800"><i class="bi bi-award me-2 text-primary"></i> Subject Performance</h6>
                <a href="<?php echo BASE_URL; ?>student/marks.php" class="btn btn-sm btn-link text-decoration-none small">All Marks &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam</th>
                            <th>Marks</th>
                            <th class="text-end">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($marks)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No marks published yet.</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($marks, 0, 5) as $m): 
                                $sub = calculateGrade($m['marks_obtained'], $m['total_marks']);
                            ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($m['subject']); ?></td>
                                    <td><span class="badge bg-light text-muted border"><?php echo htmlspecialchars($m['exam_type']); ?></span></td>
                                    <td><strong><?php echo $m['marks_obtained']; ?></strong> / <?php echo $m['total_marks']; ?></td>
                                    <td class="text-end">
                                        <span class="badge bg-<?php echo $sub['badge']; ?>-subtle text-<?php echo $sub['badge']; ?> px-2 py-1">
                                            <?php echo $sub['grade']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Fee Invoices -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0 text-slate-800"><i class="bi bi-receipt me-2 text-success"></i> Tuition &amp; Fee Status</h6>
                <a href="<?php echo BASE_URL; ?>student/fees.php" class="btn btn-sm btn-link text-decoration-none small">All Fees &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fees)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No fee records found.</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($fees, 0, 4) as $f): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($f['title']); ?></div>
                                        <small class="text-muted"><?php echo $f['payment_date'] ? date('M d, Y', strtotime($f['payment_date'])) : 'Due: ' . date('M d', strtotime($f['due_date'])); ?></small>
                                    </td>
                                    <td class="fw-bold"><?php echo CURRENCY_SYMBOL . number_format($f['amount'], 2); ?></td>
                                    <td>
                                        <?php if ($f['status'] === 'Paid'): ?>
                                            <span class="badge badge-paid rounded-pill px-2 py-1">Paid</span>
                                        <?php elseif ($f['status'] === 'Unpaid'): ?>
                                            <span class="badge badge-unpaid rounded-pill px-2 py-1">Unpaid</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending rounded-pill px-2 py-1">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($f['status'] === 'Paid'): ?>
                                            <a href="<?php echo BASE_URL; ?>admin/fee_receipt.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-light border" title="Receipt">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
