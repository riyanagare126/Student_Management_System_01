<?php
/**
 * Student Marks & Academic Results
 * Student Management System
 */
$pageTitle = 'My Marks & Results';
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

// Fetch Marks
$mStmt = $pdo->prepare("SELECT * FROM marks WHERE student_id = ? ORDER BY id DESC");
$mStmt->execute([$studentId]);
$marks = $mStmt->fetchAll();

$totalObtained = 0;
$totalMax = 0;
foreach ($marks as $m) {
    $totalObtained += (float)$m['marks_obtained'];
    $totalMax += (float)$m['total_marks'];
}
$overall = calculateGrade($totalObtained, $totalMax > 0 ? $totalMax : 100);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Academic Evaluations &amp; Marks</h3>
        <p class="text-muted small mb-0">View subject scores, exam performance, and download your official report card.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/report_card.php?id=<?php echo $studentId; ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" target="_blank">
            <i class="bi bi-printer-fill me-1"></i> Print Official Report Card
        </a>
    </div>
</div>

<!-- Academic Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Cumulative Average</span>
            <h3 class="fw-bold text-dark mt-2 mb-0"><?php echo $overall['percentage']; ?>%</h3>
            <small class="text-muted"><?php echo number_format($totalObtained, 1); ?> of <?php echo number_format($totalMax, 1); ?> points</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Grade Point Average</span>
            <h3 class="fw-bold text-primary mt-2 mb-0"><?php echo number_format($overall['gpa'], 2); ?></h3>
            <small class="text-muted">4.0 Academic scale</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Overall Grade</span>
            <h3 class="fw-bold text-success mt-2 mb-0">Grade <?php echo $overall['grade']; ?></h3>
            <small class="text-muted">Certified standing</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Course Status</span>
            <h3 class="fw-bold <?php echo ($overall['percentage'] >= 50) ? 'text-success' : 'text-danger'; ?> mt-2 mb-0">
                <?php echo ($overall['percentage'] >= 50) ? 'PASSED' : 'NEEDS ATTENTION'; ?>
            </h3>
            <small class="text-muted">Current semester evaluation</small>
        </div>
    </div>
</div>

<!-- Marks Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800">Evaluated Subjects (<?php echo count($marks); ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Subject Name</th>
                    <th>Exam / Assessment</th>
                    <th>Marks Obtained</th>
                    <th>Maximum Marks</th>
                    <th>Score %</th>
                    <th class="text-end">Assigned Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marks)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No marks published for your account yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($marks as $i => $m): 
                        $sub = calculateGrade($m['marks_obtained'], $m['total_marks']);
                    ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($m['subject']); ?></strong></td>
                            <td><span class="badge bg-light text-muted border"><?php echo htmlspecialchars($m['exam_type']); ?></span></td>
                            <td class="fw-bold text-dark"><?php echo $m['marks_obtained']; ?></td>
                            <td class="text-muted"><?php echo $m['total_marks']; ?></td>
                            <td><?php echo $sub['percentage']; ?>%</td>
                            <td class="text-end">
                                <span class="badge bg-<?php echo $sub['badge']; ?>-subtle text-<?php echo $sub['badge']; ?> border px-3 py-1">
                                    Grade <?php echo $sub['grade']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
