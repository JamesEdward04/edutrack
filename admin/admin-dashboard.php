<?php

session_start();
include '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['adminID'])) {
    header("Location: admin-login.html");
    exit();
}

// Get current admin details from session
$admin_id = isset($_SESSION['adminID']) ? (int) $_SESSION['adminID'] : 0;
$admin_name = $_SESSION['adminName'] ?? 'Admin';

if ($admin_id <= 0) {
    echo "Invalid session. Please log in again.";
    exit();
}

/* Fetch summary counts (filtered per admin)  */

/*  Total students: count only students that still exist and are assigned to this admin */
$total_students = 0;
$sql_students = "
  SELECT COUNT(DISTINCT s.id) AS cnt
  FROM admin_students a
  JOIN students s ON s.id = a.student_id
  WHERE a.admin_id = ?
";
if ($stmt = $conn->prepare($sql_students)) {
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $total_students = (int)$r['cnt'];
    }
    $stmt->close();
}

/* Total grades: count grades created by this admin for students assigned to this admin. */
$total_grades = 0;
$sql_grades = "
  SELECT COUNT(DISTINCT g.gradeID) AS cnt
  FROM grades g
  JOIN admin_students a ON a.student_id = g.studentID
  JOIN students s ON s.id = a.student_id
  WHERE a.admin_id = ? AND g.admin_id = ?
";
if ($stmt = $conn->prepare($sql_grades)) {
    // bind admin twice (assignment relation + grade creator)
    $stmt->bind_param('ii', $admin_id, $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $total_grades = (int)$r['cnt'];
    }
    $stmt->close();
}

/* Total attendance: count attendance records created by this admin for students assigned to this admin */
$total_attendance = 0;
$sql_att = "
  SELECT COUNT(DISTINCT att.attendanceID) AS cnt
  FROM attendance att
  JOIN admin_students a ON a.student_id = att.studentID
  JOIN students s ON s.id = a.student_id
  WHERE a.admin_id = ? AND att.admin_id = ?
";
if ($stmt = $conn->prepare($sql_att)) {
    $stmt->bind_param('ii', $admin_id, $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $total_attendance = (int)$r['cnt'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin-dashboard.css">
</head>

<body>
  <header class="site-header">
    <div class="header-left">
      <!-- Hamburger for tablet/phone -->
      <button id="hamburger" class="hamburger" aria-label="Toggle navigation" aria-expanded="false" aria-controls="sidebar">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
      </button>

      <h1 class="site-title">Admin Dashboard</h1>
    </div>

    <div class="header-right">
      <div class="welcome">Welcome, <?= htmlspecialchars($admin_name) ?></div>
      <a href="../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <!-- overlay for off-canvas sidebar when open on small screens -->
  <div id="page-overlay" class="page-overlay" tabindex="-1" aria-hidden="true"></div>

  <div class="container">
    <aside id="sidebar" class="sidebar" role="navigation" aria-label="Admin navigation">
      <h3>Navigation</h3>
      <ul>
        <li><a href="admin-dashboard.php"> Home</a></li>
        <li><a href="students/view-students.php"> Manage Students</a></li>
        <li><a href="grades/view-grades.php"> Manage Grades</a></li>
        <li><a href="attendance/view-attendance.php"> Manage Attendance</a></li>
      </ul>
    </aside>

    <main class="content" role="main">
      <h2>Welcome back, <?= htmlspecialchars($admin_name) ?>!</h2>
      <p class="welcome">Here's an overview of your current records.</p>

      <!-- Summary Cards -->
      <div class="dashboard-cards">
        <div class="card">
          <h3>Total Students</h3>
          <p class="card-stat"><?= (int)$total_students ?></p>
        </div>
        <div class="card">
          <h3>Total Grades</h3>
          <p class="card-stat"><?= (int)$total_grades ?></p>
        </div>
        <div class="card">
          <h3>Total Attendance Records</h3>
          <p class="card-stat"><?= (int)$total_attendance ?></p>
        </div>
      </div>

      <h2>Quick Access</h2>
      <div class="quick-links">
        <a href="students/add-student.php" class="quick-link"> Add Student</a>
        <a href="grades/add-grade.php" class="quick-link"> Add Grade</a>
        <a href="attendance/add-attendance.php" class="quick-link"> Add Attendance</a>
      </div>
    </main>
  </div>

  <!-- External JS -->
  <script src="../js/admin-dashboard.js"></script>
</body>
</html>
