<?php
/**
 * Top Navigation Bar
 * Student Management System
 */
$currentUser = getCurrentUser();
$role = $currentUser['role'] ?? 'guest';
?>
<div class="app-main">
    <header class="app-navbar no-print">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light border d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="d-none d-sm-block">
                <span class="text-muted small">Academic Year: <strong>2024 - 2025</strong></span>
                <span class="mx-2 text-muted">•</span>
                <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?php echo date('D, M j, Y'); ?></span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm d-flex align-items-center gap-2 p-1 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo BASE_URL; ?>assets/uploads/students/<?php echo htmlspecialchars($currentUser['photo'] ?? 'default-avatar.svg'); ?>" 
                         alt="Avatar" class="student-avatar" style="width: 38px; height: 38px;">
                    <div class="text-start d-none d-md-block me-1">
                        <div class="fw-semibold text-dark lh-1 small"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></div>
                        <small class="text-muted text-capitalize" style="font-size: 0.75rem;"><?php echo htmlspecialchars($role); ?></small>
                    </div>
                    <i class="bi bi-chevron-down text-muted small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></div>
                        <div class="small text-muted">@<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?></div>
                    </li>
                    <?php if ($role === 'student'): ?>
                        <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>student/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </header>
    
    <main class="page-content">
        <?php renderFlashMessage(); ?>
