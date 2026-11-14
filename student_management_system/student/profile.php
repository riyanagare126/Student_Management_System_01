<?php
/**
 * Student Personal Profile
 * Student Management System
 */
$pageTitle = 'My Profile';
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

$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section, c.room_no, u.username
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-slate-800 mb-1">My Student Profile</h3>
        <p class="text-muted small mb-0">Review your registered academic credentials and personal details.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($student['photo'] ?? 'default-avatar.svg'); ?>" 
                 alt="Avatar" class="avatar-lg mx-auto mb-3">
            <h5 class="fw-bold text-slate-800 mb-1"><?php echo htmlspecialchars($student['name']); ?></h5>
            <div class="text-muted small mb-3"><?php echo htmlspecialchars($student['email'] ?? 'No email registered'); ?></div>
            
            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                    Roll: <?php echo htmlspecialchars($student['roll_no']); ?>
                </span>
                <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill">
                    Class: <?php echo htmlspecialchars($student['class_name'] . ' (' . $student['section'] . ')'); ?>
                </span>
            </div>
            
            <p class="small text-muted mb-0">
                To update your contact information or address, please reach out to the school administrative office.
            </p>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-slate-800"><i class="bi bi-person-lines-fill me-2 text-primary"></i> Registration Details</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6 border-bottom pb-3">
                        <span class="text-muted small d-block">Full Legal Name</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($student['name']); ?></strong>
                    </div>
                    <div class="col-md-6 border-bottom pb-3">
                        <span class="text-muted small d-block">Student Roll ID</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($student['roll_no']); ?></strong>
                    </div>
                    <div class="col-md-6 border-bottom pb-3">
                        <span class="text-muted small d-block">Class / Course Stream</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($student['class_name'] . ' (Section ' . $student['section'] . ')'); ?></strong>
                    </div>
                    <div class="col-md-6 border-bottom pb-3">
                        <span class="text-muted small d-block">Assigned Classroom / Lab</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($student['room_no'] ?? 'Standard Hall'); ?></strong>
                    </div>
                    <div class="col-md-6 border-bottom pb-3">
                        <span class="text-muted small d-block">Gender</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></strong>
                    </div>
                    <div class="col-md-6 border-bottom pb-3">
                        <span class="text-muted small d-block">Date of Birth</span>
                        <strong class="text-dark"><?php echo $student['dob'] ? date('F j, Y', strtotime($student['dob'])) : 'N/A'; ?></strong>
                    </div>
                    <div class="col-md-6 border-bottom pb-3">
                        <span class="text-muted small d-block">Contact Phone</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></strong>
                    </div>
                    <div class="col-md-6 border-bottom pb-3">
                        <span class="text-muted small d-block">Email Address</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></strong>
                    </div>
                    <div class="col-12 border-bottom pb-3">
                        <span class="text-muted small d-block">Residential Address</span>
                        <strong class="text-dark"><?php echo nl2br(htmlspecialchars($student['address'] ?? 'No address registered.')); ?></strong>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Enrollment Timestamp</span>
                        <strong class="text-dark"><?php echo date('M d, Y - h:i A', strtotime($student['created_at'])); ?></strong>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted small d-block">Portal Username</span>
                        <strong class="text-dark">@<?php echo htmlspecialchars($student['username'] ?? $currentUser['username']); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
