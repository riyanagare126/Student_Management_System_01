<?php
ob_start();
/**
 * Add New Student
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Fetch Classes for Select Dropdown
$classesStmt = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC");
$classList = $classesStmt->fetchAll();

$errors = [];
$formData = [
    'name' => '',
    'roll_no' => '',
    'class_id' => '',
    'dob' => '',
    'gender' => 'Male',
    'email' => '',
    'phone' => '',
    'address' => '',
    'create_user' => '1',
    'password' => 'student123'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token validation failed. Please try again.';
    } else {
        $formData['name'] = trim($_POST['name'] ?? '');
        $formData['roll_no'] = trim($_POST['roll_no'] ?? '');
        $formData['class_id'] = (int)($_POST['class_id'] ?? 0);
        $formData['dob'] = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $formData['gender'] = $_POST['gender'] ?? 'Male';
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $formData['address'] = trim($_POST['address'] ?? '');
        $formData['create_user'] = isset($_POST['create_user']) ? '1' : '0';
        $formData['password'] = trim($_POST['password'] ?? 'student123');

        // Validation
        if (empty($formData['name'])) {
            $errors[] = 'Full Name is required.';
        }
        if (empty($formData['roll_no'])) {
            $errors[] = 'Roll Number is required.';
        } else {
            // Check if roll number already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE roll_no = ?");
            $checkStmt->execute([$formData['roll_no']]);
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = "Roll number '{$formData['roll_no']}' is already assigned to another student.";
            }
        }
        if ($formData['class_id'] <= 0) {
            $errors[] = 'Please select a valid class or course.';
        }

        // File upload handling
        $photoFilename = 'default-avatar.svg';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions)) {
                $errors[] = 'Invalid photo format. Allowed formats: JPG, JPEG, PNG, WEBP.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Photo size cannot exceed 2MB.';
            } else {
                // Ensure upload directory exists
                $uploadDir = __DIR__ . '/../assets/uploads/students/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newFileName = 'stu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $photoFilename = $newFileName;
                } else {
                    $errors[] = 'Failed to upload photo. Please check folder permissions.';
                }
            }
        }

        // Save if no errors
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $userId = null;
                // Create user login account if checked
                if ($formData['create_user'] === '1') {
                    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $formData['roll_no']));
                    if (empty($username)) {
                        $username = 'student_' . time();
                    }

                    // Check if username exists, append if needed
                    $checkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $checkUser->execute([$username]);
                    if ($checkUser->fetchColumn() > 0) {
                        $username .= '_' . rand(100, 999);
                    }

                    $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
                    $userStmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                    $userStmt->execute([$username, $hashedPassword]);
                    $userId = (int)$pdo->lastInsertId();
                }

                // Insert student
                $insertSql = "
                    INSERT INTO students (user_id, roll_no, name, class_id, dob, gender, email, phone, address, photo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    $userId,
                    $formData['roll_no'],
                    $formData['name'],
                    $formData['class_id'],
                    $formData['dob'],
                    $formData['gender'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['address'],
                    $photoFilename
                ]);
                $newStudentId = (int)$pdo->lastInsertId();

                $pdo->commit();

                setFlashMessage('success', "Student '{$formData['name']}' has been successfully enrolled!");
                header("Location: " . BASE_URL . "admin/student_view.php?id=" . $newStudentId);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Add New Student';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">Enroll New Student</h3>
        <p class="text-muted small mb-0">Fill out the student details and optionally generate their portal login.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>admin/students.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Students
    </a>
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
        <form method="POST" action="student_add.php" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

            <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-person-lines-fill text-primary me-2"></i>Personal Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                           placeholder="e.g. Johnathan Doe" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                    <div class="invalid-feedback">Student name is required.</div>
                </div>

                <div class="col-md-6">
                    <label for="roll_no" class="form-label small fw-semibold">Roll Number / Student ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="roll_no" name="roll_no" 
                           placeholder="e.g. STD-2024-010" value="<?php echo htmlspecialchars($formData['roll_no']); ?>" required>
                    <div class="invalid-feedback">Unique roll number is required.</div>
                </div>

                <div class="col-md-6">
                    <label for="class_id" class="form-label small fw-semibold">Class / Course Batch <span class="text-danger">*</span></label>
                    <select class="form-select" id="class_id" name="class_id" required>
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($classList as $cl): ?>
                            <option value="<?php echo $cl['id']; ?>" <?php echo ($formData['class_id'] == $cl['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cl['class_name'] . ' (' . $cl['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please assign a class.</div>
                </div>

                <div class="col-md-3">
                    <label for="dob" class="form-label small fw-semibold">Date of Birth</label>
                    <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($formData['dob'] ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label for="gender" class="form-label small fw-semibold">Gender</label>
                    <select class="form-select" id="gender" name="gender">
                        <option value="Male" <?php echo ($formData['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($formData['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($formData['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-envelope-at text-primary me-2"></i>Contact Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="email" class="form-label small fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="student@example.com" value="<?php echo htmlspecialchars($formData['email']); ?>">
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label small fw-semibold">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           placeholder="+1 (555) 000-0000" value="<?php echo htmlspecialchars($formData['phone']); ?>">
                </div>

                <div class="col-12">
                    <label for="address" class="form-label small fw-semibold">Residential Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2" 
                              placeholder="Street, City, State, Zip Code"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                </div>
            </div>

            <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-image text-primary me-2"></i>Student Photo</h5>
            <div class="row g-3 align-items-center mb-4">
                <div class="col-auto">
                    <img id="photoPreview" src="<?php echo BASE_URL; ?>assets/uploads/students/default-avatar.svg" 
                         alt="Preview" class="avatar-lg">
                </div>
                <div class="col">
                    <label for="photoInput" class="form-label small fw-semibold">Upload Photo (JPG, PNG, WEBP - Max 2MB)</label>
                    <input class="form-control" type="file" id="photoInput" name="photo" accept="image/*">
                    <div class="form-text">A clear frontal portrait works best for identity cards.</div>
                </div>
            </div>

            <h5 class="fw-bold text-slate-800 mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Student Portal Access</h5>
            <div class="p-3 bg-light rounded-3 mb-4">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="createUserCheck" name="create_user" value="1" 
                           <?php echo ($formData['create_user'] === '1') ? 'checked' : ''; ?> 
                           onchange="document.getElementById('credentialsBox').style.display = this.checked ? 'block' : 'none';">
                    <label class="form-check-label fw-semibold" for="createUserCheck">
                        Automatically create a portal login account for this student
                    </label>
                </div>

                <div id="credentialsBox" style="display: <?php echo ($formData['create_user'] === '1') ? 'block' : 'none'; ?>;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Generated Username</label>
                            <input type="text" class="form-control bg-white" value="[Will match Roll Number]" readonly>
                            <small class="text-muted">Students log in using their Roll Number.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label small text-muted">Initial Password</label>
                            <input type="text" class="form-control bg-white" id="password" name="password" 
                                   value="<?php echo htmlspecialchars($formData['password']); ?>">
                            <small class="text-muted">Default: <code>student123</code> (can be changed later).</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>admin/students.php" class="btn btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save Student
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
ob_end_flush();
 ?>
