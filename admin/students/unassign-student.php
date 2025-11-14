<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../../db_connect.php'; 



if (!isset($_SESSION['adminID'])) {
  // not logged in: redirect
  header("Location: ../admin-login.html");
  exit();
}

$admin_id = (int) $_SESSION['adminID'];
$student_id = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;

if ($student_id <= 0) {
  $_SESSION['flash_error'] = 'Invalid student selected.';
  header('Location: view-students.php');
  exit();
}

// OPTIONAL: confirm the mapping exists before deleting 
$stmtCheck = $conn->prepare("SELECT 1 FROM admin_students WHERE admin_id = ? AND student_id = ? LIMIT 1");
$stmtCheck->bind_param('ii', $admin_id, $student_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$exists = $resCheck && $resCheck->num_rows > 0;
$stmtCheck->close();

if (!$exists) {
  $_SESSION['flash_error'] = 'This student is not assigned to you (or already unassigned).';
  header('Location: view-students.php');
  exit();
}

// Delete mapping for this admin
$stmt = $conn->prepare("DELETE FROM admin_students WHERE admin_id = ? AND student_id = ?");
if (!$stmt) {
  // prepare failed, capture error
  $_SESSION['flash_error'] = 'DB prepare error: ' . $conn->error;
  header('Location: view-students.php');
  exit();
}
$stmt->bind_param('ii', $admin_id, $student_id);
$ok = $stmt->execute();
$err = $stmt->error;
$stmt->close();

if ($ok) {
  $_SESSION['flash_success'] = 'Student unassigned successfully.';
} else {
  $_SESSION['flash_error'] = 'Failed to unassign student: ' . $err;
}

header('Location: view-students.php');
exit();
