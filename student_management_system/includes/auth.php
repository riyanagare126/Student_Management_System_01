<?php
/**
 * Authentication & Helper Utilities
 * Student Management System
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Check if a user is currently logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role'] ?? '',
        'name'     => $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'User'),
        'photo'    => $_SESSION['user_photo'] ?? 'default-avatar.svg',
        'ref_id'   => $_SESSION['ref_id'] ?? null, // student_id or teacher_id
    ];
}

/**
 * Enforce login on protected pages
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlashMessage('danger', 'Please log in to access this page.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Enforce specific role(s)
 * @param string|array $allowedRoles Single role string or array of role strings
 */
function requireRole($allowedRoles): void {
    requireLogin();
    
    $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $currentRole = $_SESSION['role'] ?? '';

    if (!in_array($currentRole, $allowed, true)) {
        setFlashMessage('danger', 'Access denied: You do not have permission to view that page.');
        // Redirect to their respective home dashboard
        if ($currentRole === 'admin') {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        } elseif ($currentRole === 'teacher') {
            header('Location: ' . BASE_URL . 'teacher/dashboard.php');
        } elseif ($currentRole === 'student') {
            header('Location: ' . BASE_URL . 'student/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'login.php');
        }
        exit;
    }
}

/**
 * Set a session flash message
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Retrieve and clear the flash message
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message as a Bootstrap alert
 */
function renderFlashMessage(): void {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        echo "
        <div class='alert alert-{$alertClass} alert-dismissible fade show shadow-sm' role='alert'>
            <div class='d-flex align-items-center'>
                <i class='bi " . ($alertClass === 'success' ? 'bi-check-circle-fill' : ($alertClass === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill')) . " me-2 fs-5'></i>
                <div>{$message}</div>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }
}

/**
 * Sanitize string input
 */
function sanitize(mixed $data): string {
    if (is_null($data)) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Calculate Grade and GPA from percentage
 */
function calculateGrade(float $marksObtained, float $totalMarks = 100): array {
    if ($totalMarks <= 0) {
        return ['percentage' => 0, 'grade' => 'N/A', 'badge' => 'secondary', 'gpa' => 0.0];
    }
    $percentage = ($marksObtained / $totalMarks) * 100;
    
    if ($percentage >= 90) {
        return ['percentage' => round($percentage, 1), 'grade' => 'A+', 'badge' => 'success', 'gpa' => 4.0];
    } elseif ($percentage >= 80) {
        return ['percentage' => round($percentage, 1), 'grade' => 'A', 'badge' => 'success', 'gpa' => 3.7];
    } elseif ($percentage >= 70) {
        return ['percentage' => round($percentage, 1), 'grade' => 'B', 'badge' => 'primary', 'gpa' => 3.0];
    } elseif ($percentage >= 60) {
        return ['percentage' => round($percentage, 1), 'grade' => 'C', 'badge' => 'info', 'gpa' => 2.5];
    } elseif ($percentage >= 50) {
        return ['percentage' => round($percentage, 1), 'grade' => 'D', 'badge' => 'warning', 'gpa' => 2.0];
    } else {
        return ['percentage' => round($percentage, 1), 'grade' => 'F', 'badge' => 'danger', 'gpa' => 0.0];
    }
}
