<?php
/**
 * Root Router
 * Student Management System
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } elseif ($role === 'teacher') {
        header('Location: ' . BASE_URL . 'teacher/dashboard.php');
    } elseif ($role === 'student') {
        header('Location: ' . BASE_URL . 'student/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'login.php');
    }
    exit;
} else {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
