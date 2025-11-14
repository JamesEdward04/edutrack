<?php
session_start();
include '../../db_connect.php'; 

// Redirect if not logged in
if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id = (int) $_SESSION['adminID'];
$admin_name = $_SESSION['adminName'] ?? 'Admin';

// If a student_id is provided via GET, set it to session and optionally show a message
if (isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
  $sid = (int) $_GET['student_id'];
  $_SESSION['managed_student_id'] = $sid;
  $_SESSION['flash_success'] = 'Now managing the selected student.';
  // Optional: redirect to clean URL without query string
  header('Location: manage-student.php');
  exit();
}

// Get the currently managed student from session
$student_id = isset($_SESSION['managed_student_id']) ? (int) $_SESSION['managed_student_id'] : 0;

if ($student_id <= 0) {
  // No student selected yet
  $_SESSION['flash_error'] = 'No student selected. Please choose a student first.';
  header('Location: add-student.php'); // or view-students.php
  exit();
}

// Fetch student details
$stmt = $conn->prepare("SELECT id, fullName, studentNumber, email, phoneNumber FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
  // Student not found: clear session and send back to assign page
  unset($_SESSION['managed_student_id']);
  $_SESSION['flash_error'] = 'Selected student not found.';
  header('Location: add-student.php');
  exit();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Student - <?= htmlspecialchars($student['fullName']) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f7f7fb;padding:20px}
    .card{background:#fff;padding:18px;border-radius:10px;max-width:900px;margin:0 auto;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
    .btn{display:inline-block;padding:8px 12px;border-radius:6px;text-decoration:none;background:#6A11CB;color:#fff}
    .flash{padding:10px;border-radius:8px;margin-bottom:12px}
    .flash.success{background:#e6ffed;color:#1a7a3a;border:1px solid #b7f0c6}
    .flash.error{background:#ffe6e6;color:#8a1a1a;border:1px solid #f0b7b7}
  </style>
</head>
<body>
  <div class="card">
    <h2>Managing: <?= htmlspecialchars($student['fullName'] . (trim($student['studentNumber']) ? ' — ' . $student['studentNumber'] : '')) ?></h2>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="flash success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash error"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <p><strong>Email:</strong> <?= htmlspecialchars($student['email'] ?? '-') ?> &nbsp; | &nbsp; <strong>Phone:</strong> <?= htmlspecialchars($student['phoneNumber'] ?? '-') ?></p>

    <ul>
      <li><a class="btn" href="../grades/add-grade.php?student_id=<?= (int)$student['id'] ?>">Add / Edit Grades</a></li>
      <li style="margin-top:8px"><a class="btn" href="../attendance/mark-attendance.php?student_id=<?= (int)$student['id'] ?>">Mark Attendance</a></li>
      <li style="margin-top:8px"><a class="btn" href="../reports/student-report.php?student_id=<?= (int)$student['id'] ?>">View Student Report</a></li>
    </ul>

    <form method="post" action="unassign-student.php" onsubmit="return confirm('Unassign this student?');" style="margin-top:14px;">
      <input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>">
      <button type="submit" style="background:#e74c3c;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;">Unassign</button>
    </form>

    <p style="margin-top:12px;font-size:0.9em;color:#666;"><a href="view-students.php">Back to assigned students</a></p>
  </div>
</body>
</html>
