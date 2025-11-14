<?php
session_start();
include '../../db_connect.php'; 

// Redirect if not logged in
if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id   = (int) $_SESSION['adminID'];
$admin_name = $_SESSION['adminName'] ?? 'Admin';

/* Read selected student from GET (dropdown). If not provided, fall back to session-managed_student_id if available. */
$selectedStudent = isset($_GET['student']) ? (int)$_GET['student'] : 0;
if ($selectedStudent === 0 && isset($_SESSION['managed_student_id'])) {
    $selectedStudent = (int) $_SESSION['managed_student_id'];
}

/* Fetch students assigned to this admin for the dropdown */
$students = [];
$sq = "
  SELECT s.id, s.fullName
  FROM students s
  JOIN admin_students a ON s.id = a.student_id
  WHERE a.admin_id = ?
  ORDER BY s.fullName ASC
";
$sstmt = $conn->prepare($sq);
if ($sstmt) {
    $sstmt->bind_param('i', $admin_id);
    $sstmt->execute();
    $sres = $sstmt->get_result();
    while ($r = $sres->fetch_assoc()) {
        $students[] = $r;
    }
    $sstmt->close();
}



// Build and prepare the attendance query with explicit binding per branch (
if ($selectedStudent > 0) {
    $sql = "
      SELECT a.attendanceID,
             s.fullName,
             a.date AS date_recorded,
             a.status AS status,
             a.remarks AS remarks,
             a.admin_id AS record_admin_id
      FROM attendance a
      JOIN students s ON a.studentID = s.id
      JOIN admin_students asg ON s.id = asg.student_id
      WHERE asg.admin_id = ? AND asg.student_id = ? AND a.admin_id = ?
      ORDER BY a.date DESC, s.fullName ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { die("Database prepare error: " . htmlspecialchars($conn->error)); }
    $stmt->bind_param('iii', $admin_id, $selectedStudent, $admin_id);
} else {
    $sql = "
      SELECT a.attendanceID,
             s.fullName,
             a.date AS date_recorded,
             a.status AS status,
             a.remarks AS remarks,
             a.admin_id AS record_admin_id
      FROM attendance a
      JOIN students s ON a.studentID = s.id
      JOIN admin_students asg ON s.id = asg.student_id
      WHERE asg.admin_id = ? AND a.admin_id = ?
      ORDER BY a.date DESC, s.fullName ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { die("Database prepare error: " . htmlspecialchars($conn->error)); }
    $stmt->bind_param('ii', $admin_id, $admin_id);
}

