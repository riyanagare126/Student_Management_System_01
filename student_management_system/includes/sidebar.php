<?php
/**
 * Role-Aware Sidebar Navigation
 * Student Management System
 */
$currentUser = getCurrentUser();
$role = $currentUser['role'] ?? 'guest';
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<aside class="app-sidebar">
    <!-- Brand Logo -->
    <a href="<?php echo BASE_URL . ($role === 'admin' ? 'admin/dashboard.php' : ($role === 'teacher' ? 'teacher/dashboard.php' : 'student/dashboard.php')); ?>" class="sidebar-brand">
        <i class="bi bi-mortarboard-fill"></i>
        <span>EduManage</span>
    </a>

    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <?php if ($role === 'admin'): ?>
            <li class="menu-header">Main</li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="menu-link <?php echo ($currentScript === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="menu-header">Academic Management</li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/students.php" class="menu-link <?php echo in_array($currentScript, ['students.php', 'student_view.php', 'student_edit.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>All Students</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/student_add.php" class="menu-link <?php echo ($currentScript === 'student_add.php') ? 'active' : ''; ?>">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Add Student</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/classes.php" class="menu-link <?php echo ($currentScript === 'classes.php') ? 'active' : ''; ?>">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span>Classes / Courses</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/teachers.php" class="menu-link <?php echo ($currentScript === 'teachers.php') ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Teachers</span>
                </a>
            </li>

            <li class="menu-header">Records & Performance</li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/attendance.php" class="menu-link <?php echo ($currentScript === 'attendance.php') ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/marks.php" class="menu-link <?php echo in_array($currentScript, ['marks.php', 'report_card.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-award-fill"></i>
                    <span>Marks & Results</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/fees.php" class="menu-link <?php echo in_array($currentScript, ['fees.php', 'fee_receipt.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card-2-front-fill"></i>
                    <span>Fee Management</span>
                </a>
            </li>

        <?php 
        // Only show Database Explorer if logged in as authorized admin email or primary admin account
        $loggedInUsername = strtolower($currentUser['username'] ?? '');
        $allowedEmail = strtolower(defined('DB_VIEWER_AUTHORIZED_EMAIL') ? DB_VIEWER_AUTHORIZED_EMAIL : 'riyanagare126@gmail.com');
        $isAuthorizedViewer = ($role === 'admin') && (
            $loggedInUsername === $allowedEmail || 
            $loggedInUsername === 'admin' || 
            ($currentUser['id'] ?? 0) === 1
        );
        if ($isAuthorizedViewer): 
        ?>
            <li class="menu-header">Database & System</li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/db_viewer.php" class="menu-link <?php echo ($currentScript === 'db_viewer.php') ? 'active' : ''; ?>">
                    <i class="bi bi-database-lock"></i>
                    <span>Database Explorer</span>
                    <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.65rem; padding: 2px 6px;">Secure</span>
                </a>
            </li>
        <?php endif; ?>

        <?php elseif ($role === 'teacher'): ?>
            <li class="menu-header">Teacher Portal</li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="menu-link <?php echo ($currentScript === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>teacher/attendance.php" class="menu-link <?php echo ($currentScript === 'attendance.php') ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>Mark Attendance</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>teacher/marks.php" class="menu-link <?php echo ($currentScript === 'marks.php') ? 'active' : ''; ?>">
                    <i class="bi bi-award-fill"></i>
                    <span>Enter Marks</span>
                </a>
            </li>

        <?php elseif ($role === 'student'): ?>
            <li class="menu-header">Student Portal</li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="menu-link <?php echo ($currentScript === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>My Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>student/profile.php" class="menu-link <?php echo ($currentScript === 'profile.php') ? 'active' : ''; ?>">
                    <i class="bi bi-person-bounding-box"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>student/attendance.php" class="menu-link <?php echo ($currentScript === 'attendance.php') ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>My Attendance</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>student/marks.php" class="menu-link <?php echo ($currentScript === 'marks.php') ? 'active' : ''; ?>">
                    <i class="bi bi-award-fill"></i>
                    <span>Marks & Report Card</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>student/fees.php" class="menu-link <?php echo ($currentScript === 'fees.php') ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Fees & Receipts</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header">Session</li>
        <li class="menu-item">
            <a href="<?php echo BASE_URL; ?>logout.php" class="menu-link text-danger-emphasis">
                <i class="bi bi-box-arrow-right text-danger"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <!-- Bottom User Tag -->
    <div class="p-3 border-top border-secondary border-opacity-25 d-flex align-items-center gap-2">
        <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($currentUser['photo'] ?? 'default-avatar.svg'); ?>" 
             alt="User Avatar" class="student-avatar" style="width: 36px; height: 36px;">
        <div class="text-truncate">
            <div class="small fw-semibold text-white text-truncate"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></div>
            <div class="badge bg-primary bg-opacity-75 text-white" style="font-size: 0.68rem; text-transform: uppercase;">
                <?php echo htmlspecialchars($role); ?>
            </div>
        </div>
    </div>
</aside>
