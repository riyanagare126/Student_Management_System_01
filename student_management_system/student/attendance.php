<?php
/**
 * Student Attendance History
 * Student Management System
 */
$pageTitle = 'My Attendance';
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

// Attendance Counts
$attStmt = $pdo->prepare("
    SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status
");
$attStmt->execute([$studentId]);
$counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
while ($row = $attStmt->fetch()) {
    $counts[$row['status']] = (int)$row['count'];
}
$totalDays = array_sum($counts);
$presentPercent = ($totalDays > 0) ? round((($counts['Present'] + $counts['Late']) / $totalDays) * 100, 1) : 100;

// Attendance Logs
$logsStmt = $pdo->prepare("
    SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC
");
$logsStmt->execute([$studentId]);
$logs = $logsStmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">My Attendance Record</h3>
        <p class="text-muted small mb-0">Track your daily roll call status and institutional attendance percentage.</p>
    </div>
</div>

<!-- Attendance Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Overall Attendance</span>
            <h3 class="fw-bold <?php echo ($presentPercent >= 75) ? 'text-success' : 'text-danger'; ?> mt-2 mb-0">
                <?php echo $presentPercent; ?>%
            </h3>
            <small class="text-muted"><?php echo ($presentPercent >= 75) ? 'Meets 75% standard' : 'Below 75% threshold'; ?></small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Present Days</span>
            <h3 class="fw-bold text-success mt-2 mb-0"><?php echo $counts['Present']; ?></h3>
            <small class="text-muted">On-time sessions</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Absent Days</span>
            <h3 class="fw-bold text-danger mt-2 mb-0"><?php echo $counts['Absent']; ?></h3>
            <small class="text-muted">Unexcused absences</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Late / Excused</span>
            <h3 class="fw-bold text-warning mt-2 mb-0"><?php echo $counts['Late'] + $counts['Excused']; ?></h3>
            <small class="text-muted"><?php echo $counts['Late']; ?> Late, <?php echo $counts['Excused']; ?> Excused</small>
        </div>
    </div>
</div>

<!-- Attendance Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800">Attendance Log History (<?php echo count($logs); ?> Days Recorded)</h6>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day of Week</th>
                    <th>Attendance Status</th>
                    <th>Remarks / Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">No attendance logs available for your account yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><strong><?php echo date('M d, Y', strtotime($l['date'])); ?></strong></td>
                            <td class="text-muted"><?php echo date('l', strtotime($l['date'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($l['status']); ?> rounded-pill px-3 py-1">
                                    <?php echo htmlspecialchars($l['status']); ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo htmlspecialchars($l['remarks'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
