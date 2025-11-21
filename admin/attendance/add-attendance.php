<?php
session_start();
include '../../db_connect.php';

// check admin login
if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}

$admin_id   = (int)$_SESSION['adminID'];
$admin_name = $_SESSION['adminName'] ?? 'Admin';

$error = null;

// preselected student
$preselected_student = isset($_SESSION['managed_student_id']) ? (int)$_SESSION['managed_student_id'] : 0;

// handle submit
if (isset($_POST['add_attendance'])) {
  // get inputs
  $studentID = isset($_POST['studentID']) ? (int)$_POST['studentID'] : 0;
  $date      = trim($_POST['date'] ?? '');
  $status    = trim($_POST['status'] ?? '');
  $remarks   = trim($_POST['remarks'] ?? '');

  // allowed status values
  $allowed_status = ['Present', 'Absent', 'Late'];

  // check date format
  $date_ok = false;
  if ($date !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    $date_ok = $dt && $dt->format('Y-m-d') === $date;
  }

  // basic validation
  if ($studentID <= 0 || !$date_ok || !in_array($status, $allowed_status, true)) {
    $error = 'Please select a valid student, date, and status.';
  } else {
    // check if student is assigned to this admin and has attendance enabled
    $chk = $conn->prepare("
      SELECT s.has_attendance_enabled
      FROM students s
      JOIN admin_students a ON s.id = a.student_id
      WHERE s.id = ? AND a.admin_id = ?
      LIMIT 1
    ");
    if (!$chk) {
      $error = 'Database prepare error: ' . $conn->error;
    } else {
      $chk->bind_param('ii', $studentID, $admin_id);
      $chk->execute();
      $chk->bind_result($hasAttendance);

      if (!$chk->fetch()) {
        $error = 'Student not found or not assigned to your account.';
      } elseif ((int)$hasAttendance !== 1) {
        $error = 'Selected student is not enabled for Attendance. Toggle it under Students.';
      }
      $chk->close();
    }

    // insert if ok
    if ($error === null) {
      $ins = $conn->prepare("
        INSERT INTO attendance (admin_id, studentID, `date`, `status`, remarks)
        VALUES (?, ?, ?, ?, ?)
      ");
      if (!$ins) {
        $error = 'Database prepare error: ' . $conn->error;
      } else {
        $ins->bind_param('iisss', $admin_id, $studentID, $date, $status, $remarks);
        if ($ins->execute()) {
          $ins->close();
          header("Location: view-attendance.php?success=" . urlencode('Attendance added successfully'));
          exit();
        } else {
          $error = 'Database error: ' . $conn->error;
        }
        $ins->close();
      }
    }
  }
}

// get students for dropdown (only attendance-enabled + assigned to this admin)
$stu = $conn->prepare("
  SELECT s.id, s.fullName, s.studentNumber
  FROM admin_students a
  JOIN students s ON a.student_id = s.id
  WHERE a.admin_id = ? AND s.has_attendance_enabled = 1
  ORDER BY s.fullName ASC
");
$stu->bind_param('i', $admin_id);
$stu->execute();
$students = $stu->get_result();
$hasOptions = ($students && $students->num_rows > 0);

// keep date value (default today)
$currentDateValue = $_POST['date'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

    /* header */
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

    /* main layout */
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

    /* form */
    form { margin-top: 8px; }
    .form-group { margin-bottom: 18px; }
    label { display:block; font-weight:600; margin-bottom:8px; color:var(--text-dark); font-size:14px; }

    /* student search */
    .search-input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
      font-size: 14px;
      font-family: 'Poppins', sans-serif;
      margin-bottom: 6px;
      background: #fff;
      transition: all 0.2s ease;
    }
    .search-input:focus {
      outline: none;
      border-color: var(--purple-start);
      box-shadow: 0 0 0 3px rgba(106,17,203,0.08);
    }
    .search-note {
      font-size: 12px;
      color: var(--text-gray);
      margin-bottom: 8px;
    }

    select,
    input[type="text"] {
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

    /* calendar */
    .calendar-wrapper {
      border-radius: 14px;
      border: 1px solid #e4e4ef;
      padding: 16px;
      background: #faf9ff;
    }
    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }
    .calendar-month {
      font-weight: 600;
      font-size: 15px;
    }
    .cal-nav-btn {
      border-radius: 999px;
      border: 1px solid #ddd;
      background: #fff;
      padding: 4px 10px;
      font-size: 12px;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
    }
    .cal-nav-btn:hover {
      background: #f0eef9;
      border-color: var(--purple-start);
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 4px;
      font-size: 12px;
    }
    .cal-day-header {
      text-align: center;
      font-weight: 600;
      color: var(--text-gray);
      padding: 4px 0;
    }
    .cal-day {
      height: 34px;
      border-radius: 8px;
      text-align: center;
      padding-top: 6px;
      cursor: pointer;
      font-size: 13px;
      border: 1px solid transparent;
      background: #fff;
    }
    .cal-day.other-month {
      opacity: 0.25;
      cursor: default;
    }
    .cal-day:hover:not(.other-month) {
      border-color: var(--purple-start);
      background: #f4ecff;
    }
    .cal-day.selected {
      background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
      color: #fff;
      border-color: transparent;
      font-weight: 700;
    }
    .cal-day.today:not(.selected) {
      border-color: #9B59B6;
      background: #f7ebff;
      font-weight: 600;
    }

    .selected-date-label {
      margin-top: 8px;
      font-size: 12px;
      color: var(--text-gray);
    }
    .selected-date-label strong {
      color: var(--text-dark);
    }

    /* buttons */
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
      text-decoration: none;
    }
    .btn-secondary:hover {
      background: #eae7fb;
      transform: translateY(-2px);
    }

    /* responsive */
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
    @media (max-width: 768px) {
      .site-header { padding: 12px 16px; }
      .site-title { font-size: 18px; }
      .welcome-text { display: none; }
      .logout { padding: 8px 14px; font-size: 13px; }
      .back-btn { padding: 6px 12px; font-size: 13px; }
      .content { padding: 0 16px; margin: 20px auto; }
      .content-card { padding: 24px; }
      .page-title { font-size: 22px; }
      .actions { flex-direction: column; }
      .btn { width: 100%; min-width: auto; }
    }
    @media (max-width: 480px) {
      .site-header { padding: 10px 12px; }
      .site-title { font-size: 16px; }
      .logout { padding: 6px 10px; font-size: 12px; }
      .content { padding: 0 12px; margin: 16px auto; }
      .content-card { padding: 20px; }
      .page-title { font-size: 20px; }
      .btn { padding: 12px 16px; font-size: 14px; }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="header-left">
      <a href="../admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
      <h1 class="site-title">Add Attendance</h1>
    </div>

    <div class="header-right">
      <div class="welcome-text">Welcome, <?= htmlspecialchars($admin_name) ?></div>
      <a href="../../logout.php" class="logout">Logout</a>
    </div>
  </header>

  <div class="content">
    <div class="content-card">
      <h2 class="page-title">Add Attendance Record</h2>

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
        <div class="error" style="background:#fef3c7;border-color:#facc15;color:#92400e;">
          No students assigned to you are enabled for Attendance. Enable Attendance under <strong>Students</strong> first.
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <!-- student search + select -->
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
        </div>

        <div class="form-group">
          <label for="studentID">Student</label>
          <select name="studentID" id="studentID" required <?= $hasOptions ? '' : 'disabled' ?>>
            <option value=""><?= $hasOptions ? 'Select Student' : 'No eligible students' ?></option>
            <?php if ($hasOptions): ?>
              <?php while ($row = $students->fetch_assoc()):
                $sel = ($preselected_student > 0 && $preselected_student === (int)$row['id']) ? 'selected' : '';
                $label = $row['fullName'] . (trim($row['studentNumber']) ? ' — ' . $row['studentNumber'] : '');
              ?>
                <option
                  value="<?= (int)$row['id'] ?>"
                  <?= $sel ?>
                  data-name="<?= htmlspecialchars(strtolower($row['fullName'])) ?>"
                  data-number="<?= htmlspecialchars(strtolower($row['studentNumber'])) ?>"
                >
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endwhile; ?>
            <?php endif; ?>
          </select>
        </div>

        <!-- full inline calendar date picker -->
        <div class="form-group">
          <label for="date">Date</label>

          <!-- hidden real date input (for PHP) -->
          <input
            type="hidden"
            name="date"
            id="date"
            required
            value="<?= htmlspecialchars($currentDateValue) ?>"
          >

          <div class="calendar-wrapper" id="attendanceCalendar"
               data-initial-date="<?= htmlspecialchars($currentDateValue) ?>">
            <div class="calendar-header">
              <button type="button" class="cal-nav-btn" data-cal-nav="prev"> Prev</button>
              <div class="calendar-month" id="calMonthLabel">Month YYYY</div>
              <button type="button" class="cal-nav-btn" data-cal-nav="next">Next </button>
            </div>
            <div class="calendar-grid" id="calGrid">
              <!-- days render here -->
            </div>
            <div class="selected-date-label" id="selectedDateLabel">
              Selected date: <strong></strong>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="status">Status</label>
          <select name="status" id="status" required <?= $hasOptions ? '' : 'disabled' ?>>
            <option value="">-- Select status --</option>
            <option value="Present" <?= (isset($_POST['status']) && $_POST['status'] === 'Present') ? 'selected' : '' ?>>Present</option>
            <option value="Absent" <?= (isset($_POST['status']) && $_POST['status'] === 'Absent') ? 'selected' : '' ?>>Absent</option>
            <option value="Late" <?= (isset($_POST['status']) && $_POST['status'] === 'Late') ? 'selected' : '' ?>>Late</option>
          </select>
        </div>

        <div class="form-group">
          <label for="remarks">Remarks</label>
          <input
            type="text"
            name="remarks"
            id="remarks"
            placeholder="Optional"
            value="<?= htmlspecialchars($_POST['remarks'] ?? '') ?>"
            <?= $hasOptions ? '' : 'disabled' ?>
          >
        </div>

        <div class="actions">
          <button type="submit" name="add_attendance" class="btn btn-primary" <?= $hasOptions ? '' : 'disabled' ?>>
             Add Attendance
          </button>
          <a href="view-attendance.php" class="btn btn-secondary">
            ← Back to Attendance
          </a>
        </div>
      </form>
    </div>
  </div>

  <script>
    // simple student search
    (function () {
      const searchInput = document.getElementById('student_search');
      const select = document.getElementById('studentID');
      if (!searchInput || !select) return;

      const allOptions = Array.from(select.querySelectorAll('option'));
      const placeholder = allOptions[0];
      const others = allOptions.slice(1);

      searchInput.addEventListener('input', function (e) {
        const q = (e.target.value || '').trim().toLowerCase();

        select.innerHTML = '';
        select.appendChild(placeholder);

        others.forEach(opt => {
          const name = (opt.getAttribute('data-name') || '').toLowerCase();
          const num  = (opt.getAttribute('data-number') || '').toLowerCase();
          const text = (opt.textContent || '').toLowerCase();

          const match =
            q === '' ||
            name.indexOf(q) !== -1 ||
            num.indexOf(q) !== -1 ||
            text.indexOf(q) !== -1;

          if (match) {
            select.appendChild(opt);
          }
        });
      });
    })();

    // inline calendar for date
    (function () {
      const calendarEl = document.getElementById('attendanceCalendar');
      const dateInput = document.getElementById('date');
      const monthLabel = document.getElementById('calMonthLabel');
      const grid = document.getElementById('calGrid');
      const selectedLabel = document.getElementById('selectedDateLabel').querySelector('strong');

      if (!calendarEl || !dateInput || !monthLabel || !grid || !selectedLabel) return;

      const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

      // parse initial date (from PHP)
      const initial = calendarEl.getAttribute('data-initial-date') || '';
      let currentDate = initial ? new Date(initial) : new Date();
      if (isNaN(currentDate.getTime())) currentDate = new Date();

      let selectedDate = initial ? new Date(initial) : new Date();
      if (isNaN(selectedDate.getTime())) selectedDate = new Date();

      function formatYmd(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
      }

      function formatPretty(d) {
        const opts = { year: 'numeric', month: 'long', day: 'numeric' };
        return d.toLocaleDateString(undefined, opts);
      }

      function sameDay(a, b) {
        return a.getFullYear() === b.getFullYear() &&
               a.getMonth() === b.getMonth() &&
               a.getDate() === b.getDate();
      }

      function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth(); // 0-based
        const firstOfMonth = new Date(year, month, 1);
        const startDay = firstOfMonth.getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const today = new Date();
        const todayY = today.getFullYear();
        const todayM = today.getMonth();
        const todayD = today.getDate();

        monthLabel.textContent = currentDate.toLocaleDateString(undefined, {
          year: 'numeric',
          month: 'long'
        });

        grid.innerHTML = '';

        // weekday headers
        dayNames.forEach(d => {
          const h = document.createElement('div');
          h.className = 'cal-day-header';
          h.textContent = d;
          grid.appendChild(h);
        });

        // empty slots before 1st
        for (let i = 0; i < startDay; i++) {
          const empty = document.createElement('div');
          empty.className = 'cal-day other-month';
          grid.appendChild(empty);
        }

        // days of this month
        for (let d = 1; d <= daysInMonth; d++) {
          const cell = document.createElement('div');
          cell.className = 'cal-day';
          cell.textContent = d;

          const thisDate = new Date(year, month, d);

          // today highlight
          if (year === todayY && month === todayM && d === todayD) {
            cell.classList.add('today');
          }

          // selected highlight
          if (selectedDate && sameDay(thisDate, selectedDate)) {
            cell.classList.add('selected');
          }

          cell.addEventListener('click', function () {
            selectedDate = thisDate;
            dateInput.value = formatYmd(selectedDate);
            selectedLabel.textContent = formatPretty(selectedDate);
            renderCalendar(); // re-render to update selected state
          });

          grid.appendChild(cell);
        }
      }

      // nav buttons
      calendarEl.querySelectorAll('[data-cal-nav]').forEach(btn => {
        btn.addEventListener('click', function () {
          const dir = this.getAttribute('data-cal-nav');
          if (dir === 'prev') {
            currentDate.setMonth(currentDate.getMonth() - 1);
          } else if (dir === 'next') {
            currentDate.setMonth(currentDate.getMonth() + 1);
          }
          renderCalendar();
        });
      });

      // initial label + hidden input
      if (selectedDate) {
        dateInput.value = formatYmd(selectedDate);
        selectedLabel.textContent = formatPretty(selectedDate);
      }

      renderCalendar();
    })();
  </script>
</body>
</html>
<?php
$stu->close();
?>
