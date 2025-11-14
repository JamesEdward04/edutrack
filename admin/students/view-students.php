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

// Fetch assigned students (only those that still exist)
$stmt = $conn->prepare("
  SELECT s.id, s.fullName, s.studentNumber, s.email, s.phoneNumber, a.assigned_at
  FROM admin_students a
  JOIN students s ON a.student_id = s.id
  WHERE a.admin_id = ?
  ORDER BY s.fullName ASC
");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$assigned = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Assigned Students</title>

<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
  --purple-start: #6A11CB;
  --purple-end: #9B59B6;
  --gold: #FFD700;
  --light-bg: #FFFFFF;
  --light-gray: #F8F8FF;
  --text-dark: #1E1E2D;
  --text-gray: #6B6B83;
}

/* ---------- Reset & Base ---------- */
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
  font-family: 'Poppins', sans-serif;
  background: var(--light-gray);
  color: var(--text-dark);
  -webkit-font-smoothing:antialiased;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Header */
.site-header {
  background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
  color: #fff;
  padding: 16px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.15);
  position: sticky;
  top: 0;
  z-index: 1200;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-btn {
  background: rgba(255,255,255,0.2);
  color: #fff;
  padding: 8px 16px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

.back-btn:hover {
  background: rgba(255,255,255,0.3);
  transform: translateX(-2px);
}

.site-title {
  font-size: 22px;
  font-weight: 700;
  color: #fff;
  margin: 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.welcome-text {
  color: #fff;
  font-weight: 500;
  font-size: 14px;
}

.logout {
  background: var(--gold);
  color: var(--purple-start);
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 700;
  transition: all 0.2s ease;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  white-space: nowrap;
  font-size: 14px;
}

.logout:hover {
  background: #e6c200;
  transform: translateY(-2px);
}

/* ---------- Content ---------- */
.content {
  flex: 1;
  max-width: 1400px;
  width: 100%;
  margin: 40px auto;
  padding: 0 40px;
}

.content-card {
  background: var(--light-bg);
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-dark);
  margin: 0;
}

.add-btn {
  padding: 12px 20px;
  border-radius: 10px;
  background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
  color: #fff;
  font-weight: 700;
  text-decoration: none;
}

/* ---------- Table ---------- */
.table-container {
  overflow-x: auto;
  margin-top: 20px;
}

table { 
  width: 100%; 
  border-collapse: collapse; 
  font-size: 14px; 
  min-width: 720px;
}

th, td {
  padding: 16px 12px;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}

th {
  background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
  color: #fff;
  font-weight: 700;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* student column */
.student-name { font-weight:700; display:block; }
.student-sub { font-size:13px; color:var(--text-gray); margin-top:6px; }

/* action */
.action-cell { display:flex; gap:8px; align-items:center; justify-content:flex-end; }
.action-btn, .unassign-btn {
  display:inline-flex; align-items:center; justify-content:center; gap:8px;
  padding:8px 12px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; text-decoration:none;
}
.action-btn { background:#fff; border:2px solid var(--purple-start); color:var(--purple-start); }
.action-btn:hover { background:var(--purple-start); color:#fff; }
.unassign-btn { background:#ef4444; color:#fff; border:none; font-family:'Poppins',sans-serif; font-weight:700; }
.unassign-btn:hover { background:#dc2626; }

/* mobile: stacked rows  */
@media (max-width: 768px) {
  .content { padding: 0 24px; margin: 24px auto; }
  .page-title { font-size: 24px; }
  table, thead, tbody, tr, td, th { display:block; width:100%; }
  thead { display:none; }
  tr { background:#fff; margin-bottom:14px; padding:14px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.04); }
  td { display:flex; justify-content:space-between; padding:10px 0; border-bottom:none; }
  td::before { content:attr(data-label); font-weight:700; color:var(--text-gray); margin-right:12px; text-transform:uppercase; font-size:12px; }
  .action-cell { flex-direction:column; align-items:stretch; gap:8px; margin-top:8px; }
  .action-btn, .unassign-btn { width:100%; height:44px; }
}

/* very small phones */
@media (max-width: 480px) {
  .content { padding: 0 12px; margin: 16px auto; }
  .content-card { padding: 20px; }
  .welcome-text { display:none; } 
}
</style>
</head>
<body>
  <header class="site-header">
    <div class="header-left">
      <a href="../admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
      <h1 class="site-title">Assigned Students</h1>
    </div>

    <div class="header-right">
      <div class="welcome-text">Welcome, <?= htmlspecialchars($admin_name) ?></div>
      <a href="../../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div class="content">
    <div class="content-card">
      <div class="page-header">
        <h2 class="page-title">Your Assigned Students</h2>
        <a href="add-student.php" class="add-btn">Assign New Student</a>
      </div>

      <?php if (!empty($assigned)): ?>
        <div class="table-container" role="region" aria-label="Assigned students">
          <table role="table" aria-label="Assigned students table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Student No.</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Assigned At</th>
                <th style="width:220px">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assigned as $s): ?>
                <tr>
                  <td data-label="Name">
                    <span class="student-name"><?= htmlspecialchars($s['fullName']) ?></span>
                    <?php if (!empty($s['studentNumber'])): ?>
                      <span class="student-sub">ID: <?= htmlspecialchars($s['studentNumber']) ?></span>
                    <?php endif; ?>
                  </td>

                  <td data-label="Student No."><?= htmlspecialchars($s['studentNumber']) ?></td>
                  <td data-label="Email"><?= htmlspecialchars($s['email']) ?></td>
                  <td data-label="Phone"><?= htmlspecialchars($s['phoneNumber']) ?></td>
                  <td data-label="Assigned At"><?= htmlspecialchars($s['assigned_at']) ?></td>

                  <td data-label="Action" class="action-cell">
                    <a class="action-btn" href="../grades/view-grades.php?student=<?= (int)$s['id'] ?>">Grades</a>
                    <a class="action-btn" href="../attendance/view-attendance.php?student=<?= (int)$s['id'] ?>">Attendance</a>

                    <form method="post" action="unassign-student.php" onsubmit="return confirm('Unassign this student?');" style="display:inline-block">
                      <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                      <button type="submit" class="unassign-btn">Unassign</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">
          <h3>No Students Assigned Yet</h3>
          <p>You haven't assigned any students to your account.</p>
          <a href="add-student.php" class="add-btn" style="display:inline-block;margin-top:12px">Assign Your First Student</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php $conn->close(); ?>
</body>
</html>
