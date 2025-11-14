<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id = $_SESSION['adminID'];
$admin_name = $_SESSION['adminName'];
$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM students WHERE id='$id' AND admin_id='$admin_id'");
$student = mysqli_fetch_assoc($result);

if (!$student) {
  die("Unauthorized access or student not found.");
}

if (isset($_POST['update_student'])) {
  $fullName = mysqli_real_escape_string($conn, $_POST['fullName']);
  $studentNumber = mysqli_real_escape_string($conn, $_POST['studentNumber']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $phoneNumber = mysqli_real_escape_string($conn, $_POST['phoneNumber']);
  $city = mysqli_real_escape_string($conn, $_POST['city']);
  $province = mysqli_real_escape_string($conn, $_POST['province']);
  $gender = mysqli_real_escape_string($conn, $_POST['gender']);
  $has_grades_enabled = isset($_POST['has_grades_enabled']) ? 1 : 0;
$has_attendance_enabled = isset($_POST['has_attendance_enabled']) ? 1 : 0;

  $sql = "UPDATE students SET
fullName='$fullName',
studentNumber='$studentNumber',
email='$email',
phoneNumber='$phoneNumber',
city='$city',
province='$province',
gender='$gender',
has_grades_enabled='$has_grades_enabled',
has_attendance_enabled='$has_attendance_enabled'
WHERE id='$id' AND admin_id='$admin_id'";

  if (mysqli_query($conn, $sql)) {
    header("Location: view-students.php?success=Student updated successfully");
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
  <title>Edit Student</title>
  <link rel="stylesheet" href="../../css/admin-dashboard.css">
</head>
<body>
  <header>
    <h1>Edit Student</h1>
    <div>Welcome, <?php echo htmlspecialchars($admin_name); ?> | <a href="../../logout.php" class="logout">Logout</a></div>
  </header>

  <div class="content">
    <h2>Edit Student Info</h2>
    <form method="POST" class="crud-form">
      <label>Full Name:</label>
      <input type="text" name="fullName" value="<?php echo htmlspecialchars($student['fullName']); ?>" required>

      <label>Student Number:</label>
      <input type="text" name="studentNumber" value="<?php echo htmlspecialchars($student['studentNumber']); ?>" required>

      <label>Email:</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>

      <label>Phone Number:</label>
      <input type="text" name="phoneNumber" value="<?php echo htmlspecialchars($student['phoneNumber']); ?>">

      <label>City:</label>
      <input type="text" name="city" value="<?php echo htmlspecialchars($student['city']); ?>">

      <label>Province:</label>
      <input type="text" name="province" value="<?php echo htmlspecialchars($student['province']); ?>">

      <label>Gender:</label>
      <select name="gender">
        <option <?php if ($student['gender'] == 'Male') echo 'selected'; ?>>Male</option>
        <option <?php if ($student['gender'] == 'Female') echo 'selected'; ?>>Female</option>
      </select>
      <label style="display:block; margin-top:.5rem;">
<input type="checkbox" name="has_grades_enabled" value="1" <?php echo ((int)$student['has_grades_enabled'] === 1) ? 'checked' : ''; ?>>
Include in Grades
</label>
<label style="display:block;">
<input type="checkbox" name="has_attendance_enabled" value="1" <?php echo ((int)$student['has_attendance_enabled'] === 1) ? 'checked' : ''; ?>>
Track Attendance
</label>

      <button type="submit" name="update_student" class="add-btn">Update Student</button>
    </form>
  </div>
</body>
</html>
