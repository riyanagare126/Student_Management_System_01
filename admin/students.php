<?php
ob_start();
/**
 * Student Listing with Search, Filter & Pagination
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Handle Student Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['student_id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';

    if (verifyCsrfToken($token) && $deleteId > 0) {
        // Fetch student record for photo and user_id
        $stmt = $pdo->prepare("SELECT user_id, photo FROM students WHERE id = ?");
        $stmt->execute([$deleteId]);
        $student = $stmt->fetch();

        if ($student) {
            // Delete photo if not default
            if (!empty($student['photo']) && $student['photo'] !== 'default-avatar.svg') {
                $photoPath = __DIR__ . '/../assets/uploads/students/' . $student['photo'];
                if (file_exists($photoPath)) {
                    @unlink($photoPath);
                }
            }

            // Delete student (CASCADE removes attendance, marks, fees)
            $delStmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $delStmt->execute([$deleteId]);

            // If linked user account exists, delete it too
            if (!empty($student['user_id'])) {
                $delUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delUser->execute([$student['user_id']]);
            }

            setFlashMessage('success', 'Student record deleted successfully.');
        } else {
            setFlashMessage('danger', 'Student not found.');
        }
    } else {
        setFlashMessage('danger', 'Invalid security token or invalid request.');
    }
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

// Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$classFilter = (int)($_GET['class_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

// Fetch Classes for Filter Dropdown
$classesStmt = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC");
$classList = $classesStmt->fetchAll();

// Build Query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(s.name LIKE ? OR s.roll_no LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
    $searchWildcard = "%{$search}%";
    $params = array_merge($params, [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
}

if ($classFilter > 0) {
    $where[] = "s.class_id = ?";
    $params[] = $classFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count Total matching records
$countSql = "SELECT COUNT(*) FROM students s {$whereClause}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch Paginated Records
$sql = "
    SELECT s.*, c.class_name, c.section, u.username
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    {$whereClause}
    ORDER BY s.id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = 'Student Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Student Directory</h3>
        <p class="text-muted small mb-0">Manage enrolled students, search records, and view academic profiles.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/student_add.php" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-person-plus-fill"></i> Add New Student
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="students.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Search by name, roll no, email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">All Classes / Batches</option>
                    <?php foreach ($classList as $cl): ?>
                        <option value="<?php echo $cl['id']; ?>" <?php echo ($classFilter === (int)$cl['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cl['class_name'] . ' (' . $cl['section'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <?php if (!empty($search) || $classFilter > 0): ?>
                    <a href="students.php" class="btn btn-light btn-sm border" title="Reset Filter"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Students Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800">
            Total Students Found: <span class="badge bg-primary rounded-pill"><?php echo $totalRecords; ?></span>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student Info</th>
                    <th>Roll No</th>
                    <th>Class / Section</th>
                    <th>Gender</th>
                    <th>Contact</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                            No student records found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($s['photo'] ?? 'default-avatar.svg'); ?>" 
                                         alt="Avatar" class="student-avatar">
                                    <div>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($s['email'] ?? 'No email provided'); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-medium"><?php echo htmlspecialchars($s['roll_no']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info-emphasis px-2 py-1">
                                    <?php echo htmlspecialchars(($s['class_name'] ?? 'Unassigned') . ' - ' . ($s['section'] ?? '')); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($s['gender'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo BASE_URL; ?>admin/student_view.php?id=<?php echo $s['id']; ?>" 
                                       class="btn btn-outline-primary" title="View Full Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>admin/student_edit.php?id=<?php echo $s['id']; ?>" 
                                       class="btn btn-outline-secondary" title="Edit Student">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="students.php" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete <?php echo addslashes($s['name']); ?>? All attendance, marks, and fees will also be removed.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Delete Student">
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

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-transparent py-3 d-flex justify-content-between align-items-center">
            <span class="small text-muted">
                Showing page <?php echo $page; ?> of <?php echo $totalPages; ?>
            </span>
            <nav aria-label="Student directory pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&class_id=<?php echo $classFilter; ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&class_id=<?php echo $classFilter; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&class_id=<?php echo $classFilter; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
