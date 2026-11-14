<?php
/**
 * Login Page
 * Student Management System
 */
require_once __DIR__ . '/includes/auth.php';

// If already logged in, route to appropriate dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            // Find user by email/username (allows riyanagare126@gmail.com or admin)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR (role = 'admin' AND ? = 'admin') LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                // If it was stored in plain text or needs modern rehash, re-hash it automatically
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT) || $password === $user['password']) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$newHash, $user['id']]);
                }

                // Prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = ucfirst($user['username']);
                $_SESSION['user_photo'] = 'default-avatar.svg';
                $_SESSION['ref_id'] = null;

                // Load extra role details
                if ($user['role'] === 'student') {
                    $stStmt = $pdo->prepare("SELECT id, name, photo FROM students WHERE user_id = ? LIMIT 1");
                    $stStmt->execute([$user['id']]);
                    $st = $stStmt->fetch();
                    if ($st) {
                        $_SESSION['ref_id'] = $st['id'];
                        $_SESSION['full_name'] = $st['name'];
                        if (!empty($st['photo'])) {
                            $_SESSION['user_photo'] = $st['photo'];
                        }
                    }
                } elseif ($user['role'] === 'teacher') {
                    $tStmt = $pdo->prepare("SELECT id, name FROM teachers WHERE user_id = ? LIMIT 1");
                    $tStmt->execute([$user['id']]);
                    $t = $tStmt->fetch();
                    if ($t) {
                        $_SESSION['ref_id'] = $t['id'];
                        $_SESSION['full_name'] = $t['name'];
                    }
                } elseif ($user['role'] === 'admin') {
                    $_SESSION['full_name'] = 'System Administrator';
                }

                setFlashMessage('success', "Welcome back, {$_SESSION['full_name']}!");

                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . 'admin/dashboard.php');
                } elseif ($user['role'] === 'teacher') {
                    header('Location: ' . BASE_URL . 'teacher/dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . 'student/dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            max-width: 460px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .login-header {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            padding: 2.25rem 2rem;
            color: #ffffff;
            text-align: center;
        }
        .login-body {
            padding: 2.25rem;
        }
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.2);
        }
        .btn-brand {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff;
            font-weight: 600;
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            transition: opacity 0.2s;
        }
        .btn-brand:hover {
            color: #ffffff;
            opacity: 0.92;
        }
        .demo-btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.65rem;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-20 rounded-circle mb-3" style="width: 60px; height: 60px;">
            <i class="bi bi-mortarboard-fill fs-2"></i>
        </div>
        <h3 class="fw-bold mb-1">EduManage Portal</h3>
        <p class="mb-0 text-white-50 small">Sign in to access your dashboard</p>
    </div>

    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php renderFlashMessage(); ?>

        <form method="POST" action="login.php" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

            <div class="mb-3">
                <label for="username" class="form-label small fw-semibold text-muted">Email / Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control border-start-0" id="username" name="username" 
                           placeholder="riyanagare126@gmail.com" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                    <div class="invalid-feedback">Email or username is required.</div>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label small fw-semibold text-muted mb-0">Password</label>
                    <span class="text-muted small" style="font-size: 0.78rem;">Default: &lt;role&gt;123</span>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control border-start-0" id="password" name="password" 
                           placeholder="Enter password" required>
                    <div class="invalid-feedback">Password is required.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-brand w-100 mb-3 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>

        <!-- Quick 1-Click Demo Buttons -->
        <div class="mt-4 pt-3 border-top">
            <p class="small text-muted text-center fw-semibold mb-2">⚡ Quick 1-Click Demo Logins:</p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <button type="button" class="btn btn-outline-primary demo-btn" onclick="fillDemo('riyanagare126@gmail.com', 'admin123')">
                    <i class="bi bi-shield-lock me-1"></i> Admin (Riya)
                </button>
                <button type="button" class="btn btn-outline-success demo-btn" onclick="fillDemo('teacher1', 'teacher123')">
                    <i class="bi bi-person-badge me-1"></i> Teacher
                </button>
                <button type="button" class="btn btn-outline-secondary demo-btn" onclick="fillDemo('student1', 'student123')">
                    <i class="bi bi-mortarboard me-1"></i> Student
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillDemo(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
    document.getElementById('username').focus();
}

// Client-side validation
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>
</body>
</html>
