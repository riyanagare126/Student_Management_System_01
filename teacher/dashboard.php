<?php
/**
 * Faculty / Teacher Portal Dashboard
 * Student Management System
 */
$pageTitle = 'Teacher Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';

requireRole(['teacher', 'admin']);

$currentUser = getCurrentUser();

// Fetch Teacher Info
$teacherStmt = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacherStmt->execute([$currentUser['id']]);
$teacher = $teacherStmt->fetch();

// Metrics
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalClasses = (int)$pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$totalMarksRecorded = (int)$pdo->query("SELECT COUNT(*) FROM marks")->fetchColumn();

// Today's Attendance count
$todayAttendance = (int)$pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE()")->fetchColumn();

// Recent Marks Recorded
$recentMarks = $pdo->query("
    SELECT m.*, s.name as student_name, s.roll_no, c.class_name, c.section
    FROM marks m
    JOIN students s ON m.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY m.id DESC LIMIT 5
")->fetchAll();
?>

<!-- Banner -->
<div class="card border-0 shadow-sm text-white mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%); border-radius: 16px;">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-1 mb-2">Faculty Portal</span>
                <h3 class="fw-bold mb-1">Hello, <?php echo htmlspecialchars($teacher['name'] ?? $currentUser['name']); ?>!</h3>
                <p class="mb-0 text-white-50 small">
                    Department: <strong><?php echo htmlspecialchars($teacher['subject'] ?? 'Faculty Staff'); ?></strong> &bull; 
                    Academic Session: <strong>2024 - 2025</strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>admin/attendance.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-calendar-check me-1"></i> Take Attendance
                </a>
                <a href="<?php echo BASE_URL; ?>admin/marks.php" class="btn btn-light btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> Enter Marks
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Total Enrolled</span>
            <h3 class="fw-bold text-dark mt-2 mb-0"><?php echo $totalStudents; ?></h3>
            <small class="text-muted">Students across all grades</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Active Classes</span>
            <h3 class="fw-bold text-primary mt-2 mb-0"><?php echo $totalClasses; ?></h3>
            <small class="text-muted">Curriculum batches</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Evaluations Logged</span>
            <h3 class="fw-bold text-success mt-2 mb-0"><?php echo $totalMarksRecorded; ?></h3>
            <small class="text-muted">Grade entries</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0 h-100">
            <span class="text-muted small fw-semibold text-uppercase">Today's Attendance</span>
            <h3 class="fw-bold text-info mt-2 mb-0"><?php echo $todayAttendance; ?></h3>
            <small class="text-muted">Students logged today</small>
        </div>
    </div>
</div>

<!-- Recent Marks Grid -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800"><i class="bi bi-award me-2 text-primary"></i> Recently Logged Evaluations</h6>
        <a href="<?php echo BASE_URL; ?>admin/marks.php" class="btn btn-sm btn-link text-decoration-none small">Manage All &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th class="text-end">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentMarks)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No evaluations logged yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentMarks as $m): 
                        $gr = calculateGrade($m['marks_obtained'], $m['total_marks']);
                    ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($m['student_name']); ?></strong>
                                <div class="small text-muted"><?php echo htmlspecialchars($m['roll_no']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($m['class_name'] . ' (' . $m['section'] . ')'); ?></td>
                            <td><strong><?php echo htmlspecialchars($m['subject']); ?></strong></td>
                            <td><span class="badge bg-light text-muted border"><?php echo htmlspecialchars($m['exam_type']); ?></span></td>
                            <td><?php echo $m['marks_obtained']; ?> / <?php echo $m['total_marks']; ?></td>
                            <td class="text-end">
                                <span class="badge bg-<?php echo $gr['badge']; ?>-subtle text-<?php echo $gr['badge']; ?> px-2 py-1">
                                    Grade <?php echo $gr['grade']; ?>
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
