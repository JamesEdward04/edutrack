<?php
session_start();

require_once __DIR__ . '/../db_connect.php';

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

$studentId = (int)$student['id'];
$studentName = $student['fullName'];

// Helper: list columns present in a table
function table_columns($conn, $table) {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cols[] = $r['Field'];
        }
    }
    return $cols;
}

// Detect which admin columns exist
$adminCols = table_columns($conn, 'admins');
// required fields we'll always request 
$colsToSelect = [];
if (in_array('id', $adminCols, true)) $colsToSelect[] = 'a.id';
if (in_array('fullName', $adminCols, true)) $colsToSelect[] = 'a.fullName';


$optional = ['email','phoneNumber','department','office_hours'];
$presentOptional = [];
foreach ($optional as $c) {
    if (in_array($c, $adminCols, true)) {
        $colsToSelect[] = "a.`{$c}`";
        $presentOptional[] = $c;
    }
}

// If fullName or id missing something is wrong — gracefully handle
if (empty($colsToSelect) || !in_array('a.id', $colsToSelect, true) || !in_array('a.fullName', $colsToSelect, true)) {
    // fallback
    $colsToSelect = ['a.id', 'a.fullName'];
}

// Build SELECT string
$selectStr = implode(', ', $colsToSelect);

// Fetch admins/teachers assigned to this student (only request columns that exist)
$sql = "
  SELECT DISTINCT {$selectStr}
  FROM admins a
  INNER JOIN admin_students ast ON ast.admin_id = a.id
  WHERE ast.student_id = ?
  ORDER BY a.fullName ASC
";

$q = $conn->prepare($sql);
if (!$q) {
    die('Database prepare error: ' . htmlspecialchars($conn->error));
}
$q->bind_param('i', $studentId);
$q->execute();
$teachersRes = $q->get_result();
$teachers = $teachersRes->fetch_all(MYSQLI_ASSOC);
$q->close();

