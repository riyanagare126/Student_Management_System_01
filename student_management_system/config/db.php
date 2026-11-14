<?php
/**
 * Database Connection & Global Configuration
 * Student Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'student_management_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'EduManage SMS');
define('APP_TAGLINE', 'Modern Student Information & Academic Management System');
define('CURRENCY_SYMBOL', '$');

// Database Explorer Security Settings (Restricted Access)
// Authorized email address set for security
define('DB_VIEWER_AUTHORIZED_EMAIL', 'riyanagare126@gmail.com');
// Secret Security Passcode for private access (Set to 2006)
define('DB_VIEWER_SECRET_KEY', '2006');
define('DB_VIEWER_SECRET_PIN', '2006');

// Dynamic Base URL Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Find project root folder relative to current script
$projectDir = preg_replace('#/(admin|student|teacher|config|includes|assets)(/.*)?$#i', '', $scriptDir);
$baseUrl = rtrim($protocol . $host . $projectDir, '/') . '/';
define('BASE_URL', $baseUrl);

// PDO Connection (tries port 3307 first, falls back to 3306)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    try {
        $dsnFallback = "mysql:host=localhost;port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsnFallback, DB_USER, DB_PASS, $options);
    } catch (PDOException $eFallback) {
        // Keep original exception for error display
    }
}

if (!isset($pdo)) {
    // Graceful error display with setup guidance
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - <?php echo APP_NAME; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; }
            .setup-card { max-width: 650px; margin: 80px auto; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card setup-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fs-6 mb-2">Database Connection Required</span>
                    <h3 class="fw-bold text-slate-800">Cannot Connect to Database</h3>
                    <p class="text-muted">EduManage could not establish a connection with MySQL server.</p>
                </div>
                
                <div class="alert alert-warning border-0 small">
                    <strong>Error Message:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
                </div>

                <h6 class="fw-bold text-dark mt-3">Quick Setup Steps in XAMPP / WAMP:</h6>
                <ol class="small text-muted ps-3 mb-4">
                    <li>Open <strong>XAMPP Control Panel</strong> and ensure <strong>Apache</strong> and <strong>MySQL</strong> are started (Green).</li>
                    <li>Open your browser and navigate to <a href="http://localhost/phpmyadmin" target="_blank" class="fw-semibold">http://localhost/phpmyadmin</a>.</li>
                    <li>Click <strong>Import</strong> tab at the top.</li>
                    <li>Select the <code>database.sql</code> file located in this project directory and click <strong>Go / Import</strong>.</li>
                    <li>Refresh this page.</li>
                </ol>
                <div class="text-center">
                    <a href="" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">Try Reconnecting</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
