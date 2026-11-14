<?php
ob_start();
/**
 * Detailed 360-degree Student Profile
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid student ID.');
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

// 1. Fetch Student Details
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section, c.room_no, u.username 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlashMessage('danger', 'Student not found.');
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

// 2. Attendance Summary
$attStmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM attendance 
    WHERE student_id = ? 
    GROUP BY status
");
$attStmt->execute([$id]);
$attendanceStats = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
while ($row = $attStmt->fetch()) {
    $attendanceStats[$row['status']] = (int)$row['count'];
}
$totalDays = array_sum($attendanceStats);
$presentRate = ($totalDays > 0) ? round((($attendanceStats['Present'] + $attendanceStats['Late']) / $totalDays) * 100, 1) : 0;

// Recent Attendance logs
$attLogsStmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE student_id = ? 
    ORDER BY date DESC 
    LIMIT 10
");
$attLogsStmt->execute([$id]);
$recentAttendance = $attLogsStmt->fetchAll();

// 3. Academic Marks
$marksStmt = $pdo->prepare("
    SELECT * FROM marks 
    WHERE student_id = ? 
    ORDER BY id DESC
");
$marksStmt->execute([$id]);
$marks = $marksStmt->fetchAll();

$totalObtained = 0;
$totalMax = 0;
foreach ($marks as $m) {
    $totalObtained += $m['marks_obtained'];
    $totalMax += $m['total_marks'];
}
$overallGrade = calculateGrade($totalObtained, $totalMax > 0 ? $totalMax : 100);

// 4. Fees Summary
$feesStmt = $pdo->prepare("
    SELECT * FROM fees 
    WHERE student_id = ? 
    ORDER BY id DESC
");
$feesStmt->execute([$id]);
$fees = $feesStmt->fetchAll();

$totalFees = 0;
$paidFees = 0;
$pendingFees = 0;
foreach ($fees as $f) {
    $totalFees += $f['amount'];
    if ($f['status'] === 'Paid') {
        $paidFees += $f['amount'];
    } else {
        $pendingFees += $f['amount'];
    }
}

$pageTitle = 'Student Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header & Quick Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1"><?php echo htmlspecialchars($student['name']); ?></h3>
        <p class="text-muted small mb-0">Student ID: <strong><?php echo htmlspecialchars($student['roll_no']); ?></strong> &bull; Class: <strong><?php echo htmlspecialchars($student['class_name'] . ' (' . $student['section'] . ')'); ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>admin/report_card.php?id=<?php echo $id; ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-award-fill"></i> View Report Card
        </a>
        <a href="<?php echo BASE_URL; ?>admin/student_edit.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-pencil-square"></i> Edit Profile
        </a>
        <a href="<?php echo BASE_URL; ?>admin/students.php" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<!-- Profile Overview Card -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4 h-100">
            <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($student['photo'] ?? 'default-avatar.svg'); ?>" 
                 alt="Student Avatar" class="avatar-lg mx-auto mb-3">
            <h5 class="fw-bold text-slate-800 mb-1"><?php echo htmlspecialchars($student['name']); ?></h5>
            <div class="text-muted small mb-2"><?php echo htmlspecialchars($student['email'] ?? 'No email'); ?></div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-4">
                Roll No: <?php echo htmlspecialchars($student['roll_no']); ?>
            </span>

            <div class="border-top pt-3 text-start small">
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Class &amp; Section:</span>
                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($student['class_name'] . ' (' . $student['section'] . ')'); ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Gender:</span>
                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Date of Birth:</span>
                    <span class="fw-semibold text-dark"><?php echo !empty($student['dob']) ? date('M d, Y', strtotime($student['dob'])) : 'N/A'; ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Phone:</span>
                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Enrolled Since:</span>
                    <span class="fw-semibold text-dark"><?php echo date('M d, Y', strtotime($student['created_at'])); ?></span>
                </div>
                <?php if (!empty($student['username'])): ?>
                    <div class="d-flex justify-content-between py-1 border-top mt-2 pt-2">
                        <span class="text-muted">Portal Account:</span>
                        <span class="badge bg-success-subtle text-success">@<?php echo htmlspecialchars($student['username']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3 Quick Progress / Summary Cards -->
    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <!-- Attendance Rate -->
            <div class="col-md-4">
                <div class="card stat-card border-0 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Attendance</span>
                            <h3 class="fw-bold text-dark mt-2 mb-0"><?php echo $presentRate; ?>%</h3>
                            <small class="text-success"><?php echo $attendanceStats['Present']; ?> of <?php echo $totalDays; ?> days present</small>
                        </div>
                        <div class="stat-icon bg-stat-success">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Standing -->
            <div class="col-md-4">
                <div class="card stat-card border-0 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Academic Standing</span>
                            <h3 class="fw-bold text-primary mt-2 mb-0">Grade <?php echo $overallGrade['grade']; ?></h3>
                            <small class="text-muted"><?php echo $overallGrade['percentage']; ?>% Avg (GPA <?php echo $overallGrade['gpa']; ?>)</small>
                        </div>
                        <div class="stat-icon bg-stat-primary">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Status -->
            <div class="col-md-4">
                <div class="card stat-card border-0 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Fee Due</span>
                            <h3 class="fw-bold <?php echo ($pendingFees > 0) ? 'text-danger' : 'text-success'; ?> mt-2 mb-0">
                                <?php echo CURRENCY_SYMBOL . number_format($pendingFees, 2); ?>
                            </h3>
                            <small class="text-muted"><?php echo CURRENCY_SYMBOL . number_format($paidFees, 2); ?> paid</small>
                        </div>
                        <div class="stat-icon bg-stat-warning">
                            <i class="bi bi-credit-card"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabbed Sections: Marks, Attendance, Fees -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent p-2">
                <ul class="nav nav-tabs card-header-tabs border-0" id="studentTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="marks-tab" data-bs-toggle="tab" data-bs-target="#marks-pane" type="button" role="tab">
                            <i class="bi bi-award me-1"></i> Marks &amp; Grades (<?php echo count($marks); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance-pane" type="button" role="tab">
                            <i class="bi bi-calendar-check me-1"></i> Attendance Logs
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees-pane" type="button" role="tab">
                            <i class="bi bi-receipt me-1"></i> Fee Ledger (<?php echo count($fees); ?>)
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="studentTabContent">
                    <!-- Tab 1: Marks -->
                    <div class="tab-pane fade show active" id="marks-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Exam / Term</th>
                                        <th>Marks Obtained</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($marks)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No marks recorded yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($marks as $m): 
                                            $gradeInfo = calculateGrade($m['marks_obtained'], $m['total_marks']);
                                        ?>
                                            <tr>
                                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($m['subject']); ?></td>
                                                <td><span class="badge bg-light text-muted border"><?php echo htmlspecialchars($m['exam_type']); ?></span></td>
                                                <td>
                                                    <strong><?php echo $m['marks_obtained']; ?></strong> / <?php echo $m['total_marks']; ?>
                                                    <span class="text-muted small">(<?php echo $gradeInfo['percentage']; ?>%)</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $gradeInfo['badge']; ?>-subtle text-<?php echo $gradeInfo['badge']; ?> px-2 py-1">
                                                        Grade <?php echo $gradeInfo['grade']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Attendance -->
                    <div class="tab-pane fade" id="attendance-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentAttendance)): ?>
                                        <tr><td colspan="3" class="text-center py-4 text-muted">No attendance logs available.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentAttendance as $att): ?>
                                            <tr>
                                                <td class="fw-semibold text-dark"><?php echo date('D, M d, Y', strtotime($att['date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo strtolower($att['status']); ?> px-2 py-1 rounded-pill">
                                                        <?php echo htmlspecialchars($att['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted small"><?php echo htmlspecialchars($att['remarks'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Fees -->
                    <div class="tab-pane fade" id="fees-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Fee Item</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-end">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fees)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No fee invoices issued.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($fees as $f): ?>
                                            <tr>
                                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($f['title']); ?></td>
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
                                                <td class="small text-muted">
                                                    <?php echo $f['payment_date'] ? date('M d, Y', strtotime($f['payment_date'])) : 'Due: ' . date('M d, Y', strtotime($f['due_date'])); ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($f['status'] === 'Paid'): ?>
                                                        <a href="<?php echo BASE_URL; ?>admin/fee_receipt.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-light border" title="Print Receipt">
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
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
