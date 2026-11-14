<?php
ob_start();
/**
 * Faculty / Teacher Management
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$errors = [];

// Handle Actions (Add, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token invalid.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $createUser = isset($_POST['create_user']);
            $password = trim($_POST['password'] ?? 'teacher123');

            if (empty($name) || empty($email) || empty($subject)) {
                $errors[] = 'Teacher name, email, and subject are required.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $userId = null;
                    if ($createUser) {
                        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]));
                        if (empty($username)) $username = 'teacher_' . time();

                        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                        $check->execute([$username]);
                        if ($check->fetchColumn() > 0) {
                            $username .= '_' . rand(10, 99);
                        }

                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $uStmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'teacher')");
                        $uStmt->execute([$username, $hash]);
                        $userId = (int)$pdo->lastInsertId();
                    }

                    $tStmt = $pdo->prepare("INSERT INTO teachers (user_id, name, email, phone, subject) VALUES (?, ?, ?, ?, ?)");
                    $tStmt->execute([$userId, $name, $email, $phone, $subject]);

                    $pdo->commit();
                    setFlashMessage('success', "Faculty member '{$name}' registered successfully!");
                    header('Location: ' . BASE_URL . 'admin/teachers.php');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $teacherId = (int)($_POST['teacher_id'] ?? 0);
            if ($teacherId > 0) {
                // Check if user linked
                $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
                $stmt->execute([$teacherId]);
                $t = $stmt->fetch();

                $del = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
                $del->execute([$teacherId]);

                if ($t && !empty($t['user_id'])) {
                    $delU = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $delU->execute([$t['user_id']]);
                }

                setFlashMessage('success', 'Teacher record removed.');
                header('Location: ' . BASE_URL . 'admin/teachers.php');
                exit;
            }
        }
    }
}

// Fetch all teachers
$teachers = $pdo->query("
    SELECT t.*, u.username 
    FROM teachers t 
    LEFT JOIN users u ON t.user_id = u.id 
    ORDER BY t.name ASC
")->fetchAll();

$pageTitle = 'Faculty Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Faculty &amp; Instructors</h3>
        <p class="text-muted small mb-0">Manage teaching staff and grant teacher portal credentials.</p>
    </div>
    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
        <i class="bi bi-person-plus-fill"></i> Add Faculty Member
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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-slate-800">Faculty Members (<?php echo count($teachers); ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Instructor Name</th>
                    <th>Department / Subject</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Portal Login</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No teachers registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($teachers as $t): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="stat-icon bg-stat-warning rounded-circle" style="width: 36px; height: 36px; font-size: 1rem;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($t['name']); ?></div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($t['subject']); ?></span></td>
                            <td><?php echo htmlspecialchars($t['email']); ?></td>
                            <td><?php echo htmlspecialchars($t['phone'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($t['username'])): ?>
                                    <span class="badge bg-success-subtle text-success">@<?php echo htmlspecialchars($t['username']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">No Account</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="teachers.php" class="d-inline" onsubmit="return confirm('Remove this faculty member?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="teacher_id" value="<?php echo $t['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="teachers.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register Faculty Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Instructor Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Robert Vance" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject / Department <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Mathematics &amp; Physics" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="teacher@school.edu" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
                        </div>
                    </div>
                    <div class="p-3 bg-light rounded-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="teacherAccountCheck" name="create_user" value="1" checked 
                                   onchange="document.getElementById('teacherPassBox').style.display = this.checked ? 'block' : 'none';">
                            <label class="form-check-label fw-semibold small" for="teacherAccountCheck">
                                Enable Teacher Portal login
                            </label>
                        </div>
                        <div id="teacherPassBox">
                            <label class="form-label small text-muted">Initial Password</label>
                            <input type="text" name="password" class="form-control form-control-sm bg-white" value="teacher123">
                            <div class="small text-muted mt-1">Username will be generated automatically from email.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
