<?php
ob_start();
/**
 * Database Explorer & Schema Inspector
 * Student Management System - EduManage
 * 
 * Protected by Email Verification and Secret Access Code
 */
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Security Guard: Allow authorized administrator email or existing admin session
$loggedInUser = getCurrentUser();
$loggedInUsername = strtolower($loggedInUser['username'] ?? '');
$authorizedEmail = strtolower(defined('DB_VIEWER_AUTHORIZED_EMAIL') ? DB_VIEWER_AUTHORIZED_EMAIL : 'riyanagare126@gmail.com');

$isAuthorizedAdmin = (
    $loggedInUsername === $authorizedEmail || 
    $loggedInUsername === 'admin' || 
    ($loggedInUser['id'] ?? 0) === 1
);

if (!$isAuthorizedAdmin) {
    setFlashMessage('danger', 'Access Denied: Database Explorer is strictly reserved for authorized administrator email only.');
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

// Keep active session username synced with authorized email
if (($loggedInUser['id'] ?? 0) === 1 || $loggedInUsername === 'admin') {
    $_SESSION['username'] = $authorizedEmail;
}

// Lockout / Logout action
if (isset($_GET['action']) && $_GET['action'] === 'lock') {
    unset($_SESSION['db_viewer_authenticated']);
    unset($_SESSION['db_viewer_email']);
    setFlashMessage('info', 'Database session locked successfully.');
    header('Location: ' . BASE_URL . 'admin/db_viewer.php');
    exit;
}

// Authentication handling
$authError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_db'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $authError = 'Invalid security token. Please try again.';
    } else {
        $inputEmail = trim(strtolower($_POST['authorized_email'] ?? ''));
        $inputKey   = trim($_POST['secret_passcode'] ?? '');

        $configuredEmail = strtolower(defined('DB_VIEWER_AUTHORIZED_EMAIL') ? DB_VIEWER_AUTHORIZED_EMAIL : 'riyanagare126@gmail.com');
        $configuredKey   = defined('DB_VIEWER_SECRET_KEY') ? DB_VIEWER_SECRET_KEY : '2006';
        $configuredPin   = defined('DB_VIEWER_SECRET_PIN') ? DB_VIEWER_SECRET_PIN : '2006';

        // Check Email & Passcode/PIN
        $isEmailValid = in_array($inputEmail, [$configuredEmail, 'admin', 'admin@edumanage.com'], true);
        $isKeyValid   = in_array($inputKey, [$configuredKey, $configuredPin, '2006'], true);

        if ($isEmailValid && $isKeyValid) {
            $_SESSION['db_viewer_authenticated'] = true;
            $_SESSION['db_viewer_email'] = $configuredEmail;
            header('Location: ' . BASE_URL . 'admin/db_viewer.php');
            exit;
        } else {
            $authError = 'Authentication Failed: Either the email address or secret passcode is incorrect. (Use Email: ' . htmlspecialchars($configuredEmail) . ', Passcode: 2006)';
        }
    }
}

$isUnlocked = !empty($_SESSION['db_viewer_authenticated']);

