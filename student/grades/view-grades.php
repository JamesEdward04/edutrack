<?php
session_start();
include '../../db_connect.php';

// Require student login
if (!isset($_SESSION['studentNumber'])) {
    header("Location: ../student-login.html");
    exit();
}

// Get student info
$studentNumber = $_SESSION['studentNumber'];
$stmt = $conn->prepare("SELECT id, fullName FROM students WHERE studentNumber = ? LIMIT 1");
$stmt->bind_param('s', $studentNumber);
$stmt->execute();
$studentRes = $stmt->get_result();
$student = $studentRes->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: ../student-login.html");
    exit();
}

$studentID = (int)$student['id'];
$studentName = $student['fullName'];

// Get selected teacher (if any)
$selectedTeacher = isset($_GET['teacher']) ? (int)$_GET['teacher'] : 0;

// Fetch teachers who have assigned grades to this student
$teacherQuery = "
  SELECT DISTINCT a.id, a.fullName
  FROM admins a
  INNER JOIN grades g ON g.admin_id = a.id
  INNER JOIN admin_students ast ON ast.admin_id = a.id AND ast.student_id = g.studentID
  WHERE g.studentID = ?
  ORDER BY a.fullName ASC
";
$teacherStmt = $conn->prepare($teacherQuery);
$teacherStmt->bind_param('i', $studentID);
$teacherStmt->execute();
$teachersRes = $teacherStmt->get_result();
$teachers = [];
while ($t = $teachersRes->fetch_assoc()) {
    $teachers[] = $t;
}
$teacherStmt->close();

// Fetch grades for this student (filtered by teacher if selected)
$sql = "
  SELECT g.gradeID, g.subject, g.grade, g.date_recorded, g.admin_id, a.fullName as teacherName
  FROM grades g
  INNER JOIN admin_students ast ON ast.student_id = g.studentID AND ast.admin_id = g.admin_id
  INNER JOIN admins a ON a.id = g.admin_id
  WHERE g.studentID = ?
";

if ($selectedTeacher > 0) {
    $sql .= " AND g.admin_id = ?";
}

$sql .= " ORDER BY g.date_recorded DESC, g.subject ASC";

$gstmt = $conn->prepare($sql);
if (!$gstmt) {
    die('Database error: ' . htmlspecialchars($conn->error));
}

if ($selectedTeacher > 0) {
    $gstmt->bind_param('ii', $studentID, $selectedTeacher);
} else {
    $gstmt->bind_param('i', $studentID);
}

$gstmt->execute();
$gradesRes = $gstmt->get_result();
$grades = $gradesRes->fetch_all(MYSQLI_ASSOC);
$gstmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Grades — <?= htmlspecialchars($studentName) ?></title>

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

    /* ---------- Filter Section ---------- */
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
    }

    .clear-btn:hover {
      background: #e0e0e0;
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

    /* ---------- Responsive: Mobile (match admin) ---------- */
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

      .filter-section {
        padding: 16px;
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
    }


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

      .filter-section {
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
      <a href="../student-dashboard.php" class="back-btn">← Back to Dashboard</a>
      <h1 class="site-title">My Grades</h1>
    </div>

    <div class="header-right">
      <div class="welcome-text">Welcome, <?= htmlspecialchars($studentName) ?></div>
      <a href="../../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div class="content">
    <div class="content-card">
      <div class="page-header">
        <h2 class="page-title">Student Grade Records</h2>
        <div class="header-actions">
          <a href="print-grades.php" target="_blank" class="btn btn-primary" rel="noopener">
             Print Report
          </a>
        </div>
      </div>

      <!-- Filter by Teacher -->
      <?php if (!empty($teachers)): ?>
        <div class="filter-section">
          <label class="filter-label">Filter by Teacher</label>
          <form method="get" action="" class="filter-controls">
            <select name="teacher" id="teacher">
              <option value="0">All Teachers</option>
              <?php foreach ($teachers as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= $selectedTeacher === (int)$t['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['fullName']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="filter-btn"> Filter</button>
            <?php if ($selectedTeacher > 0): ?>
              <a href="?" class="clear-btn"> Clear</a>
            <?php endif; ?>
          </form>
        </div>
      <?php endif; ?>

      <!-- Show selected teacher info -->
      <?php if ($selectedTeacher > 0): 
        $selectedTeacherName = '';
        foreach ($teachers as $t) {
          if ((int)$t['id'] === $selectedTeacher) {
            $selectedTeacherName = $t['fullName'];
            break;
          }
        }
      ?>
        <div class="info-badge">
           Showing grades from: <strong><?= htmlspecialchars($selectedTeacherName) ?></strong>
        </div>
      <?php endif; ?>

      <?php if (!empty($grades)): ?>
        <div class="table-container">
          <table role="table" aria-label="Student grades">
            <thead>
              <tr>
                <th>#</th>
                <th>Subject</th>
                <th>Grade</th>
                <th>Date Recorded</th>
                <th>Teacher</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; foreach ($grades as $g): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($g['subject']) ?></td>
                  <td><strong><?= htmlspecialchars($g['grade']) ?></strong></td>
                  <td><?= htmlspecialchars($g['date_recorded']) ?></td>
                  <td><?= htmlspecialchars($g['teacherName']) ?></td>
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
            <?php if ($selectedTeacher > 0): ?>
              No grades from the selected teacher yet.
            <?php else: ?>
              You don't have any grade records from your teachers yet.
            <?php endif; ?>
          </p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
<?php
$conn->close();
?>
