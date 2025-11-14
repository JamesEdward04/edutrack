<?php

session_start();
include '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['studentID']) && !isset($_SESSION['studentNumber'])) {
  header("Location: student-login.html");
  exit();
}

// Resolve student id and name
$student_id = isset($_SESSION['studentID']) ? (int)$_SESSION['studentID'] : 0;
$student_name = $_SESSION['studentName'] ?? '';

if ($student_id <= 0 && isset($_SESSION['studentNumber'])) {
  $sn = $_SESSION['studentNumber'];
  $stmt = $conn->prepare("SELECT id, fullName FROM students WHERE studentNumber = ? LIMIT 1");
  $stmt->bind_param('s', $sn);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $student_id = (int)$row['id'];
    $student_name = $row['fullName'];
  }
  $stmt->close();
}

// Fallback name
if (empty($student_name)) {
  $student_name = 'Student';
}

if ($student_id <= 0) {
  echo "Invalid session. Please log in again.";
  exit();
}



// Total grades count and average
$total_grades = 0;
$avg_grade = null;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt, AVG(grade) AS avg_grade FROM grades WHERE studentID = ?")) {
  $stmt->bind_param('i', $student_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($r = $res->fetch_assoc()) {
    $total_grades = (int)$r['cnt'];
    $avg_grade = $r['avg_grade'] !== null ? round((float)$r['avg_grade'], 2) : null;
  }
  $stmt->close();
}

// Total attendance count
$total_attendance = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM attendance WHERE studentID = ?")) {
  $stmt->bind_param('i', $student_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($r = $res->fetch_assoc()) {
    $total_attendance = (int)$r['cnt'];
  }
  $stmt->close();
}

// Teachers (admins) managing this student
$teachers = [];
if ($stmt = $conn->prepare("
  SELECT a.admin_id,
         COALESCE(ad.fullName, CONCAT('Admin ', a.admin_id)) AS adminName,
         a.assigned_at
  FROM admin_students a
  LEFT JOIN admins ad ON a.admin_id = ad.id
  WHERE a.student_id = ?
  ORDER BY adminName ASC
")) {
  $stmt->bind_param('i', $student_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $teachers[] = $row;
  }
  $stmt->close();
}

// Recent grades
$recent_grades = [];
if ($stmt = $conn->prepare("SELECT subject, grade, date_recorded FROM grades WHERE studentID = ? ORDER BY date_recorded DESC LIMIT 6")) {
  $stmt->bind_param('i', $student_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) {
    $recent_grades[] = $r;
  }
  $stmt->close();
}


$css_path = '../css/admin-dashboard.css';
$js_path  = '../js/admin-dashboard.js';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Student Dashboard</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>

  <!-- Reuse Admin CSS -->
  <link rel="stylesheet" href="<?= htmlspecialchars($css_path) ?>">
  <style>
    /* tiny student-only tweaks */
    .teacher-list { margin-top: 8px; display:flex; flex-direction:column; gap:8px; }
    .teacher-item { background:#fff; border-radius:10px; padding:10px 12px; box-shadow:0 3px 10px rgba(0,0,0,0.04); display:flex; justify-content:space-between; align-items:center; }
    .sub-stat { color: var(--text-gray); margin-top:8px; font-weight:500; }
    .recent-table { width:100%; border-collapse:collapse; margin-top:12px; }
    .recent-table th, .recent-table td { border:1px solid #eee; padding:8px; font-size:13px; text-align:left; }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="header-left">
      <button id="hamburger" class="hamburger" aria-label="Toggle navigation" aria-expanded="false" aria-controls="sidebar">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
      </button>

      <h1 class="site-title">Student Dashboard</h1>
    </div>

    <div class="header-right">
      <div class="welcome">Welcome, <?= htmlspecialchars($student_name) ?></div>
      <a href="../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div id="page-overlay" class="page-overlay" tabindex="-1" aria-hidden="true"></div>

  <div class="container">
    <aside id="sidebar" class="sidebar" role="navigation" aria-label="Student navigation">
      <h3>Navigation</h3>
      <ul>
        <li><a href="student-dashboard.php"> Home</a></li>
        <li><a href="grades/view-grades.php"> My Grades</a></li>
        <li><a href="attendance/view-attendance.php"> My Attendance</a></li>
      </ul>
    </aside>

    <main class="content" role="main">
      <h2>Welcome back, <?= htmlspecialchars($student_name) ?>!</h2>
      <p class="welcome">Here's a quick summary of your records.</p>

      <!-- Summary Cards -->
      <div class="dashboard-cards">
        <div class="card">
          <h3>Total Grades</h3>
          <p class="card-stat"><?= (int)$total_grades ?></p>
          <?php if ($avg_grade !== null): ?>
            <div class="sub-stat">Average: <strong><?= htmlspecialchars($avg_grade) ?></strong></div>
          <?php else: ?>
            <div class="sub-stat">No grades recorded yet</div>
          <?php endif; ?>
        </div>

        <div class="card">
          <h3>Total Attendance</h3>
          <p class="card-stat"><?= (int)$total_attendance ?></p>
          <div class="sub-stat">Attendance records</div>
        </div>

        <div class="card">
          <h3>Teachers Managing You</h3>
          <?php if (!empty($teachers)): ?>
            <div class="teacher-list" aria-live="polite">
              <?php foreach ($teachers as $t): ?>
                <div class="teacher-item">
                  <div><?= htmlspecialchars($t['adminName']) ?></div>
                  <div style="font-size:12px; color:var(--text-gray)"><?= htmlspecialchars(isset($t['assigned_at']) && $t['assigned_at'] ? date('M j, Y', strtotime($t['assigned_at'])) : '') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="sub-stat">No teachers assigned to you yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <h2>Quick Access</h2>
      <div class="quick-links">
        <a href="grades/view-grades.php" class="quick-link"> View Grades</a>
        <a href="attendance/view-attendance.php" class="quick-link"> View Attendance</a>
        <a href="contact-teachers.php" class="quick-link"> Contact Teachers</a>
      </div>

      <h2 style="margin-top:32px">Recent Grades</h2>
      <?php if (!empty($recent_grades)): ?>
        <table class="recent-table">
          <thead><tr><th>Subject</th><th>Grade</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($recent_grades as $rg): ?>
              <tr>
                <td><?= htmlspecialchars($rg['subject']) ?></td>
                <td><?= htmlspecialchars($rg['grade']) ?></td>
                <td><?= htmlspecialchars($rg['date_recorded']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="sub-stat"><em>No recent grades available.</em></p>
      <?php endif; ?>
    </main>
  </div>

  <script src="<?= htmlspecialchars($js_path) ?>"></script>
</body>
</html>
