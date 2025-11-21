<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id = (int) $_SESSION['adminID'];
$admin_name = $_SESSION['adminName'] ?? 'Admin';

// Fetch students assigned to this admin (for dropdown)
$students = [];
if ($sstmt = $conn->prepare("
  SELECT s.id, s.fullName
  FROM students s
  JOIN admin_students a ON s.id = a.student_id
  WHERE a.admin_id = ?
  ORDER BY s.fullName ASC
")) {
    $sstmt->bind_param('i', $admin_id);
    $sstmt->execute();
    $sres = $sstmt->get_result();
    while ($r = $sres->fetch_assoc()) {
        $students[] = $r;
    }
    $sstmt->close();
}

// read selected student filter
$selectedStudent = isset($_GET['student']) ? (int)$_GET['student'] : 0;

// Build grades query scoped to admin and (optionally) a specific student
if ($selectedStudent > 0) {
    $sql = "
      SELECT g.gradeID, s.fullName, g.subject, g.grade, g.date_recorded, a.fullName AS teacherName
      FROM grades g
      INNER JOIN students s ON g.studentID = s.id
      INNER JOIN admin_students ast ON ast.student_id = s.id
      INNER JOIN admins a ON g.admin_id = a.id
      WHERE ast.admin_id = ? AND g.admin_id = ? AND s.id = ?
      ORDER BY s.fullName ASC, g.date_recorded DESC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) $stmt->bind_param('iii', $admin_id, $admin_id, $selectedStudent);
} else {
    $sql = "
      SELECT g.gradeID, s.fullName, g.subject, g.grade, g.date_recorded, a.fullName AS teacherName
      FROM grades g
      INNER JOIN students s ON g.studentID = s.id
      INNER JOIN admin_students ast ON ast.student_id = s.id
      INNER JOIN admins a ON g.admin_id = a.id
      WHERE ast.admin_id = ? AND g.admin_id = ?
      ORDER BY s.fullName ASC, g.date_recorded DESC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) $stmt->bind_param('ii', $admin_id, $admin_id);
}

if (!$stmt) {
    die("Database prepare error: " . htmlspecialchars($conn->error));
}

