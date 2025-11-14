<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
include 'db_connect.php';

date_default_timezone_set('Asia/Manila');
mysqli_query($conn, "SET time_zone = '+08:00'");

$success = $error = '';

if (isset($_POST['reset_request'])) {
    $email = trim($_POST['email'] ?? '');
    $user_type = ($_POST['user_type'] ?? '') === 'admin' ? 'admin' : 'student';

    if ($email === '') {
        $error = 'Please enter your email address.';
    } else {
        // Choose correct table 
        $table = $user_type === 'admin' ? 'admins' : 'students';

        // Prepared lookup to avoid injection
        $stmt = $conn->prepare("SELECT id, email FROM $table WHERE email = ? LIMIT 1");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                // Single secure token
                $token = bin2hex(random_bytes(32));
                $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

                // Delete existing tokens for this email (prepared)
                $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                if ($del) {
                    $del->bind_param('s', $email);
                    $del->execute();
                    $del->close();
                }

                // Insert new token (prepared)
                $ins = $conn->prepare("INSERT INTO password_resets (email, token, user_type, expires_at) VALUES (?, ?, ?, ?)");
                if (!$ins) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $ins->bind_param('ssss', $email, $token, $user_type, $expires_at);
                    if (!$ins->execute()) {
                        $error = "Failed to save reset token: " . $ins->error;
                    }
                    $ins->close();
                }

                // If token saved, send email
                if ($error === '') {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'edutrack321@gmail.com'; //  SMTP user
                        $mail->Password = 'sqsy cxlw axjs lqkc';    //  app password
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        $mail->setFrom('edutrack321@gmail.com', 'EduTrack');
                        $mail->addAddress($email);
                        $mail->isHTML(true);

                        // Build reset link (adjust host/path for production)
                        $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                                      . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset-password.php?token='
                                      . urlencode($token);

                        $mail->Subject = "EduTrack Password Reset Request";
                        $mail->Body = "
                            <div style='font-family: Poppins, Arial, sans-serif; color:#222;'>
                              <p>Hello,</p>
                              <p>We received a password reset request for your EduTrack account.</p>
                              <p style='text-align:center; margin:18px 0;'>
                                <a href='$reset_link' 
                                   style='background-color:#6A11CB;color:#fff;padding:10px 20px;
                                          text-decoration:none;border-radius:8px;display:inline-block;'>
                                   Reset Password
                                </a>
                              </p>
                              <p style='font-size:13px;color:#666;'>This link will expire in 15 minutes.</p>
                              <p style='font-size:13px;color:#666;'>If you did not request this, please ignore this email.</p>
                              <p style='margin-top:16px;color:#666;'>— EduTrack Support</p>
                            </div>
                        ";

                        $mail->AltBody = "We received a password reset request for your EduTrack account.\n\n"
                                       . "Reset link: $reset_link\n\n"
                                       . "This link will expire in 15 minutes.\n\n"
                                       . "If you did not request this, ignore this email.\n\n— EduTrack Support";

                        $mail->send();
                        $success = "A password reset link has been sent to your email address.";
                    } catch (Exception $e) {
                        // Mailer exception details
                        $error = "Mailer Error: " . htmlspecialchars($mail->ErrorInfo);
                    }
                }
            } else {
                $error = "No account found with that email address for the selected account type.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Forgot Password — EduTrack</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Reuse admin CSS if available; otherwise fallback to embedded styles below -->
  <link rel="stylesheet" href="css/admin-dashboard.css" onerror="this.onerror=null;this.rel='stylesheet';this.href='';">

  <style>
    :root {
      --purple-start: #6A11CB;
      --purple-end: #9B59B6;
      --gold: #FFD700;
      --bg: #F8F8FF;
      --card-bg: #FFFFFF;
      --text-dark: #1E1E2D;
      --muted: #6B6B83;
    }

    html,body { height:100%; margin:0; font-family:'Poppins',system-ui,Segoe UI,Roboto,Arial; background:var(--bg); color:var(--text-dark); }

   
    .page {
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 24px;
    }

    .card {
      width: 100%;
      max-width: 540px;
      background: var(--card-bg);
      border-radius: 14px;
      padding: 32px;
      box-shadow: 0 8px 30px rgba(16,24,40,0.06);
      border: 1px solid rgba(0,0,0,0.04);
    }

    .brand {
      display:flex;
      align-items:center;
      gap:12px;
      margin-bottom: 18px;
    }
    .brand .logo {
      width:44px;
      height:44px;
      border-radius:8px;
      background: linear-gradient(90deg,var(--purple-start),var(--purple-end));
      display:inline-flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-weight:700;
      font-size:18px;
    }
    .brand h1 { font-size:18px; margin:0; color:var(--purple-start); }

    h2 { margin: 6px 0 20px 0; color:var(--purple-start); font-size:22px; }
    p.lead { margin:0 0 14px 0; color:var(--muted); font-size:14px; }

    .form {
      display:flex;
      flex-direction:column;
      gap:12px;
      margin-top:6px;
    }
    label { font-size:13px; font-weight:600; color:var(--text-dark); }
    input[type="email"], select {
      padding:12px 14px;
      border-radius:10px;
      border:1px solid #e6e6ee;
      font-size:15px;
      width:100%;
    }
    input:focus, select:focus { outline:none; box-shadow:0 0 0 4px rgba(106,17,203,0.06); border-color:var(--purple-start); }

    .row { display:flex; gap:10px; align-items:center; }
    .btn {
      display:inline-block;
      background: linear-gradient(90deg,var(--purple-start),var(--purple-end));
      color:#fff;
      padding:12px 18px;
      border-radius:10px;
      text-decoration:none;
      border:0;
      font-weight:700;
      cursor:pointer;
      box-shadow: 0 6px 18px rgba(106,17,203,0.18);
      transition: transform .16s ease, box-shadow .16s;
      text-align:center;
    }
    .btn:active { transform: translateY(1px); }
    .btn.secondary {
      background:#f0eef9;
      color:var(--purple-start);
      border:1px solid #e1dbfa;
      box-shadow:none;
    }

    .meta { margin-top:8px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }

    .msg {
      padding:10px 12px;
      border-radius:8px;
      font-weight:600;
    }
    .msg.success { background:#e6ffed; color:#065f46; border:1px solid #b7f0c6; }
    .msg.error { background:#fff0f0; color:#7a1a1a; border:1px solid #f5c2c2; }

    .small { font-size:13px; color:var(--muted); }

    /* Responsive */
    @media (max-width: 520px) {
      .card { padding:20px; border-radius:12px; }
      .brand h1 { font-size:16px; }
      h2 { font-size:20px; }
      .row { flex-direction:column; align-items:stretch; }
      .meta { flex-direction:column; align-items:flex-start; gap:8px; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="card" role="main" aria-labelledby="forgot-heading">
      <div class="brand">
        <div class="logo">ET</div>
        <h1>EduTrack</h1>
      </div>

      <h2 id="forgot-heading">Forgot Password</h2>
      <p class="lead">Enter the email for your account and select the account type. We'll send a secure reset link that expires in 15 minutes.</p>

      <?php if ($success): ?>
        <div class="msg success" role="status"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="msg error" role="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="form" novalidate>
        <div>
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" required placeholder="you@example.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>

        <div>
          <label for="user_type">Account type</label>
          <select id="user_type" name="user_type" required>
            <option value="">Select account type</option>
            <option value="admin" <?= (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : '' ?>>Admin</option>
            <option value="student" <?= (isset($_POST['user_type']) && $_POST['user_type'] === 'student') ? 'selected' : '' ?>>Student</option>
          </select>
        </div>

        <div class="row" style="margin-top:6px;">
          <button type="submit" name="reset_request" class="btn">Send Reset Link</button>
          <a href="index.html" class="btn secondary" style="text-decoration:none;">Back to Login</a>
        </div>

        <div class="meta">
          <div class="small">Have problems? Contact <a href="mailto:edutrack321@gmail.com">support</a>.</div>
          <div class="small">Link expires in 15 minutes.</div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
