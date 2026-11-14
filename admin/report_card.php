<?php
/**
 * Official Student Academic Report Card (Printable)
 * Student Management System
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$studentId = (int)($_GET['id'] ?? 0);
$currentUser = getCurrentUser();

// Students can only view their own report card
if ($currentUser['role'] === 'student' && (int)$currentUser['ref_id'] !== $studentId) {
    setFlashMessage('danger', 'Unauthorized access to student report card.');
    header('Location: ' . BASE_URL . 'student/dashboard.php');
    exit;
}

if ($studentId <= 0) {
    echo "Invalid student ID.";
    exit;
}

// Fetch Student & Class
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section, c.room_no
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    echo "Student record not found.";
    exit;
}

// Fetch Marks
$mStmt = $pdo->prepare("SELECT * FROM marks WHERE student_id = ? ORDER BY id ASC");
$mStmt->execute([$studentId]);
$marks = $mStmt->fetchAll();

// Calculations
$totalObtained = 0;
$totalMax = 0;
foreach ($marks as $m) {
    $totalObtained += (float)$m['marks_obtained'];
    $totalMax += (float)$m['total_marks'];
}
$overall = calculateGrade($totalObtained, $totalMax > 0 ? $totalMax : 100);
$isPassed = $overall['percentage'] >= 50;

// Attendance summary for report card
$attStmt = $pdo->prepare("
    SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status
");
$attStmt->execute([$studentId]);
$attCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
while ($r = $attStmt->fetch()) {
    $attCounts[$r['status']] = (int)$r['count'];
}
$totalDays = array_sum($attCounts);
$attPercent = ($totalDays > 0) ? round((($attCounts['Present'] + $attCounts['Late']) / $totalDays) * 100, 1) : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Report Card - <?php echo htmlspecialchars($student['name']); ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            padding: 30px 15px;
        }
        .report-card-container {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            border: 2px solid #e2e8f0;
            position: relative;
        }
        .school-badge {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        .table thead th {
            background-color: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table tbody td {
            padding: 12px 14px;
            vertical-align: middle;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            width: 180px;
            margin: 50px auto 5px;
        }
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .report-card-container {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                padding: 25px !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Action Bar (hidden on print) -->
<div class="max-w-860 mx-auto mb-4 text-center no-print" style="max-width: 860px;">
    <div class="d-flex justify-content-between align-items-center">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-printer-fill me-1"></i> Print / Download PDF
            </button>
        </div>
    </div>
</div>

<div class="report-card-container">
    <!-- School Header -->
    <div class="d-flex align-items-center justify-content-between border-bottom pb-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="school-badge">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div>
                <h3 class="fw-bold text-dark mb-0"><?php echo APP_NAME; ?></h3>
                <div class="text-muted small">Academic Excellence &amp; Character Building Institute</div>
                <div class="text-muted small">Affiliated with State Educational Board &bull; Code: ED-2024</div>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill text-uppercase">Academic Transcript</span>
            <div class="small text-muted mt-1">Session: 2024 - 2025</div>
        </div>
    </div>

    <!-- Student Bio Grid -->
    <div class="row g-3 p-3 bg-light rounded-3 mb-4 small">
        <div class="col-sm-6 col-md-3">
            <span class="text-muted d-block">Student Name:</span>
            <strong class="text-dark fs-6"><?php echo htmlspecialchars($student['name']); ?></strong>
        </div>
        <div class="col-sm-6 col-md-3">
            <span class="text-muted d-block">Roll Number:</span>
            <strong class="text-dark fs-6"><?php echo htmlspecialchars($student['roll_no']); ?></strong>
        </div>
        <div class="col-sm-6 col-md-3">
            <span class="text-muted d-block">Class / Section:</span>
            <strong class="text-dark fs-6"><?php echo htmlspecialchars($student['class_name'] . ' (' . $student['section'] . ')'); ?></strong>
        </div>
        <div class="col-sm-6 col-md-3">
            <span class="text-muted d-block">Attendance:</span>
            <strong class="text-dark fs-6"><?php echo $attPercent; ?>% Present</strong>
        </div>
    </div>

    <!-- Marks Breakdown Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Subject</th>
                    <th>Assessment</th>
                    <th class="text-center" style="width: 140px;">Marks Obtained</th>
                    <th class="text-center" style="width: 120px;">Total Marks</th>
                    <th class="text-center" style="width: 100px;">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marks)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No marks recorded yet for this student.</td></tr>
                <?php else: ?>
                    <?php foreach ($marks as $i => $m): 
                        $subGrade = calculateGrade($m['marks_obtained'], $m['total_marks']);
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i + 1; ?></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($m['subject']); ?></strong></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($m['exam_type']); ?></td>
                            <td class="text-center fw-bold"><?php echo number_format($m['marks_obtained'], 1); ?></td>
                            <td class="text-center text-muted"><?php echo number_format($m['total_marks'], 1); ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?php echo $subGrade['badge']; ?>-subtle text-<?php echo $subGrade['badge']; ?> border px-2 py-1">
                                    <?php echo $subGrade['grade']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="3" class="text-end">Aggregate Total:</td>
                    <td class="text-center text-primary fs-6"><?php echo number_format($totalObtained, 1); ?></td>
                    <td class="text-center text-muted fs-6"><?php echo number_format($totalMax, 1); ?></td>
                    <td class="text-center">
                        <span class="badge bg-primary px-2 py-1"><?php echo $overall['grade']; ?></span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Summary Box -->
    <div class="row g-3 p-3 border rounded-3 align-items-center mb-5">
        <div class="col-md-3 text-center border-end">
            <span class="text-muted small d-block text-uppercase">Percentage</span>
            <h3 class="fw-bold text-dark mb-0"><?php echo $overall['percentage']; ?>%</h3>
        </div>
        <div class="col-md-3 text-center border-end">
            <span class="text-muted small d-block text-uppercase">Cumulative GPA</span>
            <h3 class="fw-bold text-primary mb-0"><?php echo number_format($overall['gpa'], 2); ?></h3>
        </div>
        <div class="col-md-3 text-center border-end">
            <span class="text-muted small d-block text-uppercase">Final Grade</span>
            <h3 class="fw-bold text-success mb-0"><?php echo $overall['grade']; ?></h3>
        </div>
        <div class="col-md-3 text-center">
            <span class="text-muted small d-block text-uppercase">Result Status</span>
            <span class="badge <?php echo $isPassed ? 'bg-success' : 'bg-danger'; ?> fs-6 px-3 py-2 rounded-pill mt-1">
                <?php echo $isPassed ? 'PASSED' : 'NEEDS RE-EXAM'; ?>
            </span>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row text-center pt-3">
        <div class="col-4">
            <div class="signature-line"></div>
            <span class="small text-muted fw-semibold">Class Teacher Signature</span>
        </div>
        <div class="col-4">
            <div class="signature-line"></div>
            <span class="small text-muted fw-semibold">Exam Controller</span>
        </div>
        <div class="col-4">
            <div class="signature-line"></div>
            <span class="small text-muted fw-semibold">Principal Seal &amp; Signature</span>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="text-center border-top mt-4 pt-3 text-muted small">
        Generated on <?php echo date('F j, Y - g:i A'); ?> via <?php echo APP_NAME; ?>. This document is system authenticated.
    </div>
</div>

</body>
</html>
