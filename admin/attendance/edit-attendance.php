<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id = $_SESSION['adminID'];
$admin_name = $_SESSION['adminName'];
$attendanceID = $_GET['id'] ?? null;

if (!$attendanceID) {
  die("No attendance record specified.");
}

//  Fetch record owned by this admin
$result = mysqli_query($conn, "SELECT * FROM attendance WHERE attendanceID='$attendanceID' AND admin_id='$admin_id'");
$record = mysqli_fetch_assoc($result);

if (!$record) {
  die("Unauthorized access or record not found.");
}

if (isset($_POST['update_attendance'])) {
  $date = $_POST['date'];
  $status = mysqli_real_escape_string($conn, $_POST['status']);
  $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

  $sql = "UPDATE attendance 
          SET date='$date', status='$status', remarks='$remarks' 
          WHERE attendanceID='$attendanceID' AND admin_id='$admin_id'";

  if (mysqli_query($conn, $sql)) {
    header("Location: view-attendance.php?success=Attendance updated successfully");
    exit();
  } else {
    echo "Error: " . mysqli_error($conn);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Attendance</title>
  <link rel="stylesheet" href="../../css/admin-dashboard.css">
</head>
<body>
  <header>
    <h1>Edit Attendance</h1>
    <div>Welcome, <?php echo htmlspecialchars($admin_name); ?> | <a href="../../logout.php" class="logout">Logout</a></div>
  </header>

  <div class="content">
    <h2>Edit Attendance Record</h2>
    <form method="POST" class="crud-form">
      <label>Date:</label>
      <input type="date" name="date" value="<?php echo htmlspecialchars($record['date']); ?>" required>

      <label>Status:</label>
      <select name="status" required>
        <option <?php if ($record['status'] == 'Present') echo 'selected'; ?>>Present</option>
        <option <?php if ($record['status'] == 'Absent') echo 'selected'; ?>>Absent</option>
        <option <?php if ($record['status'] == 'Late') echo 'selected'; ?>>Late</option>
      </select>

      <label>Remarks:</label>
      <input type="text" name="remarks" value="<?php echo htmlspecialchars($record['remarks']); ?>">

      <button type="submit" name="update_attendance" class="add-btn">Update Attendance</button>
    </form>
  </div>
</body>
</html>
