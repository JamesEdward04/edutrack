<?php

$DEBUG = isset($_GET['debug']) ? (int)$_GET['debug'] : 0;
if ($DEBUG) { ini_set('display_errors', 1); error_reporting(E_ALL); }


session_start();
$ROOT = dirname(__DIR__, 2); // from student/print-grades.php to project root
require_once $ROOT . '/vendor/autoload.php';
require_once $ROOT . '/db_connect.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['studentNumber'])) {
  header("Location: ../student-login.html");
  exit();
}

// Resolve student info
$studentNumber = $_SESSION['studentNumber'];
$stmt = $conn->prepare("SELECT id, fullName FROM students WHERE studentNumber = ? LIMIT 1");
$stmt->bind_param('s', $studentNumber);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student) {
  header("Location: ../student-login.html");
  exit();
}
$studentId = (int)$student['id'];
$studentName = $student['fullName'];

// Dashboard/back URL for student area
$DASHBOARD_URL = '../student-dashboard.php';

function embed_image_datauri($absPath) {
  if (is_readable($absPath)) {
    $data = file_get_contents($absPath);
    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    $mime = in_array($ext, ['png','jpg','jpeg','gif','svg']) ? ($ext === 'svg' ? 'image/svg+xml' : 'image/' . $ext) : 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode($data);
  }
  return '';
}
$logoPath = $ROOT . '/assets/images/edutrack.jpg';
$logoSrc  = embed_image_datauri($logoPath);

