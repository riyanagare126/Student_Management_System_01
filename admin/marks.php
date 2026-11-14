<?php
ob_start();
/**
 * Marks & Results Management
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'teacher']);

$errors = [];

// Handle Form Submissions (Add, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $studentId = (int)($_POST['student_id'] ?? 0);
            $subject = trim($_POST['subject'] ?? '');
            $examType = trim($_POST['exam_type'] ?? 'Final Exam');
            $marksObtained = (float)($_POST['marks_obtained'] ?? 0);
            $totalMarks = (float)($_POST['total_marks'] ?? 100);

            if ($studentId <= 0 || empty($subject)) {
                $errors[] = 'Please select a student and provide a subject name.';
            } elseif ($marksObtained < 0 || $totalMarks <= 0) {
                $errors[] = 'Marks obtained must be non-negative and total marks must be greater than zero.';
            } elseif ($marksObtained > $totalMarks) {
                $errors[] = 'Marks obtained cannot exceed total marks.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO marks (student_id, subject, marks_obtained, total_marks, exam_type)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$studentId, $subject, $marksObtained, $totalMarks, $examType]);
                setFlashMessage('success', "Marks recorded for {$subject}!");
                header('Location: ' . BASE_URL . 'admin/marks.php');
                exit;
            }
        } elseif ($action === 'delete') {
            $markId = (int)($_POST['mark_id'] ?? 0);
            if ($markId > 0) {
                $stmt = $pdo->prepare("DELETE FROM marks WHERE id = ?");
                $stmt->execute([$markId]);
                setFlashMessage('success', 'Mark entry deleted.');
                header('Location: ' . BASE_URL . 'admin/marks.php');
                exit;
            }
        }
    }
}

// Filter parameters
$filterStudent = (int)($_GET['student_id'] ?? 0);
$filterClass = (int)($_GET['class_id'] ?? 0);

// Fetch Students & Classes for dropdowns
$studentsList = $pdo->query("SELECT id, name, roll_no FROM students ORDER BY name ASC")->fetchAll();
$classesList = $pdo->query("SELECT id, class_name, section FROM classes ORDER BY class_name ASC")->fetchAll();

// Build Marks Query
$where = [];
$params = [];

if ($filterStudent > 0) {
    $where[] = "m.student_id = ?";
    $params[] = $filterStudent;
}
if ($filterClass > 0) {
    $where[] = "s.class_id = ?";
    $params[] = $filterClass;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$marksSql = "
    SELECT m.*, s.name as student_name, s.roll_no, s.photo, c.class_name, c.section
    FROM marks m
    JOIN students s ON m.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    {$whereSql}
    ORDER BY m.id DESC
";
$marksStmt = $pdo->prepare($marksSql);
$marksStmt->execute($params);
$marks = $marksStmt->fetchAll();

$pageTitle = 'Marks & Academic Results';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Marks &amp; Examination Records</h3>
        <p class="text-muted small mb-0">Record exam evaluations, track academic standings, and generate report cards.</p>
    </div>
    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMarkModal">
        <i class="bi bi-plus-circle"></i> Record New Marks
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

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="marks.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <select name="student_id" class="form-select form-select-sm">
                    <option value="">All Students</option>
                    <?php foreach ($studentsList as $st): ?>
                        <option value="<?php echo $st['id']; ?>" <?php echo ($filterStudent === (int)$st['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($st['name'] . ' (' . $st['roll_no'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">All Classes</option>
                    <?php foreach ($classesList as $cl): ?>
                        <option value="<?php echo $cl['id']; ?>" <?php echo ($filterClass === (int)$cl['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cl['class_name'] . ' (' . $cl['section'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <?php if ($filterStudent > 0 || $filterClass > 0): ?>
                    <a href="marks.php" class="btn btn-light btn-sm border"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Marks Records Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800">Evaluation Records (<?php echo count($marks); ?>)</h6>
        <?php if ($filterStudent > 0): ?>
            <a href="<?php echo BASE_URL; ?>admin/report_card.php?id=<?php echo $filterStudent; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                <i class="bi bi-printer me-1"></i> Print Student Report Card
            </a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Exam Type</th>
                    <th>Marks Obtained</th>
                    <th>Percentage / Grade</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marks)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No marks records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($marks as $m): 
                        $gradeInfo = calculateGrade($m['marks_obtained'], $m['total_marks']);
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($m['photo'] ?? 'default-avatar.svg'); ?>" 
                                         alt="Avatar" class="student-avatar" style="width: 32px; height: 32px;">
                                    <div>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($m['student_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($m['roll_no']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($m['class_name'] . ' (' . $m['section'] . ')'); ?></span></td>
                            <td><strong class="text-slate-800"><?php echo htmlspecialchars($m['subject']); ?></strong></td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($m['exam_type']); ?></span></td>
                            <td>
                                <span class="fw-bold text-dark"><?php echo $m['marks_obtained']; ?></span> / <?php echo $m['total_marks']; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $gradeInfo['badge']; ?>-subtle text-<?php echo $gradeInfo['badge']; ?> px-2 py-1">
                                    <?php echo $gradeInfo['percentage']; ?>% &bull; Grade <?php echo $gradeInfo['grade']; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo BASE_URL; ?>admin/report_card.php?id=<?php echo $m['student_id']; ?>" 
                                       class="btn btn-outline-primary" title="Print Report Card">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                    <form method="POST" action="marks.php" class="d-inline" onsubmit="return confirm('Delete this mark entry?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="mark_id" value="<?php echo $m['id']; ?>">
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

<!-- Record Marks Modal -->
<div class="modal fade" id="addMarkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="marks.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Record Student Marks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach ($studentsList as $st): ?>
                                <option value="<?php echo $st['id']; ?>" <?php echo ($filterStudent == $st['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($st['name'] . ' (' . $st['roll_no'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a student.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Mathematics, Physics, English" required>
                        <div class="invalid-feedback">Subject name is required.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Exam / Evaluation Type</label>
                        <select name="exam_type" class="form-select">
                            <option value="Midterm Examination">Midterm Examination</option>
                            <option value="Final Examination" selected>Final Examination</option>
                            <option value="Unit Test 1">Unit Test 1</option>
                            <option value="Unit Test 2">Unit Test 2</option>
                            <option value="Semester Assessment">Semester Assessment</option>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Marks Obtained <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" min="0" max="500" name="marks_obtained" class="form-control" placeholder="e.g. 88.5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Total Marks <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" max="500" name="total_marks" class="form-control" value="100" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Marks Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
