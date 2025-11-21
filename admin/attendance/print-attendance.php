<?php
// ===== Debug switch (use ?debug=1 while testing) =====
$DEBUG = isset($_GET['debug']) ? (int)$_GET['debug'] : 0;
if ($DEBUG) { ini_set('display_errors', 1); error_reporting(E_ALL); }

session_start();
$ROOT = dirname(__DIR__, 2); // from admin/print to project root
require_once $ROOT . '/vendor/autoload.php';
require_once $ROOT . '/db_connect.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ===== Auth =====
if (!isset($_SESSION['adminID'])) {
  header("Location: ../admin-login.html");
  exit();
}
$adminID = (int)$_SESSION['adminID'];
$adminName = $_SESSION['adminName'] ?? 'Admin';

// support managed student preselect
$managed_student = isset($_SESSION['managed_student_id']) ? (int)$_SESSION['managed_student_id'] : 0;

// ===== Back to Dashboard URL  =====
$DASHBOARD_URL = '../admin-dashboard.php';

// ===== Helper: embed logo =====
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

// ===== Inputs / defaults =====
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');

// If managed student exists and no id passed, keep UI selection blank but allow preselect display
if ($studentId <= 0 && $managed_student > 0) {
  $studentId = 0;
}

// Simple date validator
$validDate = function($d) {
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  return $dt && $dt->format('Y-m-d') === $d;
};
if (!$validDate($date_from) || !$validDate($date_to) || $date_from > $date_to) {
  $date_from = date('Y-m-01');
  $date_to   = date('Y-m-d');
}