//show selection UI if no 'teacher' param or 'generate' not present 
if (!isset($_GET['generate'])) {
  // Fetch teachers assigned to this student (via admin_students)
  $tstmt = $conn->prepare("
    SELECT DISTINCT a.id, a.fullName
    FROM admins a
    INNER JOIN admin_students ast ON ast.admin_id = a.id
    WHERE ast.student_id = ?
    ORDER BY a.fullName ASC
  ");
  $tstmt->bind_param('i', $studentId);
  $tstmt->execute();
  $tres = $tstmt->get_result();
  $teachers = $tres->fetch_all(MYSQLI_ASSOC);
  $tstmt->close();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Print My Grades</title>
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

      .header-left { display: flex; align-items: center; gap: 16px; }
      .site-title { font-size: 22px; font-weight: 700; color: #fff; margin: 0; }
      .header-right { display: flex; align-items: center; gap: 16px; }
      .welcome-text { color: #fff; font-weight: 500; font-size: 14px; }
      .logout { background: var(--gold); color: var(--purple-start); padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; transition: all 0.2s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1); white-space: nowrap; font-size: 14px; }
      .logout:hover { background: #e6c200; transform: translateY(-2px); }

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
      .back-btn:hover { background: rgba(255,255,255,0.3); transform: translateX(-2px); }

      /* ---------- Main Content ---------- */
      .content {
        flex: 1;
        max-width: 800px;
        width: 100%;
        margin: 40px auto;
        padding: 0 40px;
      }

      .card {
        background: var(--light-bg);
        width: 100%;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      }

      .card-title {
        text-align: center;
        color: var(--text-dark);
        margin: 0 0 28px 0;
        font-size: 28px;
        font-weight: 700;
      }

      .form-group { margin-bottom: 24px; }

      label { display:block; margin-bottom:8px; font-weight:600; color:var(--text-dark); font-size:14px; }

      select {
        width:100%;
        padding:12px 14px;
        border-radius:10px;
        border:2px solid #e0e0e0;
        font-size:15px;
        font-family:'Poppins',sans-serif;
        color:var(--text-dark);
        background:#fff;
      }
      select:focus { outline:none; border-color:var(--purple-start); box-shadow:0 0 0 3px rgba(106,17,203,0.1); }

      .btn {
        width:100%;
        padding:14px 0;
        font-weight:700;
        color:#fff;
        border:none;
        border-radius:10px;
        cursor:pointer;
        transition:all 0.2s ease;
        background:linear-gradient(90deg,var(--purple-start),var(--purple-end));
        box-shadow: 0 6px 18px rgba(106,17,203,0.25);
        font-size:15px;
        font-family:'Poppins',sans-serif;
      }
      .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(106,17,203,0.35); }

      .back-link { display:block; width:100%; padding:14px 0; margin-top:12px; text-align:center; border-radius:10px; font-weight:700; text-decoration:none; background:#f0eef9; color:var(--purple-start); border:2px solid #e1dbfa; transition:all 0.2s ease; font-size:15px; }
      .back-link:hover { background:#eae7fb; transform: translateY(-2px); }

      .debug { margin-top:16px; text-align:center; color:#666; font-size:12px; }
      .icon { font-size:48px; text-align:center; margin-bottom:20px; }

      /* Responsive */
      @media (max-width: 992px) {
        .content { padding:0 24px; margin:24px auto; }
        .card { padding:32px; }
        .card-title { font-size:24px; }
        .site-title { font-size:20px; }
      }
      @media (max-width: 768px) {
        .site-header { padding:12px 16px; }
        .site-title { font-size:18px; }
        .welcome-text { display:none; }
        .logout { padding:8px 14px; font-size:13px; }
        .back-btn { padding:6px 12px; font-size:13px; }
        .content { padding:0 16px; margin:20px auto; }
        .card { padding:28px 24px; }
        .card-title { font-size:22px; }
      }
      @media (max-width: 480px) {
        .site-header { padding:10px 12px; }
        .site-title { font-size:16px; }
        .logout { padding:6px 10px; font-size:12px; }
        .content { padding:0 12px; margin:16px auto; }
        .card { padding:24px 20px; }
        .card-title { font-size:20px; margin-bottom:20px; }
      }
    </style>
  </head>
  <body>
    <header class="site-header">
      <div class="header-left">
        <a href="<?= htmlspecialchars($DASHBOARD_URL) ?>" class="back-btn">← Back to Home</a>
        <h1 class="site-title">Print My Grades</h1>
      </div>

      <div class="header-right">
        <div class="welcome-text">Welcome, <?= htmlspecialchars($studentName) ?></div>
        <a href="../../logout.php" class="logout">Logout</a>
      </div>
    </header>

    <div class="content">
      <div class="card">
        <div class="icon"></div>
        <h2 class="card-title">Generate Grade Report</h2>

        <form method="get" action="" target="_blank" aria-label="Generate grade PDF">
          <input type="hidden" name="generate" value="1">
          <div class="form-group">
            <label for="teacher">Select Teacher (filter)</label>
            <select id="teacher" name="teacher">
              <option value="0">All Teachers</option>
              <?php foreach ($teachers as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['fullName']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn"> Generate PDF Report</button>
        </form>

        <a href="view-grades.php" class="back-link">← Back to Grades</a>

        <?php if ($DEBUG): ?>
          <div class="debug">Debug mode ON</div>
        <?php endif; ?>
      </div>
    </div>
  </body>
  </html>
  <?php
  exit;
}


// teacher filter (0 means all)
$teacherFilter = isset($_GET['teacher']) ? (int)$_GET['teacher'] : 0;

// Security: if teacherFilter > 0, verify that teacher is assigned to this student
if ($teacherFilter > 0) {
  $chk = $conn->prepare("SELECT 1 FROM admin_students WHERE admin_id = ? AND student_id = ? LIMIT 1");
  $chk->bind_param('ii', $teacherFilter, $studentId);
  $chk->execute();
  $cres = $chk->get_result();
  if ($cres->num_rows === 0) {
    http_response_code(403);
    echo "Unauthorized teacher selection.";
    exit;
  }
  $chk->close();
}

// Fetch grades for this student, filtered by teacher if provided
if ($teacherFilter > 0) {
  $gstmt = $conn->prepare("
    SELECT subject, grade, date_recorded
    FROM grades
    WHERE studentID = ? AND admin_id = ?
    ORDER BY date_recorded DESC, subject ASC
  ");
  $gstmt->bind_param('ii', $studentId, $teacherFilter);
} else {
  $gstmt = $conn->prepare("
    SELECT subject, grade, date_recorded
    FROM grades
    WHERE studentID = ?
    ORDER BY date_recorded DESC, subject ASC
  ");
  $gstmt->bind_param('i', $studentId);
}

if (!$gstmt) {
  http_response_code(500);
  die('Database error: ' . htmlspecialchars($conn->error));
}

$gstmt->execute();
$gradesRes = $gstmt->get_result();

// prepare teacher name if filtered
$teacherNameForTitle = 'All Teachers';
if ($teacherFilter > 0) {
  $tn = $conn->prepare("SELECT fullName FROM admins WHERE id = ? LIMIT 1");
  $tn->bind_param('i', $teacherFilter);
  $tn->execute();
  $tr = $tn->get_result()->fetch_assoc();
  $teacherNameForTitle = $tr['fullName'] ?? $teacherNameForTitle;
  $tn->close();
}

// Build HTML for PDF
ob_start();
?>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 40px 30px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
    .header { background:#4A148C; color:#fff; padding:18px 22px; display:flex; justify-content:space-between; align-items:center; border-bottom:3px solid #9B59B6; }
    .left { display:flex; align-items:center; gap:14px; }
    .left img { width:90px; height:auto; border-radius:6px; background:#fff; padding:4px; }
    h2 { text-align:center; color:#6A11CB; margin:18px 0 10px; }
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    th,td { border:1px solid #ccc; padding:8px; text-align:left; font-size:11px; }
    th { background:#6A11CB; color:#fff; font-weight:600; }
    tr:nth-child(even){ background:#f9f9f9; }
    .footer { text-align:center; font-size:10px; color:#888; margin-top:18px; }
  </style>
</head>
<body>
  <div class="header">
    <div class="left">
      <?php if ($logoSrc): ?><img src="<?= $logoSrc ?>" alt="EduTrack"><?php endif; ?>
      <div>
        <div style="font-weight:700;font-size:20px;">EduTrack Report</div>
        <div style="font-size:12px;color:#eaeaea;">Grades Record</div>
      </div>
    </div>
    <div style="font-size:12px;">Generated: <?= date('F j, Y, g:i a') ?></div>
  </div>

  <h2><?= htmlspecialchars($studentName) ?> — Grades (<?= htmlspecialchars($teacherNameForTitle) ?>)</h2>

  <table>
    <tr><th>Subject</th><th>Grade</th><th>Date Recorded</th></tr>
    <?php $count = 0; while ($r = $gradesRes->fetch_assoc()): $count++; ?>
      <tr>
        <td><?= htmlspecialchars($r['subject']) ?></td>
        <td><?= htmlspecialchars($r['grade']) ?></td>
        <td><?= htmlspecialchars($r['date_recorded']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>

  <?php if ($count === 0): ?>
    <p style="text-align:center;margin-top:10px;"><em>No grade records matching your selection.</em></p>
  <?php endif; ?>

  <div class="footer">© <?= date('Y') ?> EduTrack </div>
</body>
</html>
<?php
$html = ob_get_clean();
$gstmt->close();

// Render PDF inline using Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

if (ob_get_length()) { ob_end_clean(); }
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="grade_report.pdf"');
echo $dompdf->output();
exit;
