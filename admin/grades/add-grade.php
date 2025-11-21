<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id   = (int)$_SESSION['adminID'];
$admin_name = $_SESSION['adminName'] ?? 'Admin';

$error = null;

/* preselected student from Manage button (if any) */
$preselected_student = isset($_SESSION['managed_student_id']) ? (int)$_SESSION['managed_student_id'] : 0;

/* Handle POST submission */
if (isset($_POST['add_grade'])) {
  // 1) Read inputs
  $studentID = isset($_POST['studentID']) ? (int)$_POST['studentID'] : 0;
  $subject   = trim($_POST['subject'] ?? '');
  $grade     = $_POST['grade'] ?? '';

  //  Basic validation
  if ($studentID <= 0 || $subject === '' || $grade === '' || !is_numeric($grade)) {
    $error = 'Please complete all fields correctly.';
  } else {
    //  Enforce toggles for this student (and ownership) using admin_students mapping
    $chk = $conn->prepare("
      SELECT s.has_grades_enabled, s.has_attendance_enabled
      FROM students s
      JOIN admin_students a ON s.id = a.student_id
      WHERE s.id = ? AND a.admin_id = ?
      LIMIT 1
    ");
    $chk->bind_param('ii', $studentID, $admin_id);
    $chk->execute();
    $chk->bind_result($hasGrades, $hasAttendance);

    if (!$chk->fetch()) {
      $error = 'Student not found or not assigned to your account.';
    } elseif ((int)$hasGrades !== 1) {
      $error = 'Selected student is not enabled for Grades. Toggle it under Students.';
    } elseif (strcasecmp($subject, 'Attendance') === 0 && (int)$hasAttendance !== 1) {
      $error = 'This student is not enabled for Attendance. Toggle it under Students.';
    }
    $chk->close();

    //  Insert if OK
    if ($error === null) {
      $ins = $conn->prepare("
        INSERT INTO grades (admin_id, studentID, subject, grade)
        VALUES (?, ?, ?, ?)
      ");
      // 'iisd' => int, int, string, double
      $ins->bind_param('iisd', $admin_id, $studentID, $subject, $grade);
      if ($ins->execute()) {
        $ins->close();
        header("Location: view-grades.php?success=" . urlencode('Grade added successfully'));
        exit();
      } else {
        $error = 'Database error: ' . $conn->error;
      }
      $ins->close();
    }
  }
}

/*  Fetch students for dropdown
   Only show Grades-enabled students that are assigned to this admin  */
$stu = $conn->prepare("
  SELECT s.id, s.fullName, s.studentNumber
  FROM admin_students a
  JOIN students s ON a.student_id = s.id
  WHERE a.admin_id = ? AND s.has_grades_enabled = 1
  ORDER BY s.fullName ASC
");
$stu->bind_param('i', $admin_id);
$stu->execute();
$students = $stu->get_result();
$hasOptions = ($students && $students->num_rows > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Grade</title>

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

    .content-card {
      background: var(--light-bg);
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .page-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0 0 24px 0;
      text-align: center;
    }

    /* ---------- Alerts ---------- */
    .note {
      background: #faf7ff;
      border: 1px solid #e1d5ff;
      padding: 14px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
      color: #4b3b86;
      line-height: 1.6;
    }

    .note strong {
      font-weight: 700;
    }

    .note a {
      color: var(--purple-start);
      font-weight: 700;
      text-decoration: underline;
    }

    .error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
      padding: 14px 18px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* ---------- Form ---------- */
    form {
      margin-top: 24px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--text-dark);
      font-size: 14px;
    }

    select,
    input[type="text"],
    input[type="number"] {
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

    select:focus,
    input:focus {
      outline: none;
      border-color: var(--purple-start);
      box-shadow: 0 0 0 3px rgba(106,17,203,0.1);
    }

    select:disabled,
    input:disabled {
      background: #f5f5f5;
      cursor: not-allowed;
      opacity: 0.6;
    }

    /* search input style (same vibe as add-student) */
    .search-input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
      font-size: 14px;
      font-family: 'Poppins', sans-serif;
      margin-bottom: 8px;
      background: #fff;
      transition: all 0.2s ease;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--purple-start);
      box-shadow: 0 0 0 3px rgba(106,17,203,0.08);
    }

    /* ---------- Action Buttons ---------- */
    .actions {
      margin-top: 28px;
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

    .btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(106,17,203,0.35);
    }

    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    .btn-secondary {
      background: #f0eef9;
      color: var(--purple-start);
      border: 2px solid #e1dbfa;
    }

    .btn-secondary:hover {
      background: #eae7fb;
      transform: translateY(-2px);
    }

    /* ---------- Responsive: Tablets ---------- */
    @media (max-width: 992px) {
      .content {
        padding: 0 24px;
        margin: 24px auto;
      }

      .content-card {
        padding: 32px;
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
        padding: 24px;
      }

      .page-title {
        font-size: 22px;
      }

      .actions {
        flex-direction: column;
      }

      .btn {
        width: 100%;
        min-width: auto;
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
        padding: 20px;
      }

      .page-title {
        font-size: 20px;
      }

      .btn {
        padding: 12px 16px;
        font-size: 14px;
      }
    }
  </style>
</head>

<body>
  <header class="site-header">
    <div class="header-left">
      <a href="../admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
      <h1 class="site-title">Add Grade</h1>
    </div>

    <div class="header-right">
      <div class="welcome-text">Welcome, <?= htmlspecialchars($admin_name) ?></div>
      <a href="../../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div class="content">
    <div class="content-card">
      <h2 class="page-title">Add New Grade</h2>

      <?php if (isset($_GET['error'])): ?>
        <div class="error">
           <?= htmlspecialchars($_GET['error']) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="error">
           <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if (!$hasOptions): ?>
        <div class="note">
          No students assigned to you are currently <strong>enabled for Grades</strong>. Go to
          <a href="../students/view-students.php">Students</a>
          and enable the Grades toggle for a student to proceed.
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <!--  Search student (fast filter, like add-student) -->
        <div class="form-group">
          <label for="student_search">Search student</label>
          <input
            type="search"
            id="student_search"
            class="search-input"
            placeholder="Search by name or student number"
            aria-label="Search student by name or number"
            <?= $hasOptions ? '' : 'disabled' ?>
          >
          <div class="note">Type to filter the student list below. </div>
        </div>

        <div class="form-group">
          <label for="studentID">Student</label>
          <select name="studentID" id="studentID" required <?= $hasOptions ? '' : 'disabled' ?>>
            <option value=""><?= $hasOptions ? 'Select Student' : 'No eligible students' ?></option>
            <?php if ($hasOptions): ?>
              <?php while ($row = $students->fetch_assoc()):
                $sel = ($preselected_student > 0 && $preselected_student === (int)$row['id']) ? 'selected' : '';
                $fullName = $row['fullName'];
                $studNum  = $row['studentNumber'];
                $label    = $fullName . (trim($studNum) ? ' — ' . $studNum : '');
              ?>
                <option
                  value="<?= (int)$row['id'] ?>"
                  <?= $sel ?>
                  data-name="<?= htmlspecialchars(strtolower($fullName)) ?>"
                  data-number="<?= htmlspecialchars(strtolower($studNum)) ?>"
                >
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endwhile; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="subject">Subject</label>
          <input type="text" name="subject" id="subject" required placeholder="e.g., Mathematics" <?= $hasOptions ? '' : 'disabled' ?>>
        </div>

        <div class="form-group">
          <label for="grade">Grade</label>
          <input type="number" step="0.01" name="grade" id="grade" required placeholder="e.g., 92.5" <?= $hasOptions ? '' : 'disabled' ?>>
        </div>

        <div class="actions">
          <button type="submit" name="add_grade" class="btn btn-primary" <?= $hasOptions ? '' : 'disabled' ?>>
             Add Grade
          </button>
          <a href="view-grades.php" class="btn btn-secondary">
            ← Back to Grades
          </a>
        </div>
      </form>
    </div>
  </div>

  <!--  Fast search: hide/show options instead of rebuilding -->
  <script>
    (function () {
      const searchInput = document.getElementById('student_search');
      const select = document.getElementById('studentID');
      if (!searchInput || !select) return;

      const options = Array.from(select.options); // includes placeholder at index 0

      searchInput.addEventListener('input', function (e) {
        const q = (e.target.value || '').trim().toLowerCase();

        options.forEach((opt, index) => {
          // Keep placeholder always visible
          if (index === 0) {
            opt.hidden = false;
            return;
          }

          const name = (opt.getAttribute('data-name') || '').toLowerCase();
          const num  = (opt.getAttribute('data-number') || '').toLowerCase();
          const text = (opt.textContent || '').toLowerCase();

          const match =
            q === '' ||
            name.indexOf(q) !== -1 ||
            num.indexOf(q) !== -1 ||
            text.indexOf(q) !== -1;

          opt.hidden = !match;
        });

        // Reset selection if selected option is hidden after filtering
        const selected = select.selectedOptions[0];
        if (selected && selected.hidden) {
          select.value = '';
        }
      });
    })();
  </script>
</body>
</html>
<?php
$stu->close();
?>