/* Show selection UI if no student chosen via GET id (match print-grades design) */
if ($studentId <= 0 && empty($_GET['id'])) {
  // Fetch students assigned to this admin and enabled for attendance
  $stmt = $conn->prepare("
    SELECT s.id, s.fullName
    FROM admin_students a
    JOIN students s ON a.student_id = s.id
    WHERE a.admin_id = ? AND s.has_attendance_enabled = 1
    ORDER BY s.fullName
  ");
  $stmt->bind_param('i', $adminID);
  $stmt->execute();
  $res = $stmt->get_result();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Select Student – Attendance Report</title>

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

      .form-group { margin-bottom: 20px; }
      label {
        display:block; margin-bottom:8px; font-weight:600; color:var(--text-dark); font-size:14px;
      }

      /* search for student select */
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
        width:100%;
        padding:12px 14px;
        border-radius:10px;
        border:2px solid #e0e0e0;
        font-size:15px;
        background:#fff;
      }

      select:focus {
        outline:none; border-color:var(--purple-start); box-shadow:0 0 0 3px rgba(106,17,203,0.08);
      }

      /* Inline date range calendar */
      .calendar-wrapper {
        border-radius: 14px;
        border: 1px solid #e4e4ef;
        padding: 16px;
        background: #faf9ff;
        margin-top: 4px;
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
        opacity: 0.22;
        cursor: default;
      }
      .cal-day:hover:not(.other-month) {
        border-color: var(--purple-start);
        background: #f4ecff;
      }
      .cal-day.today:not(.selected-start):not(.selected-end):not(.in-range) {
        border-color: #9B59B6;
        background: #f7ebff;
        font-weight: 600;
      }
      .cal-day.selected-start,
      .cal-day.selected-end {
        background: linear-gradient(90deg, var(--purple-start), var(--purple-end));
        color: #fff;
        border-color: transparent;
        font-weight: 700;
      }
      .cal-day.in-range {
        background: #e6ddff;
        color: var(--text-dark);
      }

      .range-labels {
        margin-top: 8px;
        font-size: 12px;
        color: var(--text-gray);
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      .range-labels strong {
        color: var(--text-dark);
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
      }
      .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(106,17,203,0.35); }

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
      .back-link:hover { background: #eae7fb; transform: translateY(-2px); }

      .debug { margin-top: 16px; text-align: center; color: #666; font-size: 12px; }

      @media (max-width: 992px) {
        .content { padding: 0 24px; margin: 24px auto; }
        .card { padding: 32px; }
        .card-title { font-size: 24px; }
      }

      @media (max-width: 768px) {
        .site-header { padding: 12px 16px; }
        .site-title { font-size: 18px; }
        .welcome-text { display: none; }
        .back-btn { padding: 6px 12px; font-size: 13px; }
        .content { padding: 0 16px; margin: 20px auto; }
        .card { padding: 28px 24px; }
        .card-title { font-size: 22px; }
      }

      @media (max-width: 480px) {
        .card { padding: 24px 20px; }
        .card-title { font-size: 20px; margin-bottom: 20px; }
      }
    </style>
  </head>
  <body>
    <header class="site-header">
      <div class="header-left">
        <a href="<?= htmlspecialchars($DASHBOARD_URL) ?>" class="back-btn">← Back to Dashboard</a>
        <h1 class="site-title">Print Attendance Report</h1>
      </div>

      <div class="header-right">
        <div class="welcome-text">Welcome, <?= htmlspecialchars($adminName) ?></div>
        <a href="../../logout.php" class="logout">Logout</a>
      </div>
    </header>

    <div class="content">
      <div class="card">
        <div class="card-title">Generate Attendance Report</div>

        <form method="get" action="" target="_blank" novalidate>
          <!-- student search -->
          <div class="form-group">
            <label for="student_search">Search student</label>
            <input
              type="search"
              id="student_search"
              class="search-input"
              placeholder="Type a name to filter "
              aria-label="Search student by name"
            >
          </div>

          <div class="form-group">
            <label for="student">Select Student</label>
            <select id="student" name="id" required>
              <option value="">-- Choose a student --</option>
              <?php while ($row = $res->fetch_assoc()):
                $sel = ($managed_student > 0 && $managed_student === (int)$row['id']) ? 'selected' : '';
              ?>
                <option value="<?= (int)$row['id'] ?>" <?= $sel ?>>
                  <?= htmlspecialchars($row['fullName']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- inline date range calendar -->
          <div class="form-group">
            <label>Date Range</label>

            <!-- hidden fields sent to PHP -->
            <input type="hidden" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
            <input type="hidden" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">

            <div
              class="calendar-wrapper"
              id="rangeCalendar"
              data-initial-from="<?= htmlspecialchars($date_from) ?>"
              data-initial-to="<?= htmlspecialchars($date_to) ?>"
            >
              <div class="calendar-header">
                <button type="button" class="cal-nav-btn" data-cal-nav="prev"> Prev</button>
                <div class="calendar-month" id="calMonthLabel">Month YYYY</div>
                <button type="button" class="cal-nav-btn" data-cal-nav="next">Next </button>
              </div>
              <div class="calendar-grid" id="calGrid">
                <!-- days will be rendered here -->
              </div>
              <div class="range-labels">
                <div>From: <strong id="fromLabel"></strong></div>
                <div>To:&nbsp;&nbsp;&nbsp;&nbsp;<strong id="toLabel"></strong></div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn">Generate PDF</button>
        </form>

        <a href="view-attendance.php" class="back-link">← Back to Attendance</a>

        <?php if ($DEBUG): ?>
          <div class="debug">Debug mode ON</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- client-side filter for student dropdown + inline range calendar -->
    <script>
      // student search
      (function () {
        const searchInput = document.getElementById('student_search');
        const select = document.getElementById('student');
        if (!searchInput || !select) return;

        const allOptions = Array.from(select.querySelectorAll('option'));
        const placeholder = allOptions[0];
        const others = allOptions.slice(1);

        searchInput.addEventListener('input', function (e) {
          const q = (e.target.value || '').trim().toLowerCase();

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

      // inline date range calendar
      (function () {
        const calendarEl = document.getElementById('rangeCalendar');
        if (!calendarEl) return;

        const dateFromInput = document.getElementById('date_from');
        const dateToInput   = document.getElementById('date_to');
        const monthLabel    = document.getElementById('calMonthLabel');
        const grid          = document.getElementById('calGrid');
        const fromLabel     = document.getElementById('fromLabel');
        const toLabel       = document.getElementById('toLabel');

        if (!dateFromInput || !dateToInput || !monthLabel || !grid || !fromLabel || !toLabel) return;

        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        function parseDate(str) {
          if (!str) return null;
          const d = new Date(str);
          return isNaN(d.getTime()) ? null : d;
        }

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

        // init dates from PHP defaults
        let selectedFrom = parseDate(calendarEl.getAttribute('data-initial-from')) || new Date();
        let selectedTo   = parseDate(calendarEl.getAttribute('data-initial-to'))   || new Date();

        if (selectedFrom > selectedTo) {
          const tmp = selectedFrom;
          selectedFrom = selectedTo;
          selectedTo = tmp;
        }

        // month being viewed
        let currentView = new Date(selectedFrom.getTime());

        function updateLabelsAndInputs() {
          if (selectedFrom) {
            dateFromInput.value = formatYmd(selectedFrom);
            fromLabel.textContent = formatPretty(selectedFrom);
          } else {
            dateFromInput.value = '';
            fromLabel.textContent = '—';
          }

          if (selectedTo) {
            dateToInput.value = formatYmd(selectedTo);
            toLabel.textContent = formatPretty(selectedTo);
          } else if (selectedFrom) {
            // if only one selected, use same for "to"
            dateToInput.value = formatYmd(selectedFrom);
            toLabel.textContent = formatPretty(selectedFrom);
          } else {
            dateToInput.value = '';
            toLabel.textContent = '—';
          }
        }

        function renderCalendar() {
          const year = currentView.getFullYear();
          const month = currentView.getMonth(); // 0-11
          const firstOfMonth = new Date(year, month, 1);
          const startDay = firstOfMonth.getDay();
          const daysInMonth = new Date(year, month + 1, 0).getDate();

          const today = new Date();
          const todayY = today.getFullYear();
          const todayM = today.getMonth();
          const todayD = today.getDate();

          monthLabel.textContent = currentView.toLocaleDateString(undefined, {
            month: 'long',
            year: 'numeric'
          });

          grid.innerHTML = '';

          // weekday headers
          dayNames.forEach(d => {
            const h = document.createElement('div');
            h.className = 'cal-day-header';
            h.textContent = d;
            grid.appendChild(h);
          });

          // blanks before first day
          for (let i = 0; i < startDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'cal-day other-month';
            grid.appendChild(empty);
          }

          // actual days
          for (let d = 1; d <= daysInMonth; d++) {
            const cell = document.createElement('div');
            cell.className = 'cal-day';
            cell.textContent = d;

            const thisDate = new Date(year, month, d);

            // today marker
            if (year === todayY && month === todayM && d === todayD) {
              cell.classList.add('today');
            }

            // range styles
            if (selectedFrom && selectedTo) {
              if (sameDay(thisDate, selectedFrom)) {
                cell.classList.add('selected-start');
              } else if (sameDay(thisDate, selectedTo)) {
                cell.classList.add('selected-end');
              } else if (thisDate > selectedFrom && thisDate < selectedTo) {
                cell.classList.add('in-range');
              }
            } else if (selectedFrom && !selectedTo) {
              if (sameDay(thisDate, selectedFrom)) {
                cell.classList.add('selected-start');
              }
            }

            cell.addEventListener('click', function () {
              // start new range
              if (!selectedFrom || (selectedFrom && selectedTo)) {
                selectedFrom = thisDate;
                selectedTo = null;
              } else if (selectedFrom && !selectedTo) {
                // complete range
                if (thisDate < selectedFrom) {
                  selectedTo = selectedFrom;
                  selectedFrom = thisDate;
                } else {
                  selectedTo = thisDate;
                }
              }
              // if still no "to", treat as same day
              updateLabelsAndInputs();
              renderCalendar();
            });

            grid.appendChild(cell);
          }
        }

        // nav buttons
        calendarEl.querySelectorAll('[data-cal-nav]').forEach(btn => {
          btn.addEventListener('click', function () {
            const dir = this.getAttribute('data-cal-nav');
            if (dir === 'prev') {
              currentView.setMonth(currentView.getMonth() - 1);
            } else if (dir === 'next') {
              currentView.setMonth(currentView.getMonth() + 1);
            }
            renderCalendar();
          });
        });

        updateLabelsAndInputs();
        renderCalendar();
      })();
    </script>
  </body>
  </html>
  <?php
  $stmt->close();
  exit;
}

/*  Generate PDF (student + date range validated)  */
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$date_from = $_GET['date_from'] ?? $date_from;
$date_to   = $_GET['date_to']   ?? $date_to;

// Re-validate dates
$validDate = function($d) {
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  return $dt && $dt->format('Y-m-d') === $d;
};
if (!$validDate($date_from) || !$validDate($date_to) || $date_from > $date_to) {
  $date_from = date('Y-m-01');
  $date_to   = date('Y-m-d');
}

// Verify student belongs to admin and is attendance-enabled (use admin_students mapping)
$nm = $conn->prepare("
  SELECT s.fullName
  FROM students s
  JOIN admin_students a ON s.id = a.student_id
  WHERE s.id = ? AND a.admin_id = ? AND s.has_attendance_enabled = 1
  LIMIT 1
");
$nm->bind_param('ii', $studentId, $adminID);
$nm->execute();
$nm->bind_result($studentName);
$found = $nm->fetch();
$nm->close();

if (!$found) {
  http_response_code(404);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Not Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Poppins', sans-serif; background:#F8F8FF; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; color:#1E1E2D; }
      .error-card { background:#fff; padding:40px; border-radius:16px; box-shadow:0 6px 24px rgba(0,0,0,0.08); text-align:center; max-width:480px; }
      h3 { color:#6A11CB; margin-bottom:12px; }
      a.btn { display:inline-block; background:linear-gradient(90deg,#6A11CB,#9B59B6); color:#fff; padding:12px 20px; border-radius:10px; text-decoration:none; font-weight:700; }
    </style>
  </head>
  <body>
    <div class="error-card">
      <h3>Student Not Found</h3>
      <p>The selected student was not found or is not enabled for attendance.</p>
      <p><a class="btn" href="print-attendance.php">← Go Back</a></p>
    </div>
  </body>
  </html>
  <?php
  exit;
}

// Fetch attendance with mapping enforced (and date range)
$qs = $conn->prepare("
  SELECT a.date, a.status, a.remarks
  FROM attendance a
  JOIN admin_students asg ON a.studentID = asg.student_id
  WHERE asg.admin_id = ? AND a.studentID = ? AND a.date BETWEEN ? AND ?
  ORDER BY a.date ASC
");
$qs->bind_param('iiss', $adminID, $studentId, $date_from, $date_to);
$qs->execute();
$rows = $qs->get_result();

// Build HTML for PDF (match print-grades PDF style)
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
    h2 { text-align:center; color:#6A11CB; margin:18px 0 6px; }
    .range { text-align:center; color:#666; font-size:12px; margin-bottom:8px; }
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
        <div style="font-size:12px;color:#eaeaea;">Attendance Record</div>
      </div>
    </div>
    <div style="font-size:12px;">Generated: <?= date('F j, Y, g:i a') ?></div>
  </div>

  <h2><?= htmlspecialchars($studentName) ?> — Attendance</h2>
  <div class="range">From <?= htmlspecialchars($date_from) ?> to <?= htmlspecialchars($date_to) ?></div>

  <table>
    <tr><th>Date</th><th>Status</th><th>Remarks</th></tr>
    <?php $count=0; while($r=$rows->fetch_assoc()): $count++; ?>
      <tr>
        <td><?= htmlspecialchars($r['date']) ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= htmlspecialchars($r['remarks']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>

  <?php if ($count === 0): ?>
    <p style="text-align:center;margin-top:10px;"><em>No attendance records in this range.</em></p>
  <?php endif; ?>

  <div class="footer">© <?= date('Y') ?> EduTrack</div>
</body>
</html>
<?php
$html = ob_get_clean();
$qs->close();

// Render PDF inline
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

if (ob_get_length()) { ob_end_clean(); }
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="attendance_report.pdf"');
echo $dompdf->output();
exit;