$stmt->execute();
$result = $stmt->get_result();
$attendances = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Helper to format date if value exists (short month, no time) */
function friendly_date($val) {
    if (!$val) return 'N/A';
    $t = strtotime($val);
    if ($t === false) return htmlspecialchars($val);
    return date('M j, Y', $t); // e.g. "Nov 12, 2025"
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>View Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --purple-start: #6A11CB;
      --purple-end: #9B59B6;
      --gold: #FFD700;
      --light-bg: #FFFFFF;
      --light-gray: #F8F8FF;
      --text-dark: #1E1E2D;
      --text-gray: #6B6B83;
      --success: #10b981;
      --absent: #ef4444;
      --late: #f59e0b;
    }

    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--light-gray);
      color: var(--text-dark);
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* ---------- Header (matches view-grades) ---------- */
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

    .header-left { display:flex; align-items:center; gap:16px; }
    .site-title { font-size:22px; font-weight:700; color:#fff; margin:0; }
    .header-right { display:flex; align-items:center; gap:16px; }

    .welcome-text { color: #fff; font-weight:500; font-size:14px; }
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
    .logout:hover { background: #e6c200; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,0.15); }

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
    .back-btn:hover { background: rgba(255,255,255,0.3); transform: translateX(-2px); }

    /* ---------- Main Content ---------- */
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

    .page-title { font-size: 28px; font-weight: 700; color: var(--text-dark); margin: 0; }

    .header-actions { display:flex; gap:12px; flex-wrap:wrap; }

    .btn {
      padding: 12px 24px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 700;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      border: none;
      cursor: pointer;
    }

    .btn-primary {
      background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
      color: #fff;
      box-shadow: 0 4px 12px rgba(106,17,203,0.2);
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(106,17,203,0.3); }

    .btn-secondary {
      background: #fff;
      color: var(--purple-start);
      border: 2px solid var(--purple-start);
    }
    .btn-secondary:hover { background: var(--purple-start); color: #fff; transform: translateY(-2px); }

    .success {
      background: #d1fae5;          
      color: #065f46;               
      border: 1px solid #6ee7b7;
      padding: 14px 18px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .success .check {
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:28px;
      height:28px;
      border-radius:999px;
      background:#10b981;
      color:#fff;
      font-weight:700;
      font-size:14px;
    }

    /* ---------- Filter Section (Student dropdown) ---------- */
    .filter-section {
      background: #fafafa;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 24px;
    }

    .filter-label {
      display: block;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-dark);
      font-size: 14px;
    }

    .filter-controls {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }

    select {
      flex: 1;
      min-width: 250px;
      padding: 12px 14px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 15px;
      font-family: 'Poppins', sans-serif;
      color: var(--text-dark);
      transition: all 0.2s ease;
      background: #fff;
    }

    .filter-btn {
      padding: 12px 20px;
      background: var(--purple-start);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
    }

    .clear-btn {
      padding: 12px 20px;
      background: #f0f0f0;
      color: var(--text-dark);
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: 'Poppins', sans-serif;
      text-decoration: none;
      font-size: 14px;
    }

    /* ---------- Table ---------- */
    .table-container {
      overflow-x: auto;
      margin-top: 20px;
    }

    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { padding: 16px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    th { background: linear-gradient(90deg, var(--purple-start), var(--purple-end)); color: #fff; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }

    .status {
      font-weight: 700;
      padding: 6px 10px;
      border-radius: 999px;
      display: inline-block;
      font-size: 13px;
    }
    .status.present { background: rgba(16,185,129,0.12); color: var(--success); }
    .status.absent { background: rgba(239,68,68,0.08); color: var(--absent); }
    .status.late { background: rgba(245,158,11,0.08); color: var(--late); }

    tbody tr:hover { background: #f9fafb; }
    tbody tr:nth-child(even) { background: #fafafa; }
    tbody tr:nth-child(even):hover { background: #f9fafb; }

    .action-links { display:flex; gap:8px; align-items:center; }
    .action { color: var(--purple-start); font-weight: 600; text-decoration: none; padding: 6px 12px; border-radius: 6px; transition: all 0.2s ease; font-size: 13px; }
    .action.delete { color: #ef4444; }

    .empty { text-align:center; padding:60px 24px; color:var(--text-gray); font-size:15px; }
    .empty-title { font-size:20px; font-weight:700; color:var(--text-dark); margin-bottom:8px; }

    /* ---------- Responsive: Tablets ---------- */
    @media (max-width: 992px) {
      .content { padding: 0 24px; margin: 24px auto; }
      .content-card { padding: 24px; }
      .page-title { font-size: 24px; }
      .site-title { font-size: 20px; }
      .filter-controls { flex-direction: column; align-items: stretch; }
      select { min-width: 100%; }
    }

    /* ---------- Responsive: Mobile ---------- */
    @media (max-width: 768px) {
      .site-header { padding: 12px 16px; }
      .site-title { font-size: 18px; }
      .welcome-text { display: none; }
      .back-btn { padding: 6px 12px; font-size: 13px; }
      .content { padding: 0 16px; margin: 20px auto; }
      .content-card { padding: 20px; }
      .page-header { flex-direction: column; align-items: flex-start; }
      .page-title { font-size: 22px; }
      .header-actions { width: 100%; }
      .btn { flex: 1; justify-content: center; padding: 10px 16px; }
      .filter-section { padding: 16px; }
      .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
      table { font-size: 13px; }
      th, td { padding: 12px 8px; }
      th { font-size: 12px; }
    }

    /* ---------- Responsive: Small Phones ---------- */
    @media (max-width: 480px) {
      .site-header { padding: 10px 12px; }
      .site-title { font-size: 16px; }
      .content { padding: 0 12px; margin: 16px auto; }
      .content-card { padding: 16px; }
      .page-title { font-size: 20px; }
      .btn { padding: 8px 12px; font-size: 13px; }
      table { font-size: 12px; }
      th, td { padding: 10px 6px; }
      .action { padding: 4px 8px; font-size: 12px; }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="header-left">
      <a href="../admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
      <h1 class="site-title">Attendance Records</h1>
    </div>

    <div class="header-right">
      <div class="welcome-text">Welcome, <?= htmlspecialchars($admin_name) ?></div>
      <a href="../../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div class="content">
    <div class="content-card">
      <div class="page-header">
        <h2 class="page-title">Attendance List</h2>
        <div class="header-actions">
          <a href="add-attendance.php" class="btn btn-primary">Add Attendance</a>
          <a href="../attendance/print-attendance.php" target="_blank" class="btn btn-secondary">Print Report</a>
        </div>
      </div>

      <?php if (isset($_GET['success'])): ?>
        <div class="success" role="status">
          <span class="check">✓</span>
          <span><?= htmlspecialchars($_GET['success']) ?></span>
        </div>
      <?php endif; ?>

      <!-- Student dropdown filter (matches student view style) -->
      <div class="filter-section" aria-label="Filter by student">
        <label class="filter-label">Filter by Student</label>
        <form method="get" action="" class="filter-controls" role="search">
          <select name="student" aria-label="Select student">
            <option value="0">All Assigned Students</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $selectedStudent === (int)$s['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['fullName']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <button type="submit" class="filter-btn">Filter</button>
          <a href="view-attendance.php" class="clear-btn">Clear</a>
        </form>
      </div>

      <?php if (!empty($attendances)): ?>
        <div class="table-container" role="region" aria-label="Attendance records">
          <table role="table" aria-label="Attendance table">
            <thead>
              <tr>
                <th>Student Name</th>
                <th>Date</th>
                <th>Status</th>
                <th>Remarks</th>
                <th style="width:160px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendances as $row):
                $rawStatus = $row['status'] ?? null;
                $statusLower = $rawStatus ? strtolower(trim($rawStatus)) : '';
                $statusClass = 'present';
                if ($statusLower === 'absent') $statusClass = 'absent';
                else if ($statusLower === 'late') $statusClass = 'late';
              ?>
                <tr>
                  <td><?= htmlspecialchars($row['fullName']) ?></td>
                  <td><?= friendly_date($row['date_recorded']) ?></td>
                  <td><span class="status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($row['status'] ?? 'N/A') ?></span></td>
                  <td><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
                  <td>
                    <div class="action-links">
                      <a href="edit-attendance.php?id=<?= (int)$row['attendanceID'] ?>" class="action">Edit</a>
                      <a href="delete-attendance.php?id=<?= (int)$row['attendanceID'] ?>" class="action delete" onclick="return confirm('Are you sure you want to delete this attendance record?')">Delete</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">
          <div class="empty-title">No Attendance Records Found</div>
          <p>No attendance records were found for your assigned students.</p>
          <a href="add-attendance.php" class="empty-link">Add Attendance</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php $conn->close(); ?>
</body>
</html>
