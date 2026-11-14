<?php
ob_start();
/**
 * Edit Student
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

// Fetch Student
$stmt = $pdo->prepare("
    SELECT s.*, u.username 
    FROM students s 
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

// Fetch Classes
$classesStmt = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC");
$classList = $classesStmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token validation failed. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $roll_no = trim($_POST['roll_no'] ?? '');
        $class_id = (int)($_POST['class_id'] ?? 0);
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender = $_POST['gender'] ?? 'Male';
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');

        // Validation
        if (empty($name)) {
            $errors[] = 'Full Name is required.';
        }
        if (empty($roll_no)) {
            $errors[] = 'Roll Number is required.';
        } else {
            // Check if roll number taken by another student
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE roll_no = ? AND id != ?");
            $checkStmt->execute([$roll_no, $id]);
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = "Roll number '{$roll_no}' is already in use by another student.";
            }
        }
        if ($class_id <= 0) {
            $errors[] = 'Please select a valid class.';
        }

        // Photo Upload
        $photoFilename = $student['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = 'Invalid photo format. Allowed formats: JPG, JPEG, PNG, WEBP.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Photo size cannot exceed 2MB.';
            } else {
                $uploadDir = __DIR__ . '/../assets/uploads/students/';
                $newFileName = 'stu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    // Delete previous photo if not default
                    if (!empty($student['photo']) && $student['photo'] !== 'default-avatar.svg') {
                        $oldPhotoPath = $uploadDir . $student['photo'];
                        if (file_exists($oldPhotoPath)) {
                            @unlink($oldPhotoPath);
                        }
                    }
                    $photoFilename = $newFileName;
                } else {
                    $errors[] = 'Failed to upload photo.';
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update student
                $updateSql = "
                    UPDATE students 
                    SET name = ?, roll_no = ?, class_id = ?, dob = ?, gender = ?, email = ?, phone = ?, address = ?, photo = ?
                    WHERE id = ?
                ";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    $name, $roll_no, $class_id, $dob, $gender, $email, $phone, $address, $photoFilename, $id
                ]);

                // Update password if specified
                if (!empty($newPassword) && !empty($student['user_id'])) {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $pwdStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $pwdStmt->execute([$hash, $student['user_id']]);
                }

                $pdo->commit();

                setFlashMessage('success', 'Student details updated successfully!');
                header("Location: " . BASE_URL . "admin/student_view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Student';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Edit Student: <?php echo htmlspecialchars($student['name']); ?></h3>
        <p class="text-muted small mb-0">Update profile details, academic placement, or student credentials.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>admin/student_view.php?id=<?php echo $id; ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-eye me-1"></i> View Profile
        </a>
        <a href="<?php echo BASE_URL; ?>admin/students.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Students
        </a>
    </div>
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
    <div class="card-body p-4">
        <form method="POST" action="student_edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

            <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-person-lines-fill text-primary me-2"></i>Personal Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    <div class="invalid-feedback">Full name is required.</div>
                </div>

                <div class="col-md-6">
                    <label for="roll_no" class="form-label small fw-semibold">Roll Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="roll_no" name="roll_no" 
                           value="<?php echo htmlspecialchars($student['roll_no']); ?>" required>
                    <div class="invalid-feedback">Roll number is required.</div>
                </div>

                <div class="col-md-6">
                    <label for="class_id" class="form-label small fw-semibold">Class / Course <span class="text-danger">*</span></label>
                    <select class="form-select" id="class_id" name="class_id" required>
                        <?php foreach ($classList as $cl): ?>
                            <option value="<?php echo $cl['id']; ?>" <?php echo ($student['class_id'] == $cl['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cl['class_name'] . ' (' . $cl['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="dob" class="form-label small fw-semibold">Date of Birth</label>
                    <input type="date" class="form-control" id="dob" name="dob" 
                           value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label for="gender" class="form-label small fw-semibold">Gender</label>
                    <select class="form-select" id="gender" name="gender">
                        <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-envelope-at text-primary me-2"></i>Contact Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="email" class="form-label small fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label small fw-semibold">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                </div>

                <div class="col-12">
                    <label for="address" class="form-label small fw-semibold">Residential Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-image text-primary me-2"></i>Student Photo</h5>
            <div class="row g-3 align-items-center mb-4">
                <div class="col-auto">
                    <img id="photoPreview" src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($student['photo'] ?? 'default-avatar.svg'); ?>" 
                         alt="Current Avatar" class="avatar-lg">
                </div>
                <div class="col">
                    <label for="photoInput" class="form-label small fw-semibold">Change Photo (Leave blank to keep existing)</label>
                    <input class="form-control" type="file" id="photoInput" name="photo" accept="image/*">
                    <div class="form-text">Uploading a new image will replace the current one.</div>
                </div>
            </div>

            <?php if (!empty($student['user_id'])): ?>
                <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Portal Login Account</h5>
                <div class="p-3 bg-light rounded-3 mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Portal Username</label>
                            <input type="text" class="form-control bg-white" value="<?php echo htmlspecialchars($student['username']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="new_password" class="form-label small text-muted">Reset Password (Leave blank to keep unchanged)</label>
                            <input type="password" class="form-control bg-white" id="new_password" name="new_password" placeholder="Enter new password">
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>admin/student_view.php?id=<?php echo $id; ?>" class="btn btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