// Helper to safely read a column if it exists in the returned row
function safe_col($row, $col) {
    return isset($row[$col]) && $row[$col] !== null && $row[$col] !== '' ? $row[$col] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contact Teachers — <?= htmlspecialchars($studentName) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --purple-start: #6A11CB;
      --purple-end: #9B59B6;
      --gold: #FFD700;
      --light-bg: #FFFFFF;
      --light-gray: #F8F8FF;
      --text-dark: #1E1E2D;
      --text-gray: #6B6B83;
      --card-shadow: 0 8px 24px rgba(16,24,40,0.04);
    }

    *{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%}
    body{
      font-family: 'Poppins', sans-serif;
      background:var(--light-gray);
      color:var(--text-dark);
      display:flex;
      flex-direction:column;
      min-height:100vh;
    }

    .site-header{
      background: linear-gradient(90deg,var(--purple-start),var(--purple-end));
      color:#fff;
      padding:16px 24px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      box-shadow:0 2px 12px rgba(0,0,0,0.15);
      position:sticky;
      top:0;
      z-index:1200;
    }
    .header-left{display:flex;align-items:center;gap:16px}
    .site-title{font-size:22px;font-weight:700;color:#fff;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px}
    .header-right{display:flex;align-items:center;gap:16px}
    .welcome-text{color:#fff;font-weight:500;font-size:14px}
    .logout{
      background:var(--gold);
      color:var(--purple-start);
      padding:10px 20px;
      border-radius:8px;
      text-decoration:none;
      font-weight:700;
      transition:all .2s ease;
      box-shadow:0 4px 10px rgba(0,0,0,0.1);
      white-space:nowrap;
      font-size:14px;
    }
    .logout:hover{background:#e6c200;transform:translateY(-2px)}

    .back-btn{
      background:rgba(255,255,255,0.2);
      color:#fff;
      padding:8px 16px;
      border-radius:8px;
      text-decoration:none;
      font-weight:600;
      transition:all .2s ease;
      display:inline-flex;
      align-items:center;
      gap:6px;
      font-size:14px;
    }
    .back-btn:hover{background:rgba(255,255,255,0.3);transform:translateX(-2px)}

    .content{flex:1;max-width:1200px;width:100%;margin:40px auto;padding:0 40px}
    .content-card{background:var(--light-bg);border-radius:16px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,0.06)}

    .page-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:18px}
    .page-title{font-size:26px;font-weight:700;color:var(--text-dark);margin:0}
    .header-actions{display:flex;gap:12px;align-items:center}

    .search-wrap{display:flex;gap:12px;align-items:center}
    .search-input{
      padding:10px 12px;border-radius:10px;border:2px solid #e8e8ef;font-size:14px;min-width:240px;font-family:'Poppins',sans-serif;
    }

    .grid{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap:18px;
      margin-top:8px;
    }

    .card-teacher{
      background:var(--light-bg);
      border-radius:12px;
      padding:18px;
      box-shadow: var(--card-shadow);
      border:1px solid rgba(15,15,20,0.02);
      display:flex;
      gap:14px;
      align-items:flex-start;
    }

    .avatar{
      width:64px;height:64px;border-radius:12px;flex-shrink:0;
      display:flex;align-items:center;justify-content:center;font-weight:700;background:linear-gradient(180deg,#f3e8ff,#e9d6ff);color:var(--purple-start);
      font-size:22px;
    }

    .teacher-body{flex:1;min-width:0}
    .teacher-name{font-weight:700;font-size:16px;color:var(--text-dark);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .teacher-meta{font-size:13px;color:var(--text-gray);margin-bottom:8px}
    .contact-list{display:flex;flex-direction:column;gap:6px}
    .contact-item{font-size:14px;color:var(--text-dark);word-break:break-word}
    .contact-item a{color:var(--purple-start);text-decoration:none;font-weight:600}
    .contact-item .muted{color:var(--text-gray);font-weight:600;font-size:13px}

    .empty{padding:40px;text-align:center;color:var(--text-gray);font-size:15px}

    @media (max-width: 992px){
      .grid{grid-template-columns:repeat(2,1fr)}
      .content{padding:0 24px;margin:24px auto}
      .content-card{padding:22px}
      .page-title{font-size:22px}
      .site-title{font-size:20px}
    }

    @media (max-width:768px){
      .site-header{padding:12px 16px}
      .site-title{font-size:18px}
      .welcome-text{display:none}
      .logout{padding:8px 14px;font-size:13px}
      .back-btn{padding:6px 12px;font-size:13px}
      .content{padding:0 16px;margin:20px auto}
      .content-card{padding:18px}
      .grid{grid-template-columns:1fr}
      .search-input{min-width:100%}
    }

    @media (max-width:480px){
      .site-header{padding:10px 12px}
      .site-title{font-size:16px}
      .logout{padding:6px 10px;font-size:12px}
      .content{padding:0 12px;margin:16px auto}
      .content-card{padding:14px}
      .page-title{font-size:20px}
      .avatar{width:56px;height:56px;font-size:20px}
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="header-left">
      <a href="student-dashboard.php" class="back-btn">← Back</a>
      <h1 class="site-title">Contact Teachers</h1>
    </div>

    <div class="header-right">
      <div class="welcome-text">Welcome, <?= htmlspecialchars($studentName) ?></div>
      <a href="../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <main class="content">
    <div class="content-card">
      <div class="page-header">
        <h2 class="page-title">Teachers Assigned to You</h2>
        <div class="header-actions">
          <div class="search-wrap">
            <input id="searchInput" class="search-input" type="search" placeholder="Search by name or email" aria-label="Search teachers">
          </div>
        </div>
      </div>

      <?php if (empty($teachers)): ?>
        <div class="empty">
          <div class="empty-title">No teachers assigned</div>
          <p>It looks like you don't have any teachers assigned to you yet.</p>
        </div>
      <?php else: ?>
        <div id="grid" class="grid" aria-live="polite">
          <?php foreach ($teachers as $t):
              $initial = strtoupper(substr(trim(safe_col($t,'fullName') ?? 'T'), 0, 1));
              $email = safe_col($t,'email');
              $phoneNumber = safe_col($t,'phoneNumber');
              $department = safe_col($t,'department');
              $office_hours = safe_col($t,'office_hours');
          ?>
            <article class="card-teacher" data-name="<?= htmlspecialchars(strtolower($t['fullName'] ?? '')) ?>" data-email="<?= htmlspecialchars(strtolower($email ?? '')) ?>">
              <div class="avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
              <div class="teacher-body">
                <div class="teacher-name"><?= htmlspecialchars($t['fullName'] ?? '—') ?></div>
                <?php if ($department): ?>
                  <div class="teacher-meta"><?= htmlspecialchars($department) ?></div>
                <?php endif; ?>

                <div class="contact-list">
                  <?php if ($email): ?>
                    <div class="contact-item"><span class="muted">Email:</span> <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></div>
                  <?php else: ?>
                    <div class="contact-item"><span class="muted">Email:</span> <span class="text-muted">N/A</span></div>
                  <?php endif; ?>

                  <?php if ($phoneNumber): ?>
                    <div class="contact-item"><span class="muted">Phone:</span> <a href="tel:<?= htmlspecialchars($phoneNumber) ?>"><?= htmlspecialchars($phoneNumber) ?></a></div>
                  <?php else: ?>
                    <div class="contact-item"><span class="muted">Phone:</span> <span class="text-muted">N/A</span></div>
                  <?php endif; ?>

                  <?php if ($office_hours): ?>
                    <div class="contact-item"><span class="muted">Office Hours:</span> <?= htmlspecialchars($office_hours) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    (function(){
      const input = document.getElementById('searchInput');
      const grid = document.getElementById('grid');
      if (!input || !grid) return;

      input.addEventListener('input', function(e){
        const q = (e.target.value || '').trim().toLowerCase();
        const cards = grid.querySelectorAll('.card-teacher');
        cards.forEach(card => {
          const name = card.getAttribute('data-name') || '';
          const email = card.getAttribute('data-email') || '';
          const match = q === '' || name.indexOf(q) !== -1 || email.indexOf(q) !== -1;
          card.style.display = match ? '' : 'none';
        });
      });
    })();
  </script>
</body>
</html>
<?php
$conn->close();
?>
