<?php
session_start();
include '../../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id   = (int)$_SESSION['adminID'];
$admin_name = $_SESSION['adminName'] ?? 'Admin';

// Get gradeID from URL
$gradeID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($gradeID <= 0) {
  die("No grade ID provided.");
}

// Fetch this grade (only if it belongs to this admin) + student name
$stmt = $conn->prepare("
  SELECT g.gradeID, g.subject, g.grade, g.studentID, s.fullName
  FROM grades g
  INNER JOIN students s ON s.id = g.studentID
  WHERE g.gradeID = ? AND g.admin_id = ?
  LIMIT 1
");
$stmt->bind_param('ii', $gradeID, $admin_id);
$stmt->execute();
$res = $stmt->get_result();
$grade = $res->fetch_assoc();
$stmt->close();

if (!$grade) {
  die("Unauthorized access or grade not found.");
}

$error = null;

// Handle form submission
if (isset($_POST['update_grade'])) {
  $subjectRaw = trim($_POST['subject'] ?? '');
  $gradeRaw   = trim($_POST['grade'] ?? '');

  if ($subjectRaw === '' || $gradeRaw === '' || !is_numeric($gradeRaw)) {
    $error = 'Please enter a valid subject and numeric grade.';
  } else {
    $subject = $subjectRaw;
    $gradeVal = (float)$gradeRaw;

    // Update safely
    $upd = $conn->prepare("UPDATE grades SET subject = ?, grade = ? WHERE gradeID = ? AND admin_id = ?");
    $upd->bind_param('sdii', $subject, $gradeVal, $gradeID, $admin_id);
    if ($upd->execute()) {
      $upd->close();
      header("Location: view-grades.php?success=" . urlencode('Grade updated successfully'));
      exit();
    } else {
      $error = 'Database error: ' . $conn->error;
      $upd->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Grade</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --purple1: #6A11CB;
      --purple2: #9B59B6;
      --bg: #f5f6fa;
      --text: #333;
    }
    * { box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      background: var(--bg);
      margin: 0;
      color: var(--text);
    }
    header {
      background: linear-gradient(to right, var(--purple1), var(--purple2));
      color: white;
      padding: 18px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    header h1 { margin: 0; font-size: 22px; }
    .logout { color: #fff; text-decoration: underline; font-weight: 500; }
    .content {
      max-width: 720px;
      background: #fff;
      margin: 40px auto;
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }
    h2 {
      margin: 0 0 16px 0;
      color: var(--purple1);
      text-align: center;
      font-size: 22px;
    }
    .meta {
      background: #faf7ff;
      border: 1px solid #ece1ff;
      padding: 12px 14px;
      border-radius: 12px;
      margin-bottom: 16px;
      font-size: 14px;
    }
    form label {
      display: block;
      font-weight: 600;
      margin-top: 12px;
      margin-bottom: 6px;
    }
    form input[type="text"],
    form input[type="number"] {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
      transition: 0.2s;
    }
    form input:focus {
      outline: none;
      border-color: var(--purple1);
      box-shadow: 0 0 5px rgba(106,17,203,0.25);
    }
    .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 18px;
    }
    .btn-primary, .btn-secondary {
      flex: 1 1 180px;
      padding: 12px;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.25s;
      text-align: center;
    }
    .btn-primary {
      color: #fff;
      background: linear-gradient(to right, var(--purple1), var(--purple2));
      box-shadow: 0 4px 10px rgba(106,17,203,0.25);
    }
    .btn-primary:hover {
      background: linear-gradient(to right, var(--purple2), var(--purple1));
      box-shadow: 0 5px 12px rgba(106,17,203,0.3);
    }
    .btn-secondary {
      background: #f0eef9;
      color: #4b3b86;
      border: 1px solid #e1dbfa;
    }
    .btn-secondary:hover {
      background: #eae7fb;
    }
    .error {
      background: #fdecef;
      color: #a3172a;
      border: 1px solid #f8c7cf;
      padding: 10px 12px;
      border-radius: 8px;
      margin-bottom: 12px;
      font-weight: 600;
    }
    @media (max-width: 768px) {
      .content { margin: 20px; padding: 20px; }
      header { gap: 8px; }
    }
  </style>
</head>
<body>
  <header>
    <h1>Edit Grade</h1>
    <div>
      Welcome, <?= htmlspecialchars($admin_name) ?> |
      <a href="../../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div class="content">
    <h2>Edit Grade Record</h2>

    <div class="meta">
      <strong>Student:</strong> <?= htmlspecialchars($grade['fullName']) ?><br>
      <strong>Grade ID:</strong> <?= (int)$grade['gradeID'] ?>
    </div>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <label>Subject</label>
      <input type="text" name="subject" value="<?= htmlspecialchars($grade['subject']) ?>" required>

      <label>Grade</label>
      <input type="number" step="0.01" name="grade"
             value="<?= htmlspecialchars($grade['grade']) ?>" required>

      <div class="actions">
        <button type="submit" name="update_grade" class="btn-primary">Update Grade</button>
        <a class="btn-secondary" href="view-grades.php">Back to Grades</a>
      </div>
    </form>
  </div>
</body>
</html>
