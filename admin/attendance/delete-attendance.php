<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id = $_SESSION['adminID'];
$attendanceID = $_GET['id'] ?? null;

if (!$attendanceID) {
  die("No attendance ID specified.");
}

// Delete only if it belongs to this admin
$sql = "DELETE FROM attendance WHERE attendanceID='$attendanceID' AND admin_id='$admin_id'";
if (mysqli_query($conn, $sql)) {
  header("Location: view-attendance.php?success=Attendance deleted successfully");
  exit();
} else {
  echo "Error: " . mysqli_error($conn);
}
?>
