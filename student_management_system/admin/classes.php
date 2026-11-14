<?php
ob_start();
/**
 * Class / Course Management
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$errors = [];

// Handle Form Submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $className = trim($_POST['class_name'] ?? '');
            $section = trim($_POST['section'] ?? 'A');
            $roomNo = trim($_POST['room_no'] ?? '');

            if (empty($className)) {
                $errors[] = 'Class / Course name is required.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO classes (class_name, section, room_no) VALUES (?, ?, ?)");
                $stmt->execute([$className, $section, $roomNo]);
                setFlashMessage('success', "Class '{$className} ({$section})' created successfully!");
                header('Location: ' . BASE_URL . 'admin/classes.php');
                exit;
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['class_id'] ?? 0);
            $className = trim($_POST['class_name'] ?? '');
            $section = trim($_POST['section'] ?? 'A');
            $roomNo = trim($_POST['room_no'] ?? '');

            if (empty($className) || $id <= 0) {
                $errors[] = 'Valid class ID and Class Name are required.';
            } else {
                $stmt = $pdo->prepare("UPDATE classes SET class_name = ?, section = ?, room_no = ? WHERE id = ?");
                $stmt->execute([$className, $section, $roomNo, $id]);
                setFlashMessage('success', 'Class updated successfully!');
                header('Location: ' . BASE_URL . 'admin/classes.php');
                exit;
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['class_id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Class deleted successfully.');
                header('Location: ' . BASE_URL . 'admin/classes.php');
                exit;
            }
        }
    }
}

// Fetch all classes with student count
$sql = "
    SELECT c.*, COUNT(s.id) as student_count 
    FROM classes c 
    LEFT JOIN students s ON c.id = s.class_id 
    GROUP BY c.id 
    ORDER BY c.class_name ASC
";
$classes = $pdo->query($sql)->fetchAll();

$pageTitle = 'Manage Classes';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Class &amp; Course Management</h3>
        <p class="text-muted small mb-0">Create grade levels, curriculum tracks, and assign rooms.</p>
    </div>
    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
        <i class="bi bi-plus-circle"></i> Create New Class
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

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0 text-slate-800">Existing Classes (<?php echo count($classes); ?>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Class / Course Name</th>
                            <th>Section</th>
                            <th>Room / Facility</th>
                            <th>Enrolled Students</th>
                            <th>Date Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No classes added yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($classes as $idx => $c): ?>
                                <tr>
                                    <td><?php echo $idx + 1; ?></td>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($c['class_name']); ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['section']); ?></span></td>
                                    <td><?php echo htmlspecialchars($c['room_no'] ?? 'TBD'); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>admin/students.php?class_id=<?php echo $c['id']; ?>" class="badge bg-primary-subtle text-primary text-decoration-none">
                                            <i class="bi bi-person me-1"></i> <?php echo $c['student_count']; ?> students
                                        </a>
                                    </td>
                                    <td class="small text-muted"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editClassModal<?php echo $c['id']; ?>" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="classes.php" class="d-inline" 
                                                  onsubmit="return confirm('Deleting this class will also delete all students and records in it. Continue?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="class_id" value="<?php echo $c['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Edit Class Modal -->
                                        <div class="modal fade" id="editClassModal<?php echo $c['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog text-start">
                                                <div class="modal-content">
                                                    <form method="POST" action="classes.php">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="class_id" value="<?php echo $c['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">Edit Class</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Class / Course Name</label>
                                                                <input type="text" name="class_name" class="form-control" value="<?php echo htmlspecialchars($c['class_name']); ?>" required>
                                                            </div>
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <label class="form-label small fw-semibold">Section</label>
                                                                    <input type="text" name="section" class="form-control" value="<?php echo htmlspecialchars($c['section']); ?>" required>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label small fw-semibold">Room / Lab</label>
                                                                    <input type="text" name="room_no" class="form-control" value="<?php echo htmlspecialchars($c['room_no'] ?? ''); ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="classes.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Class / Course Name <span class="text-danger">*</span></label>
                        <input type="text" name="class_name" class="form-control" placeholder="e.g. Grade 11 - Computer Science" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Section / Batch</label>
                            <input type="text" name="section" class="form-control" placeholder="A" value="A" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Room No / Lab</label>
                            <input type="text" name="room_no" class="form-control" placeholder="e.g. Lab-102">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
