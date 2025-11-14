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

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_student'])) {
  $student_id = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;

  if ($student_id <= 0) {
    $_SESSION['flash_error'] = 'Please select a valid student.';
    header("Location: add-student.php");
    exit();
  }

  // Ensure mapping table exists
  $create_sql = "CREATE TABLE IF NOT EXISTS admin_students (
    admin_id INT NOT NULL,
    student_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id, student_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

  if (!$conn->query($create_sql)) {
    $_SESSION['flash_error'] = 'Failed to ensure mapping table: ' . $conn->error;
    header("Location: add-student.php");
    exit();
  }

  // Insert mapping if not exists
  $stmt = $conn->prepare("INSERT IGNORE INTO admin_students (admin_id, student_id) VALUES (?, ?)");
  if (!$stmt) {
    $_SESSION['flash_error'] = 'Database prepare error: ' . $conn->error;
    header("Location: add-student.php");
    exit();
  }
  $stmt->bind_param('ii', $admin_id, $student_id);
  $ok = $stmt->execute();
  $stmt->close();

  if ($ok) {
    $_SESSION['flash_success'] = 'Student assigned to you successfully.';
    header("Location: view-students.php");
    exit();
  } else {
    $_SESSION['flash_error'] = 'Failed to assign student: ' . $conn->error;
    header("Location: add-student.php");
    exit();
  }
}

// Fetch students for dropdown
$students = [];
$res = $conn->query("SELECT id, fullName, studentNumber FROM students ORDER BY fullName ASC");
if ($res) {
  while ($r = $res->fetch_assoc()) {
    $students[] = $r;
  }
  $res->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assign Student</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Poppins -->
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
      --error: #ef4444;
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

    .welcome-text { color:#fff; font-weight:500; font-size:14px; }
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
    .logout:hover { background:#e6c200; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,0.15); }

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

    .content {
      flex: 1;
      max-width: 800px;
      width: 100%;
      margin: 40px auto;
      padding: 0 40px;
    }

    .content-card {
      background: var(--light-bg);
      border-radius: 16px;
      padding: 36px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .page-title {
      font-size: 26px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0 0 20px 0;
      text-align: center;
    }

    .note {
      background: #faf7ff;
      border: 1px solid #e1d5ff;
      padding: 12px 14px;
      border-radius: 10px;
      margin-bottom: 18px;
      font-size: 14px;
      color: #4b3b86;
    }

    .error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
      padding: 12px 14px;
      border-radius: 10px;
      margin-bottom: 18px;
      font-weight: 600;
      font-size: 14px;
    }

    .success {
      background: #d1fae5;
      color: #065f46;
      border: 1px solid #6ee7b7;
      padding: 12px 14px;
      border-radius: 10px;
      margin-bottom: 18px;
      font-weight: 600;
      font-size: 14px;
    }

    form { margin-top: 8px; }
    .form-group { margin-bottom: 18px; }
    label { display:block; font-weight:600; margin-bottom:8px; color:var(--text-dark); font-size:14px; }

    select {
      width: 100%;
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
      box-shadow: 0 0 0 3px rgba(106,17,203,0.08);
    }

    .actions {
      margin-top: 20px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
      flex: 1;
      min-width: 200px;
      padding: 14px 24px;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: center;
      font-size: 15px;
      font-family: 'Poppins', sans-serif;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-primary {
      color: #fff;
      background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
      box-shadow: 0 6px 18px rgba(106,17,203,0.25);
    }

    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(106,17,203,0.35); }

    .btn-secondary {
      background: #f0eef9;
      color: var(--purple-start);
      border: 2px solid #e1dbfa;
      text-decoration: none;
    }

    .btn-secondary:hover { background: #eae7fb; transform: translateY(-2px); }

    @media (max-width: 992px) {
      .content { padding: 0 24px; margin: 24px auto; }
      .content-card { padding: 28px; }
      .page-title { font-size: 24px; }
    }

    @media (max-width: 768px) {
      .site-header { padding: 12px 16px; }
      .site-title { font-size: 18px; }
      .welcome-text { display: none; }
      .logout { padding: 8px 14px; font-size: 13px; }
      .back-btn { padding: 6px 12px; font-size: 13px; }
      .content { padding: 0 16px; margin: 20px auto; }
      .content-card { padding: 20px; }
      .page-title { font-size: 22px; }
      .actions { flex-direction: column; }
      .btn { width: 100%; min-width: auto; }
    }

    @media (max-width: 480px) {
      .content-card { padding: 16px; }
      .page-title { font-size: 20px; }
      .btn { padding: 12px 14px; font-size: 14px; }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="header-left">
      <a href="../admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
      <h1 class="site-title">Assign Student</h1>
    </div>

    <div class="header-right">
      <div class="welcome-text">Welcome, <?= htmlspecialchars($admin_name) ?></div>
      <a href="../../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div class="content">
    <div class="content-card">
      <h2 class="page-title">Assign a Student to Manage</h2>

      <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
      <?php endif; ?>

      <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="error"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="form-group">
          <label for="student_id">Select a student</label>
          <select name="student_id" id="student_id" required>
            <option value="">-- Select student --</option>
            <?php foreach ($students as $s):
              $label = trim($s['studentNumber'])
                ? $s['fullName'] . ' — ' . $s['studentNumber']
                : $s['fullName'];
            ?>
              <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="actions">
          <button type="submit" name="assign_student" class="btn btn-primary">Assign to Me</button>
          <a href="view-students.php" class="btn btn-secondary">← Back to Students</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