$stmt->execute();
$res = $stmt->get_result();
$grades = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>View Grades</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>

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
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* ---------- Header ---------- */
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
      box-shadow: 0 6px 14px rgba(0,0,0,0.15);
    }

    /* ---------- Back Button ---------- */
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

    .page-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0;
    }

    .header-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

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
      font-family: 'Poppins', sans-serif;
    }

    .btn-primary {
      background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
      color: #fff;
      box-shadow: 0 4px 12px rgba(106,17,203,0.2);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(106,17,203,0.3);
    }

    .btn-secondary {
      background: #fff;
      border: 2px solid var(--purple-start);
      color: var(--purple-start);
    }

    .btn-secondary:hover {
      background: #f9f9f9;
      transform: translateY(-2px);
    }

    /* Filter Section  */
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

    select:focus {
      outline: none;
      border-color: var(--purple-start);
      box-shadow: 0 0 0 3px rgba(106,17,203,0.1);
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

    .filter-btn:hover {
      background: var(--purple-end);
      transform: translateY(-2px);
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
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .clear-btn:hover {
      background: #e0e0e0;
    }

    /* 🔍 Search Section */
    .search-section {
      background: #fafafa;
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 16px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .search-input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
      font-size: 14px;
      font-family: 'Poppins', sans-serif;
      background: #fff;
      transition: all 0.2s ease;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--purple-start);
      box-shadow: 0 0 0 3px rgba(106,17,203,0.08);
    }

    /* ---------- Info Badge ---------- */
    .info-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #e0f2fe;
      color: #0369a1;
      padding: 10px 16px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 20px;
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

    tbody tr {
      transition: background 0.2s ease;
    }

    tbody tr:hover {
      background: #f9fafb;
    }

    tbody tr:nth-child(even) {
      background: #fafafa;
    }

    tbody tr:nth-child(even):hover {
      background: #f9fafb;
    }

    .action-links { 
      display: flex; 
      gap: 8px; 
      align-items: center; 
    }

    .action { 
      color: var(--purple-start); 
      font-weight: 600; 
      text-decoration: none; 
      padding: 6px 12px; 
      border-radius: 6px;
      transition: all 0.2s ease;
    }

    .action:hover {
      background: #f0f0f0;
    }

    .action.delete { 
      color: #ef4444; 
    }

    .action.delete:hover {
      background: #fee;
    }

    /* ---------- Empty State ---------- */
    .empty {
      text-align: center;
      padding: 60px 24px;
      color: var(--text-gray);
      font-size: 15px;
    }

    .empty-icon {
      font-size: 64px;
      margin-bottom: 16px;
      opacity: 0.5;
    }

    .empty-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    /* ---------- Responsive: Tablets ---------- */
    @media (max-width: 992px) {
      .content {
        padding: 0 24px;
        margin: 24px auto;
      }

      .content-card {
        padding: 24px;
      }

      .page-title {
        font-size: 24px;
      }

      .site-title {
        font-size: 20px;
      }
    }

    /* ---------- Responsive: Mobile ---------- */
    @media (max-width: 768px) {
      .site-header {
        padding: 12px 16px;
      }

      .site-title {
        font-size: 18px;
      }

      .welcome-text {
        display: none;
      }

      .logout {
        padding: 8px 14px;
        font-size: 13px;
      }

      .back-btn {
        padding: 6px 12px;
        font-size: 13px;
      }

      .content {
        padding: 0 16px;
        margin: 20px auto;
      }

      .content-card {
        padding: 20px;
      }

      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .page-title {
        font-size: 22px;
      }

      .header-actions {
        width: 100%;
      }

      .btn {
        flex: 1;
        justify-content: center;
        padding: 10px 16px;
      }

      .filter-section,
      .search-section {
        padding: 16px;
      }

      .filter-controls {
        flex-direction: column;
        width: 100%;
      }

      select {
        width: 100%;
        min-width: 0;
      }

      .filter-btn,
      .clear-btn {
        width: 100%;
        justify-content: center;
      }

      .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      table {
        font-size: 13px;
      }

      th, td {
        padding: 12px 8px;
      }

      th {
        font-size: 12px;
      }

      .action-links {
        flex-direction: column;
        gap: 4px;
      }

      .action {
        padding: 4px 8px;
        font-size: 13px;
      }
    }

    /* ---------- Responsive: Small Phones ---------- */
    @media (max-width: 480px) {
      .site-header {
        padding: 10px 12px;
      }

      .site-title {
        font-size: 16px;
      }

      .logout {
        padding: 6px 10px;
        font-size: 12px;
      }

      .content {
        padding: 0 12px;
        margin: 16px auto;
      }

      .content-card {
        padding: 16px;
      }

      .page-title {
        font-size: 20px;
      }

      .btn {
        padding: 8px 12px;
        font-size: 13px;
      }

      .filter-section,
      .search-section {
        padding: 12px;
      }

      table {
        font-size: 12px;
      }

      th, td {
        padding: 10px 6px;
      }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="header-left">
      <a href="../admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
      <h1 class="site-title">Grade Records</h1>
    </div>

    <div class="header-right">
      <div class="welcome-text">Welcome, <?= htmlspecialchars($admin_name) ?></div>
      <a href="../../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div class="content">
    <div class="content-card">
      <div class="page-header">
        <h2 class="page-title">Student Grade Records</h2>
        <div class="header-actions">
          <a href="add-grade.php" class="btn btn-primary"> Add New Grade</a>
          <a href="print-grades.php" target="_blank" class="btn btn-secondary" rel="noopener"> Print Report</a>
        </div>
      </div>

      <!-- Filter by Student -->
      <?php if (!empty($students)): ?>
        <div class="filter-section">
          <label class="filter-label">Filter by Student</label>
          <form method="get" action="" class="filter-controls">
            <select name="student" id="student">
              <option value="0">All Assigned Students</option>
              <?php foreach ($students as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= $selectedStudent === (int)$s['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['fullName']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="filter-btn"> Filter</button>
            <?php if ($selectedStudent > 0): ?>
              <a href="view-grades.php" class="clear-btn"> Clear</a>
            <?php endif; ?>
          </form>
        </div>
      <?php endif; ?>

      <!-- Show selected student info -->
      <?php if ($selectedStudent > 0): 
        $selectedStudentName = '';
        foreach ($students as $s) {
          if ((int)$s['id'] === $selectedStudent) {
            $selectedStudentName = $s['fullName'];
            break;
          }
        }
      ?>
        <div class="info-badge">
           Showing grades for: <strong><?= htmlspecialchars($selectedStudentName) ?></strong>
        </div>
      <?php endif; ?>

      <!-- Client-side search for table -->
      <?php if (!empty($grades)): ?>
        <div class="search-section">
          <label class="filter-label" for="gradesSearch">Search Records</label>
          <input
            id="gradesSearch"
            class="search-input"
            type="search"
            placeholder="Search by student, subject, grade, date, or teacher"
            aria-label="Search grade records"
          >
        </div>
      <?php endif; ?>

      <?php if (!empty($grades)): ?>
        <div class="table-container">
          <table role="table" aria-label="Student grades" id="gradesTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>Subject</th>
                <th>Grade</th>
                <th>Date Recorded</th>
                <th>Teacher</th>
                <th style="width:140px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; foreach ($grades as $row): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($row['fullName']) ?></td>
                  <td><?= htmlspecialchars($row['subject']) ?></td>
                  <td><strong><?= htmlspecialchars($row['grade']) ?></strong></td>
                  <td><?= htmlspecialchars($row['date_recorded']) ?></td>
                  <td><?= htmlspecialchars($row['teacherName'] ?? '—') ?></td>
                  <td>
                    <div class="action-links">
                      <a href="edit-grade.php?id=<?= (int)$row['gradeID'] ?>" class="action">Edit</a>
                      <a href="delete-grade.php?id=<?= (int)$row['gradeID'] ?>" class="action delete" onclick="return confirm('Are you sure you want to delete this grade?')">Delete</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">
          <div class="empty-icon"></div>
          <div class="empty-title">No Grades Found</div>
          <p>
            <?php if ($selectedStudent > 0): ?>
              No grades recorded for the selected student yet.
            <?php else: ?>
              No grade records found for your assigned students. Start by adding a new grade!
            <?php endif; ?>
          </p>
          <a href="add-grade.php" style="display:inline-block;margin-top:12px;padding:10px 16px;border-radius:8px;background:var(--purple-start);color:#fff;text-decoration:none;font-weight:600;">Add Grade</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!--  Client-side search script -->
  <script>
    (function () {
      const input = document.getElementById('gradesSearch');
      const table = document.getElementById('gradesTable');
      if (!input || !table) return;

      const rows = Array.from(table.querySelectorAll('tbody tr'));

      input.addEventListener('input', function (e) {
        const q = (e.target.value || '').trim().toLowerCase();

        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          const match = q === '' || text.indexOf(q) !== -1;
          row.style.display = match ? '' : 'none';
        });
      });
    })();
  </script>

</body>
</html>
<?php $conn->close(); ?>
