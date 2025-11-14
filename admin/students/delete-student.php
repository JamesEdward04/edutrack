<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id = $_SESSION['adminID'];
$id = $_GET['id'];

//  Delete only this admin's student
$sql = "DELETE FROM students WHERE id='$id' AND admin_id='$admin_id'";
if (mysqli_query($conn, $sql)) {
  header("Location: view-students.php?success=Student deleted successfully");
  exit();
} else {
  echo "Error: " . mysqli_error($conn);
}
?>
