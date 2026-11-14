<?php
ob_start();
/**
 * Admin Dashboard
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// 1. Fetch Metrics
// Total Students
$stmt = $pdo->query("SELECT COUNT(*) FROM students");
$totalStudents = (int)$stmt->fetchColumn();

// Total Classes
$stmt = $pdo->query("SELECT COUNT(*) FROM classes");
$totalClasses = (int)$stmt->fetchColumn();

// Total Teachers
$stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
$totalTeachers = (int)$stmt->fetchColumn();

// Total Pending Fees
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE status IN ('Unpaid', 'Pending')");
$pendingFees = (float)$stmt->fetchColumn();

// Total Collected Fees
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE status = 'Paid'");
$collectedFees = (float)$stmt->fetchColumn();

// 2. Today's Attendance Snapshot
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM attendance 
    WHERE date = ? 
    GROUP BY status
");
$stmt->execute([$today]);
$attendanceStats = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
while ($row = $stmt->fetch()) {
    $attendanceStats[$row['status']] = (int)$row['count'];
}
$totalMarkedToday = array_sum($attendanceStats);

// 3. Recent Students
$stmt = $pdo->query("
    SELECT s.*, c.class_name, c.section 
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY s.id DESC 
    LIMIT 5
");
$recentStudents = $stmt->fetchAll();

// 4. Recent Fee Invoices
$stmt = $pdo->query("
    SELECT f.*, s.name as student_name, s.roll_no
    FROM fees f
    JOIN students s ON f.student_id = s.id
    ORDER BY f.id DESC 
    LIMIT 5
");
$recentFees = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Administrative Overview</h3>
        <p class="text-muted small mb-0">Welcome back to EduManage control center. Here is your institutional snapshot.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>admin/student_add.php" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-person-plus-fill"></i> Add Student
        </a>
        <a href="<?php echo BASE_URL; ?>admin/attendance.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-calendar-check"></i> Mark Attendance
        </a>
    </div>
</div>

<!-- 4 Key Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Total Students -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Total Enrolled</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?php echo number_format($totalStudents); ?></h3>
                    <small class="text-success fw-medium"><i class="bi bi-arrow-up-short"></i> Active records</small>
                </div>
                <div class="stat-icon bg-stat-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Classes -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Classes &amp; Batches</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?php echo number_format($totalClasses); ?></h3>
                    <small class="text-primary fw-medium"><i class="bi bi-check2"></i> Active curriculums</small>
                </div>
                <div class="stat-icon bg-stat-info">
                    <i class="bi bi-journal-text"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Teachers -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Faculty / Staff</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?php echo number_format($totalTeachers); ?></h3>
                    <small class="text-secondary fw-medium"><i class="bi bi-person-check"></i> Instructors</small>
                </div>
                <div class="stat-icon bg-stat-warning">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Fees -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Pending Receivables</span>
                    <h3 class="fw-bold text-danger mt-2 mb-0"><?php echo CURRENCY_SYMBOL . number_format($pendingFees, 2); ?></h3>
                    <small class="text-muted"><span class="text-success fw-semibold"><?php echo CURRENCY_SYMBOL . number_format($collectedFees, 2); ?></span> collected</small>
                </div>
                <div class="stat-icon bg-stat-danger">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance & Quick Actions Row -->
<div class="row g-4 mb-4">
    <!-- Today's Attendance Breakdown -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0 text-slate-800"><i class="bi bi-calendar-event me-2 text-primary"></i> Today's Attendance Overview (<?php echo date('M d, Y'); ?>)</h6>
                <a href="<?php echo BASE_URL; ?>admin/attendance.php" class="btn btn-sm btn-light border small">Manage Attendance</a>
            </div>
            <div class="card-body">
                <?php if ($totalMarkedToday === 0): ?>
                    <div class="text-center py-4">
                        <div class="text-muted mb-2"><i class="bi bi-calendar-x fs-1"></i></div>
                        <p class="text-muted mb-2">No attendance has been logged yet for today.</p>
                        <a href="<?php echo BASE_URL; ?>admin/attendance.php" class="btn btn-primary btn-sm">Take Daily Attendance Now</a>
                    </div>
                <?php else: ?>
                    <div class="row text-center g-3 mb-3">
                        <div class="col-3">
                            <div class="p-3 bg-light rounded-3">
                                <span class="d-block small text-muted text-uppercase fw-semibold">Present</span>
                                <h4 class="fw-bold text-success mb-0"><?php echo $attendanceStats['Present']; ?></h4>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 bg-light rounded-3">
                                <span class="d-block small text-muted text-uppercase fw-semibold">Absent</span>
                                <h4 class="fw-bold text-danger mb-0"><?php echo $attendanceStats['Absent']; ?></h4>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 bg-light rounded-3">
                                <span class="d-block small text-muted text-uppercase fw-semibold">Late</span>
                                <h4 class="fw-bold text-warning mb-0"><?php echo $attendanceStats['Late']; ?></h4>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 bg-light rounded-3">
                                <span class="d-block small text-muted text-uppercase fw-semibold">Excused</span>
                                <h4 class="fw-bold text-info mb-0"><?php echo $attendanceStats['Excused']; ?></h4>
                            </div>
                        </div>
                    </div>
                    <?php 
                        $presentPct = ($totalMarkedToday > 0) ? round(($attendanceStats['Present'] / $totalMarkedToday) * 100, 1) : 0;
                    ?>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Attendance Ratio</span>
                        <span class="fw-bold text-dark"><?php echo $presentPct; ?>%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $presentPct; ?>%" aria-valuenow="<?php echo $presentPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-slate-800"><i class="bi bi-lightning-charge-fill me-2 text-warning"></i> Quick Operations</h6>
            </div>
            <div class="card-body d-flex flex-column gap-2 justify-content-center">
                <a href="<?php echo BASE_URL; ?>admin/student_add.php" class="btn btn-light border text-start d-flex align-items-center gap-3 p-2 rounded-3">
                    <div class="stat-icon bg-stat-primary" style="width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-person-plus"></i></div>
                    <div>
                        <div class="fw-semibold text-dark small">Register New Student</div>
                        <small class="text-muted">Enroll student with photo &amp; class</small>
                    </div>
                </a>
                <a href="<?php echo BASE_URL; ?>admin/marks.php" class="btn btn-light border text-start d-flex align-items-center gap-3 p-2 rounded-3">
                    <div class="stat-icon bg-stat-success" style="width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-pencil-square"></i></div>
                    <div>
                        <div class="fw-semibold text-dark small">Grade Book &amp; Marks</div>
                        <small class="text-muted">Enter exam marks &amp; view report cards</small>
                    </div>
                </a>
                <a href="<?php echo BASE_URL; ?>admin/fees.php" class="btn btn-light border text-start d-flex align-items-center gap-3 p-2 rounded-3">
                    <div class="stat-icon bg-stat-warning" style="width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="fw-semibold text-dark small">Fee Invoicing</div>
                        <small class="text-muted">Issue invoices &amp; generate receipts</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Records Row -->
<div class="row g-4">
    <!-- Recently Enrolled Students -->
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0 text-slate-800"><i class="bi bi-person-lines-fill me-2 text-primary"></i> Recently Enrolled Students</h6>
                <a href="<?php echo BASE_URL; ?>admin/students.php" class="btn btn-sm btn-link text-decoration-none small">View All &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Roll Number</th>
                            <th>Class</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentStudents)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No students enrolled yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentStudents as $s): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($s['photo'] ?? 'default-avatar.svg'); ?>" 
                                                 alt="Avatar" class="student-avatar" style="width: 32px; height: 32px;">
                                            <div>
                                                <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($s['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($s['email'] ?? 'No email'); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['roll_no']); ?></span></td>
                                    <td><?php echo htmlspecialchars(($s['class_name'] ?? 'N/A') . ' (' . ($s['section'] ?? '') . ')'); ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo BASE_URL; ?>admin/student_view.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Fee Status -->
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0 text-slate-800"><i class="bi bi-credit-card me-2 text-success"></i> Recent Fee Invoices</h6>
                <a href="<?php echo BASE_URL; ?>admin/fees.php" class="btn btn-sm btn-link text-decoration-none small">View Fees &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentFees)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No fee records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentFees as $f): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($f['student_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($f['title']); ?></small>
                                    </td>
                                    <td class="fw-bold text-dark"><?php echo CURRENCY_SYMBOL . number_format($f['amount'], 2); ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
