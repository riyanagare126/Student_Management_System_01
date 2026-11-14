<?php
ob_start();
/**
 * Daily Attendance Management & Reports
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'teacher']);

// Fetch Classes
$classesStmt = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC");
$classList = $classesStmt->fetchAll();

$selectedClass = (int)($_GET['class_id'] ?? ($classList[0]['id'] ?? 0));
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    $token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($token)) {
        $statuses = $_POST['status'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        $postDate = $_POST['date'] ?? date('Y-m-d');

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, date, status, remarks) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)
            ");

            foreach ($statuses as $studentId => $status) {
                $rem = trim($remarks[$studentId] ?? '');
                $stmt->execute([(int)$studentId, $postDate, $status, $rem]);
            }

            $pdo->commit();
            setFlashMessage('success', 'Attendance records successfully saved for ' . date('M d, Y', strtotime($postDate)) . '!');
            header("Location: attendance.php?class_id={$selectedClass}&date={$postDate}");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage('danger', 'Failed to save attendance: ' . $e->getMessage());
        }
    } else {
        setFlashMessage('danger', 'Invalid security token.');
    }
}

// Fetch Students in Selected Class
$students = [];
if ($selectedClass > 0) {
    $stStmt = $pdo->prepare("
        SELECT s.id, s.name, s.roll_no, s.photo,
               a.status as attendance_status, a.remarks as attendance_remarks
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
        WHERE s.class_id = ?
        ORDER BY s.roll_no ASC
    ");
    $stStmt->execute([$selectedDate, $selectedClass]);
    $students = $stStmt->fetchAll();
}

// Quick metrics for this class on this date
$counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0, 'Unmarked' => 0];
foreach ($students as $st) {
    if (!empty($st['attendance_status'])) {
        $counts[$st['attendance_status']]++;
    } else {
        $counts['Unmarked']++;
    }
}

$pageTitle = 'Attendance Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Attendance Management</h3>
        <p class="text-muted small mb-0">Record daily class attendance and monitor student presence trends.</p>
    </div>
    <?php if (!empty($students)): ?>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-success btn-sm" onclick="markAll('Present')">
                <i class="bi bi-check-all me-1"></i> Mark All Present
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Class & Date Selection Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="attendance.php" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted">Select Class / Batch</label>
                <select name="class_id" class="form-select form-select-sm" required>
                    <?php foreach ($classList as $cl): ?>
                        <option value="<?php echo $cl['id']; ?>" <?php echo ($selectedClass == $cl['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cl['class_name'] . ' (' . $cl['section'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Attendance Date</label>
                <input type="date" name="date" class="form-control form-select-sm" value="<?php echo htmlspecialchars($selectedDate); ?>" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-arrow-repeat me-1"></i> Load Roll Call
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Today's Class Summary Metrics -->
<?php if (!empty($students)): ?>
    <div class="row g-2 mb-4 text-center">
        <div class="col">
            <div class="p-2 bg-white rounded-3 border shadow-sm">
                <span class="d-block small text-muted">Total</span>
                <strong class="fs-5 text-dark"><?php echo count($students); ?></strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 bg-white rounded-3 border shadow-sm">
                <span class="d-block small text-muted">Present</span>
                <strong class="fs-5 text-success"><?php echo $counts['Present']; ?></strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 bg-white rounded-3 border shadow-sm">
                <span class="d-block small text-muted">Absent</span>
                <strong class="fs-5 text-danger"><?php echo $counts['Absent']; ?></strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 bg-white rounded-3 border shadow-sm">
                <span class="d-block small text-muted">Late</span>
                <strong class="fs-5 text-warning"><?php echo $counts['Late']; ?></strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 bg-white rounded-3 border shadow-sm">
                <span class="d-block small text-muted">Excused</span>
                <strong class="fs-5 text-info"><?php echo $counts['Excused']; ?></strong>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Attendance Sheet Form -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800">
            Roll Call Sheet: <span class="text-primary"><?php echo date('l, F j, Y', strtotime($selectedDate)); ?></span>
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($students)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                No students enrolled in the selected class yet.
            </div>
        <?php else: ?>
            <form method="POST" action="attendance.php?class_id=<?php echo $selectedClass; ?>&date=<?php echo $selectedDate; ?>">
                <input type="hidden" name="action" value="save_attendance">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Roll</th>
                                <th>Student</th>
                                <th class="text-center" style="width: 380px;">Status</th>
                                <th>Remarks / Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): 
                                $currentStatus = $s['attendance_status'] ?? 'Present';
                            ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['roll_no']); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($s['photo'] ?? 'default-avatar.svg'); ?>" 
                                                 alt="Avatar" class="student-avatar" style="width: 32px; height: 32px;">
                                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($s['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check status-radio status-present" 
                                                   name="status[<?php echo $s['id']; ?>]" id="pres_<?php echo $s['id']; ?>" 
                                                   value="Present" <?php echo ($currentStatus === 'Present') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-success btn-sm" for="pres_<?php echo $s['id']; ?>">Present</label>

                                            <input type="radio" class="btn-check status-radio status-absent" 
                                                   name="status[<?php echo $s['id']; ?>]" id="abs_<?php echo $s['id']; ?>" 
                                                   value="Absent" <?php echo ($currentStatus === 'Absent') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-danger btn-sm" for="abs_<?php echo $s['id']; ?>">Absent</label>

                                            <input type="radio" class="btn-check status-radio status-late" 
                                                   name="status[<?php echo $s['id']; ?>]" id="late_<?php echo $s['id']; ?>" 
                                                   value="Late" <?php echo ($currentStatus === 'Late') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-warning btn-sm" for="late_<?php echo $s['id']; ?>">Late</label>

                                            <input type="radio" class="btn-check status-radio status-excused" 
                                                   name="status[<?php echo $s['id']; ?>]" id="exc_<?php echo $s['id']; ?>" 
                                                   value="Excused" <?php echo ($currentStatus === 'Excused') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-info btn-sm" for="exc_<?php echo $s['id']; ?>">Excused</label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[<?php echo $s['id']; ?>]" 
                                               class="form-control form-control-sm" 
                                               placeholder="Optional note" 
                                               value="<?php echo htmlspecialchars($s['attendance_remarks'] ?? ''); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Make sure to submit to record changes in database.</span>
                    <button type="submit" class="btn btn-primary shadow-sm px-4">
                        <i class="bi bi-save me-1"></i> Save Attendance
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function markAll(status) {
    const selector = `.status-${status.toLowerCase()}`;
    document.querySelectorAll(selector).forEach(radio => {
        radio.checked = true;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
