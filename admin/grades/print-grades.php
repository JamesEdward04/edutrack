<?php
// Debug switch 
$DEBUG = isset($_GET['debug']) ? (int)$_GET['debug'] : 0;
if ($DEBUG) { ini_set('display_errors', 1); error_reporting(E_ALL); }

// Paths & includes 
session_start();
$ROOT = dirname(__DIR__, 2); 
require_once $ROOT . '/vendor/autoload.php';
require_once $ROOT . '/db_connect.php';

use Dompdf\Dompdf;
use Dompdf\Options;

//  Auth 
if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}
$adminID = (int)$_SESSION['adminID'];
$adminName = $_SESSION['adminName'] ?? 'Admin';

// Back to Dashboard URL 
$DASHBOARD_URL = '../admin-dashboard.php';

// Helper: embed logo so dompdf never blocks it
function embed_image_datauri($absPath) {
  if (is_readable($absPath)) {
    $data = file_get_contents($absPath);
    $mime = 'image/' . strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    return 'data:' . $mime . ';base64,' . base64_encode($data);
  }
  return '';
}
$logoPath = $ROOT . '/assets/images/edutrack.jpg';
$logoSrc  = embed_image_datauri($logoPath);

//  Show selection UI if no student chosen 
if (empty($_GET['id'])) {
  // Fetch students assigned to this admin who have grades FROM this admin
  $stmt = $conn->prepare("
    SELECT DISTINCT s.id, s.fullName
    FROM students s
    JOIN grades g ON s.id = g.studentID
    WHERE g.admin_id = ? AND s.has_grades_enabled = 1
    ORDER BY s.fullName
  ");
  $stmt->bind_param('i', $adminID);
  $stmt->execute();
  $res = $stmt->get_result();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Grade Report</title>

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

      .form-group {
        margin-bottom: 24px;
      }

      label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-dark);
        font-size: 14px;
      }

      /* 🔍 Search input for student dropdown */
      .search-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        background: #fff;
        transition: all 0.2s ease;
        margin-bottom: 6px;
      }

      .search-input:focus {
        outline: none;
        border-color: var(--purple-start);
        box-shadow: 0 0 0 3px rgba(106,17,203,0.08);
      }

      .search-note {
        font-size: 12px;
        color: var(--text-gray);
        margin-bottom: 10px;
      }

      select {
        width: 100%;
        padding: 12px 14px;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
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

      .btn {
        width: 100%;
        padding: 14px 0;
        font-weight: 700;
        color: #fff;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
        box-shadow: 0 6px 18px rgba(106,17,203,0.25);
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
      }

      .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(106,17,203,0.35);
      }

      .btn:active {
        transform: translateY(0);
      }

      .back-link {
        display: block;
        width: 100%;
        padding: 14px 0;
        margin-top: 12px;
        text-align: center;
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
        background: #f0eef9;
        color: var(--purple-start);
        border: 2px solid #e1dbfa;
        transition: all 0.2s ease;
        font-size: 15px;
      }

      .back-link:hover {
        background: #eae7fb;
        transform: translateY(-2px);
      }

      .debug {
        margin-top: 16px;
        text-align: center;
        color: #666;
        font-size: 12px;
      }

      .icon {
        font-size: 48px;
        text-align: center;
        margin-bottom: 20px;
      }

      .info-note {
        background: #e0f2fe;
        color: #0369a1;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
        text-align: center;
      }

      /* ---------- Responsive: Tablets ---------- */
      @media (max-width: 992px) {
        .content {
          padding: 0 24px;
          margin: 24px auto;
        }

        .card {
          padding: 32px;
        }

        .card-title {
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

        .card {
          padding: 28px 24px;
        }

        .card-title {
          font-size: 22px;
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

        .card {
          padding: 24px 20px;
        }

        .card-title {
          font-size: 20px;
          margin-bottom: 20px;
        }
      }
    </style>
  </head>
  <body>
    <header class="site-header">
      <div class="header-left">
        <a href="<?= htmlspecialchars($DASHBOARD_URL) ?>" class="back-btn">← Back to Dashboard</a>
        <h1 class="site-title">Print Grade Report</h1>
      </div>

      <div class="header-right">
        <div class="welcome-text">Welcome, <?= htmlspecialchars($adminName) ?></div>
        <a href="../../logout.php" class="logout">Logout</a>
      </div>
    </header>

    <div class="content">
      <div class="card">
        <div class="icon"></div>
        <h2 class="card-title">Generate Grade Report</h2>
        
        <form method="get" action="" target="_blank">
          <!-- 🔍 Search box for student select -->
          <div class="form-group">
            <label for="student_search">Search student</label>
            <input
              type="search"
              id="student_search"
              class="search-input"
              placeholder="Type a name to filter"
              aria-label="Search student by name"
            >
          </div>

          <div class="form-group">
            <label for="student">Select Student</label>
            <select id="student" name="id" required>
              <option value="">-- Choose a student --</option>
              <?php while ($row = $res->fetch_assoc()): ?>
                <option value="<?= (int)$row['id'] ?>">
                  <?= htmlspecialchars($row['fullName']) ?>
                </option>
              <?php endwhile; ?>
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

    <!--  Client-side filter for student dropdown -->
    <script>
      (function () {
        const searchInput = document.getElementById('student_search');
        const select = document.getElementById('student');
        if (!searchInput || !select) return;

        const allOptions = Array.from(select.querySelectorAll('option'));
        const placeholder = allOptions[0];
        const others = allOptions.slice(1);

        searchInput.addEventListener('input', function (e) {
          const q = (e.target.value || '').trim().toLowerCase();

          // reset options
          select.innerHTML = '';
          select.appendChild(placeholder);

          others.forEach(opt => {
            const text = (opt.textContent || '').toLowerCase();
            const match = q === '' || text.indexOf(q) !== -1;
            if (match) {
              select.appendChild(opt);
            }
          });
        });
      })();
    </script>
  </body>
  </html>
  <?php
  $stmt->close();
  exit;
}

//  Generate PDF for selected student
$studentId = (int)$_GET['id'];

// Verify student has grades FROM this specific admin
$nameStmt = $conn->prepare("
  SELECT s.fullName
  FROM students s
  WHERE s.id = ?
    AND EXISTS (
      SELECT 1 FROM grades g 
      WHERE g.studentID = s.id 
      AND g.admin_id = ?
    )
    AND s.has_grades_enabled = 1
  LIMIT 1
");
$nameStmt->bind_param('ii', $studentId, $adminID);
$nameStmt->execute();
$nameStmt->bind_result($studentName);
$found = $nameStmt->fetch();
$nameStmt->close();

if (!$found) {
  http_response_code(404);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body {
        font-family: 'Poppins', sans-serif;
        background: #F8F8FF;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        color: #1E1E2D;
      }
      .error-card {
        background: #fff;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 6px 24px rgba(0,0,0,0.08);
        text-align: center;
        max-width: 480px;
      }
      .error-icon {
        font-size: 64px;
        margin-bottom: 16px;
      }
      h3 {
        color: #6A11CB;
        margin-bottom: 12px;
      }
      p {
        color: #6B6B83;
        margin-bottom: 24px;
      }
      a {
        display: inline-block;
        background: linear-gradient(90deg, #6A11CB, #9B59B6);
        color: #fff;
        padding: 12px 24px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 700;
        transition: all 0.2s ease;
      }
      a:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(106,17,203,0.3);
      }
    </style>
  </head>
  <body>
    <div class="error-card">
      <div class="error-icon"></div>
      <h3>No Grades Found</h3>
      <p>You haven't recorded any grades for this student yet, or the student doesn't exist.</p>
      <a href="print-grades.php">← Go Back</a>
    </div>
  </body>
  </html>
  <?php
  exit;
}

// Fetch ONLY grades that THIS admin recorded
$q = $conn->prepare("
  SELECT g.subject, g.grade, g.date_recorded
  FROM grades g
  WHERE g.admin_id = ? AND g.studentID = ?
  ORDER BY g.subject
");
$q->bind_param('ii', $adminID, $studentId);
$q->execute();
$grades = $q->get_result();

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
    .teacher-info { text-align:center; color:#666; font-size:11px; margin-bottom:12px; font-style:italic; }
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

  <h2><?= htmlspecialchars($studentName) ?> — Grade Report</h2>
  <div class="teacher-info">Grades recorded by: <?= htmlspecialchars($adminName) ?></div>

  <table>
    <tr><th>Subject</th><th>Grade</th><th>Date Recorded</th></tr>
    <?php $count=0; while($r=$grades->fetch_assoc()): $count++; ?>
      <tr>
        <td><?= htmlspecialchars($r['subject']) ?></td>
        <td><strong><?= htmlspecialchars($r['grade']) ?></strong></td>
        <td><?= htmlspecialchars($r['date_recorded']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>

  <?php if ($count === 0): ?>
    <p style="text-align:center;margin-top:10px;"><em>No grade records for this student.</em></p>
  <?php endif; ?>

  <div class="footer">© <?= date('Y') ?> EduTrack — Report generated by <?= htmlspecialchars($adminName) ?></div>
</body>
</html>
<?php
$html = ob_get_clean();
$q->close();

// Render PDF inline
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

if (ob_get_length()) { ob_end_clean(); }
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="grade_report_' . preg_replace('/[^a-z0-9]/i', '_', $studentName) . '.pdf"');
echo $dompdf->output();
exit;