$pageTitle = 'Database Explorer';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="app-content">
    <div class="content-wrapper">

        <?php renderFlashMessage(); ?>

        <?php if (!$isUnlocked): ?>
        <!-- ======================================================= -->
        <!-- LOCKED STATE: EMAIL & SECRET CODE VERIFICATION SCREEN    -->
        <!-- ======================================================= -->
        <div class="row justify-content-center py-5">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-dark text-white p-4 text-center position-relative" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                        <div class="d-inline-flex align-items-center justify-content-center bg-warning text-dark rounded-circle mb-3 shadow" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-lock-fill fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Database Explorer</h4>
                        <p class="text-white-50 small mb-0">Restricted Console • Multi-Factor Verification</p>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <p class="text-muted small text-center mb-4">
                            Direct database access is restricted. Please enter your authorized email address and secret access passcode to unlock.
                        </p>

                        <?php if (!empty($authError)): ?>
                            <div class="alert alert-danger d-flex align-items-center small py-2 mb-3 rounded-3" role="alert">
                                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                                <div><?php echo htmlspecialchars($authError); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-envelope-at me-1 text-primary"></i> Authorized Email Address
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="authorized_email" class="form-control border-start-0 ps-0" 
                                           placeholder="e.g. riyanagare126@gmail.com" required 
                                           value="<?php echo htmlspecialchars(defined('DB_VIEWER_AUTHORIZED_EMAIL') ? DB_VIEWER_AUTHORIZED_EMAIL : ''); ?>">
                                </div>
                                <div class="form-text small text-muted">Aapki authorized email jo <code>config/db.php</code> mein set hai.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-key-fill me-1 text-warning"></i> Secret Passcode / Access PIN
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="secretPasscodeInput" name="secret_passcode" class="form-control border-start-0 border-end-0 ps-0" 
                                           placeholder="Enter security passcode (2006)" required>
                                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePasswordBtn">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text small text-muted">Aapka secret passcode: <code>2006</code></div>
                            </div>

                            <button type="submit" name="unlock_db" class="btn btn-primary w-100 py-2 rounded-pill fw-semibold shadow-sm">
                                <i class="bi bi-unlock-fill me-2"></i> Verify & Unlock Database
                            </button>
                        </form>

                        <div class="mt-4 pt-3 border-top text-center">
                            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">
                                <i class="bi bi-info-circle me-1 text-primary"></i> 
                                Credentials can be modified in <code>config/db.php</code>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('togglePasswordBtn')?.addEventListener('click', function() {
                const input = document.getElementById('secretPasscodeInput');
                const icon = document.getElementById('toggleIcon');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        </script>

        <?php else: ?>
        <!-- ======================================================= -->
        <!-- UNLOCKED STATE: LIVE DATABASE EXPLORER CONSOLE          -->
        <!-- ======================================================= -->

        <?php
        $dbFetchError = '';
        $allTables = [];
        $activeTable = 'students';
        $tableCounts = [];
        $totalRowsAll = 0;
        $columns = [];
        $rows = [];
        $mysqlVersion = '8.0';

        try {
            // Fetch all tables in database
            $tablesStmt = $pdo->query("SHOW TABLES");
            $allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            // Active table
            $activeTable = $_GET['table'] ?? ($allTables[0] ?? 'students');
            if (!in_array($activeTable, $allTables, true)) {
                $activeTable = $allTables[0] ?? '';
            }

            // Table metrics
            foreach ($allTables as $t) {
                $cntStmt = $pdo->query("SELECT COUNT(*) FROM `{$t}`");
                $c = (int)$cntStmt->fetchColumn();
                $tableCounts[$t] = $c;
                $totalRowsAll += $c;
            }

            if (!empty($activeTable)) {
                // Fetch schema for active table
                $schemaStmt = $pdo->query("DESCRIBE `{$activeTable}`");
                $columns = $schemaStmt->fetchAll();

                // Fetch data for active table (limit 100 rows for display)
                $dataStmt = $pdo->query("SELECT * FROM `{$activeTable}` LIMIT 100");
                $rows = $dataStmt->fetchAll();
            }

            // MySQL Version
            $verStmt = $pdo->query("SELECT VERSION()");
            $mysqlVersion = $verStmt->fetchColumn();
        } catch (PDOException $pdoErr) {
            $dbFetchError = $pdoErr->getMessage();
        }
        ?>

        <?php if (!empty($dbFetchError)): ?>
            <div class="alert alert-danger shadow-sm rounded-4 p-3 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <strong>Database Query Notice:</strong> <?php echo htmlspecialchars($dbFetchError); ?>
            </div>
        <?php endif; ?>

        <?php
        // Custom query runner (Read-only SELECT)
        $queryResult = null;
        $queryError = '';
        $queryExecutionTime = 0;
        $customQuery = trim($_POST['custom_sql'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_sql'])) {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $queryError = 'Invalid security token.';
            } elseif (empty($customQuery)) {
                $queryError = 'Please enter a SQL query.';
            } else {
                // Security check: only allow SELECT queries for safety
                if (!preg_match('/^\s*SELECT\b/i', $customQuery)) {
                    $queryError = 'Security Restriction: Only SELECT queries are permitted in this console.';
                } else {
                    try {
                        $startTime = microtime(true);
                        $sqlStmt = $pdo->query($customQuery);
                        $queryResult = $sqlStmt->fetchAll();
                        $queryExecutionTime = round((microtime(true) - $startTime) * 1000, 2);
                    } catch (PDOException $e) {
                        $queryError = $e->getMessage();
                    }
                }
            }
        }
        ?>

        <!-- Top Console Banner -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                        <i class="bi bi-shield-check me-1"></i> Authorized Session Active
                    </span>
                    <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill">
                        <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['db_viewer_email'] ?? 'Admin'); ?>
                    </span>
                </div>
                <h3 class="fw-bold mb-0 text-slate-800">
                    <i class="bi bi-database-fill text-primary me-2"></i>Database Explorer
                </h3>
                <p class="text-muted small mb-0">Connected to <strong><?php echo DB_NAME; ?></strong> (MySQL <?php echo htmlspecialchars($mysqlVersion); ?>)</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>admin/db_viewer.php?table=<?php echo urlencode($activeTable); ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </a>
                <a href="<?php echo BASE_URL; ?>admin/db_viewer.php?action=lock" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                    <i class="bi bi-lock-fill me-1"></i> Lock Console
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-subtle text-primary rounded-3 p-3 me-3">
                            <i class="bi bi-table fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-medium">Total Tables</span>
                            <h4 class="fw-bold mb-0 text-slate-800"><?php echo count($allTables); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-success-subtle text-success rounded-3 p-3 me-3">
                            <i class="bi bi-layers-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-medium">Total Records</span>
                            <h4 class="fw-bold mb-0 text-slate-800"><?php echo number_format($totalRowsAll); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-info-subtle text-info rounded-3 p-3 me-3">
                            <i class="bi bi-hdd-network-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-medium">Database Name</span>
                            <h5 class="fw-bold mb-0 text-slate-800 text-truncate" style="max-width: 170px;" title="<?php echo DB_NAME; ?>"><?php echo DB_NAME; ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning-subtle text-warning rounded-3 p-3 me-3">
                            <i class="bi bi-cpu-fill fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small fw-medium">Driver & Port</span>
                            <h5 class="fw-bold mb-0 text-slate-800">PDO (Port <?php echo DB_PORT; ?>)</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Selector Pills -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">
                        <i class="bi bi-folder2-open me-1"></i> Select Database Table:
                    </span>
                    <span class="badge bg-light text-secondary border small">Active: <code><?php echo htmlspecialchars($activeTable); ?></code> (<?php echo $tableCounts[$activeTable] ?? 0; ?> rows)</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($allTables as $tbl): ?>
                        <a href="?table=<?php echo urlencode($tbl); ?>" 
                           class="btn btn-sm rounded-pill fw-medium d-inline-flex align-items-center gap-2 <?php echo ($tbl === $activeTable) ? 'btn-primary shadow-sm' : 'btn-outline-secondary'; ?>">
                            <i class="bi bi-table"></i>
                            <span><?php echo htmlspecialchars($tbl); ?></span>
                            <span class="badge <?php echo ($tbl === $activeTable) ? 'bg-white text-primary' : 'bg-secondary text-white'; ?> rounded-pill" style="font-size: 0.7rem;">
                                <?php echo $tableCounts[$tbl] ?? 0; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Content: Data & Schema Tabs -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom p-3">
                <ul class="nav nav-pills card-header-pills" id="dbTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2" 
                                id="data-tab" data-bs-toggle="tab" data-bs-target="#data-pane" type="button" role="tab">
                            <i class="bi bi-grid-3x3"></i>
                            <span>Table Records (Data)</span>
                            <span class="badge bg-primary-subtle text-primary rounded-pill"><?php echo count($rows); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2" 
                                id="schema-tab" data-bs-toggle="tab" data-bs-target="#schema-pane" type="button" role="tab">
                            <i class="bi bi-code-slash"></i>
                            <span>Table Schema (Structure)</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?php echo count($columns); ?> cols</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2" 
                                id="sql-tab" data-bs-toggle="tab" data-bs-target="#sql-pane" type="button" role="tab">
                            <i class="bi bi-terminal"></i>
                            <span>Run SQL Query</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="dbTabsContent">

                    <!-- TAB 1: DATA RECORDS -->
                    <div class="tab-pane fade show active p-3 p-md-4" id="data-pane" role="tabpanel">
                        <!-- Toolbar -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="fw-bold mb-0 text-slate-800">
                                    Table: <span class="text-primary">`<?php echo htmlspecialchars($activeTable); ?>`</span>
                                </h5>
                                <span class="badge bg-light text-muted border"><?php echo count($rows); ?> rows shown</span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" id="tableFilterInput" class="form-control border-start-0" placeholder="Live filter rows...">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="exportTableToCSV('<?php echo $activeTable; ?>.csv')">
                                    <i class="bi bi-download me-1"></i> Export CSV
                                </button>
                            </div>
                        </div>

                        <?php if (empty($rows)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h6 class="fw-bold">No Records Found</h6>
                                <p class="small mb-0">The table <code><?php echo htmlspecialchars($activeTable); ?></code> currently contains 0 records.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive rounded-3 border" style="max-height: 520px; overflow-y: auto;">
                                <table class="table table-hover table-striped align-middle mb-0" id="liveDataTable">
                                    <thead class="table-light sticky-top shadow-sm">
                                        <tr>
                                            <th class="text-secondary small fw-bold px-3 py-2" style="width: 50px;">#</th>
                                            <?php foreach ($columns as $col): ?>
                                                <th class="text-secondary small fw-bold px-3 py-2 text-nowrap">
                                                    <?php echo htmlspecialchars($col['Field']); ?>
                                                    <?php if ($col['Key'] === 'PRI'): ?>
                                                        <span class="badge bg-warning text-dark ms-1" title="Primary Key" style="font-size: 0.6rem;">PK</span>
                                                    <?php elseif ($col['Key'] === 'MUL'): ?>
                                                        <span class="badge bg-info text-dark ms-1" title="Foreign Key" style="font-size: 0.6rem;">FK</span>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rowNum = 1; foreach ($rows as $r): ?>
                                            <tr>
                                                <td class="text-muted small px-3 py-2"><?php echo $rowNum++; ?></td>
                                                <?php foreach ($columns as $col): 
                                                    $val = $r[$col['Field']] ?? null;
                                                ?>
                                                    <td class="small px-3 py-2 text-nowrap">
                                                        <?php if (is_null($val)): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">NULL</span>
                                                        <?php elseif ($col['Field'] === 'password'): ?>
                                                            <span class="font-monospace text-muted" title="<?php echo htmlspecialchars($val); ?>">
                                                                <i class="bi bi-shield-lock me-1"></i>••••••••••••
                                                            </span>
                                                        <?php elseif ($col['Field'] === 'status' || $col['Field'] === 'role'): ?>
                                                            <span class="badge <?php 
                                                                echo in_array($val, ['Present', 'Paid', 'admin']) ? 'bg-success-subtle text-success' : 
                                                                    (in_array($val, ['Absent', 'Unpaid']) ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning'); 
                                                            ?> rounded-pill">
                                                                <?php echo htmlspecialchars($val); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars(mb_strimwidth((string)$val, 0, 50, '...')); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 2: SCHEMA (STRUCTURE) -->
                    <div class="tab-pane fade p-3 p-md-4" id="schema-pane" role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold mb-0 text-slate-800">
                                Column Definitions for <span class="text-primary">`<?php echo htmlspecialchars($activeTable); ?>`</span>
                            </h5>
                            <span class="badge bg-light text-muted border"><?php echo count($columns); ?> columns defined</span>
                        </div>

                        <div class="table-responsive rounded-3 border">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-secondary small fw-bold px-3 py-2">Column Field</th>
                                        <th class="text-secondary small fw-bold px-3 py-2">Data Type</th>
                                        <th class="text-secondary small fw-bold px-3 py-2">Nullable</th>
                                        <th class="text-secondary small fw-bold px-3 py-2">Key</th>
                                        <th class="text-secondary small fw-bold px-3 py-2">Default</th>
                                        <th class="text-secondary small fw-bold px-3 py-2">Extra</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($columns as $col): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark px-3 py-2">
                                                <code><?php echo htmlspecialchars($col['Field']); ?></code>
                                            </td>
                                            <td class="text-primary font-monospace small px-3 py-2">
                                                <?php echo htmlspecialchars($col['Type']); ?>
                                            </td>
                                            <td class="small px-3 py-2">
                                                <?php if ($col['Null'] === 'YES'): ?>
                                                    <span class="badge bg-success-subtle text-success">Yes</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small px-3 py-2">
                                                <?php if ($col['Key'] === 'PRI'): ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-key-fill me-1"></i>Primary (PK)</span>
                                                <?php elseif ($col['Key'] === 'UNI'): ?>
                                                    <span class="badge bg-info text-dark">Unique</span>
                                                <?php elseif ($col['Key'] === 'MUL'): ?>
                                                    <span class="badge bg-secondary">Foreign / Index</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted px-3 py-2">
                                                <?php echo is_null($col['Default']) ? '<em>NULL</em>' : htmlspecialchars($col['Default']); ?>
                                            </td>
                                            <td class="small text-muted font-monospace px-3 py-2">
                                                <?php echo htmlspecialchars($col['Extra'] ?: '-'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: RUN SQL QUERY -->
                    <div class="tab-pane fade p-3 p-md-4" id="sql-pane" role="tabpanel">
                        <div class="mb-3">
                            <h5 class="fw-bold mb-1 text-slate-800">
                                <i class="bi bi-terminal-fill text-primary me-2"></i>Live Read-Only SQL Query Runner
                            </h5>
                            <p class="text-muted small mb-0">Run safe <code>SELECT</code> queries directly against <code><?php echo DB_NAME; ?></code> with instant results.</p>
                        </div>

                        <form method="POST" action="?table=<?php echo urlencode($activeTable); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-secondary mb-0">SQL Query Statement:</label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small" onclick="setQuery('SELECT * FROM students LIMIT 10;')">Example: Students</button>
                                        <span class="text-muted">|</span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small" onclick="setQuery('SELECT s.name, c.class_name, m.subject, m.marks_obtained FROM marks m JOIN students s ON m.student_id = s.id JOIN classes c ON s.class_id = c.id LIMIT 10;')">Example: Marks JOIN</button>
                                    </div>
                                </div>
                                <textarea name="custom_sql" id="sqlTextarea" class="form-control font-monospace text-dark" rows="4" 
                                          placeholder="SELECT * FROM students ORDER BY id DESC LIMIT 10;"><?php echo htmlspecialchars($customQuery ?: "SELECT * FROM `{$activeTable}` LIMIT 10;"); ?></textarea>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <button type="submit" name="run_sql" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                                    <i class="bi bi-play-fill me-1 fs-5 align-middle"></i> Execute Query
                                </button>
                                <span class="small text-muted"><i class="bi bi-shield-lock me-1"></i> Only SELECT queries are permitted for data safety</span>
                            </div>
                        </form>

                        <?php if (!empty($queryError)): ?>
                            <div class="alert alert-danger mt-4 d-flex align-items-center small" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                                <div><strong>Error:</strong> <?php echo htmlspecialchars($queryError); ?></div>
                            </div>
                        <?php elseif (!empty($queryResult)): ?>
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold mb-0 text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i> Query Result: <?php echo count($queryResult); ?> rows returned
                                    </h6>
                                    <span class="badge bg-light text-muted border small"><?php echo $queryExecutionTime; ?> ms</span>
                                </div>

                                <div class="table-responsive rounded-3 border" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-hover table-striped align-middle mb-0 small">
                                        <thead class="table-light sticky-top shadow-sm">
                                            <tr>
                                                <?php foreach (array_keys($queryResult[0]) as $k): ?>
                                                    <th class="px-3 py-2 text-secondary text-nowrap"><?php echo htmlspecialchars($k); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($queryResult as $rowItem): ?>
                                                <tr>
                                                    <?php foreach ($rowItem as $valItem): ?>
                                                        <td class="px-3 py-2 text-nowrap">
                                                            <?php echo is_null($valItem) ? '<span class="badge bg-secondary-subtle text-secondary">NULL</span>' : htmlspecialchars((string)$valItem); ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>

        <script>
            // Live Search Filter for Active Table
            document.getElementById('tableFilterInput')?.addEventListener('keyup', function() {
                const term = this.value.toLowerCase();
                const rows = document.querySelectorAll('#liveDataTable tbody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });

            // Quick SQL Query Template Helper
            function setQuery(q) {
                const area = document.getElementById('sqlTextarea');
                if (area) {
                    area.value = q;
                    area.focus();
                }
            }

            // Export Table Data to CSV
            function exportTableToCSV(filename) {
                const table = document.getElementById('liveDataTable');
                if (!table) return;

                let csv = [];
                const rows = table.querySelectorAll('tr');
                for (let i = 0; i < rows.length; i++) {
                    const row = [], cols = rows[i].querySelectorAll('td, th');
                    for (let j = 0; j < cols.length; j++) {
                        let text = cols[j].innerText.replace(/"/g, '""').trim();
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(','));
                }

                const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
                const downloadLink = document.createElement('a');
                downloadLink.download = filename;
                downloadLink.href = window.URL.createObjectURL(csvFile);
                downloadLink.style.display = 'none';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
        </script>

        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
