<?php
session_start();
include '../../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id = $_SESSION['adminID'];
$gradeID = $_GET['id'] ?? null;

if (!$gradeID) {
  die("No grade ID provided.");
}

// Delete only if this grade belongs to the logged-in admin
$sql = "DELETE FROM grades WHERE gradeID='$gradeID' AND admin_id='$admin_id'";
if (mysqli_query($conn, $sql)) {
  header("Location: view-grades.php?success=Grade deleted successfully");
  exit();
} else {
  echo "Error: " . mysqli_error($conn);
}
?>
