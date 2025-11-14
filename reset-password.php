<?php
require __DIR__ . '/vendor/autoload.php';
include 'db_connect.php';

//  Set timezone for Philippines
date_default_timezone_set('Asia/Manila');
mysqli_query($conn, "SET time_zone = '+08:00'");

//  Get token from URL and sanitize
$token = $_GET['token'] ?? '';

if (empty($token)) {
  die("<h3 style='font-family:Poppins, sans-serif; color:#6A11CB; text-align:center;'>Invalid reset link.</h3>");
}

//  Safely look up token and check expiry
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$reset = $result->fetch_assoc();

if (!$reset) {
  die("<h3 style='font-family:Poppins, sans-serif; color:#6A11CB; text-align:center;'>Reset link is invalid or has expired.</h3>");
}

//  Handle password reset
if (isset($_POST['reset_password'])) {
  $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $email = $reset['email'];
  $user_type = $reset['user_type'];
  $table = ($user_type === 'admin') ? 'admins' : 'students';

  //  Update password securely
  $update = $conn->prepare("UPDATE $table SET password = ? WHERE email = ?");
  $update->bind_param("ss", $new_password, $email);
  $update->execute();

  //  Delete the token so it can’t be reused
  $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
  $delete->bind_param("s", $email);
  $delete->execute();

  echo "
    <script>
      alert('Password reset successful! You can now log in.');
      window.location='index.html';
    </script>
  ";
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="css/admin-dashboard.css">
  <style>
    body {
      background: #F8F8FF;
      font-family: 'Poppins', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .reset-container {
      background: #fff;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 6px 20px rgba(106,17,203,0.15);
      max-width: 400px;
      width: 90%;
      text-align: center;
    }
    h2 {
      color: #6A11CB;
      margin-bottom: 20px;
    }
    .crud-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    .crud-form label {
      text-align: left;
      font-weight: 500;
      color: #333;
    }
    .crud-form input {
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
    }
    .add-btn {
      background: linear-gradient(to right, #6A11CB, #9B59B6);
      color: white;
      padding: 12px;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
    }
    .add-btn:hover {
      background: linear-gradient(to right, #9B59B6, #6A11CB);
      transform: translateY(-2px);
    }
    .login-link {
      margin-top: 20px;
      display: block;
      text-decoration: none;
      color: #6A11CB;
      font-weight: 500;
    }
  </style>
</head>
<body>
  <div class="reset-container">
    <h2>Reset Your Password</h2>

    <form method="POST" class="crud-form">
      <label>New Password:</label>
      <input type="password" name="password" required placeholder="Enter new password">

      <button type="submit" name="reset_password" class="add-btn">Reset Password</button>
    </form>

    <a href="index.html" class="login-link">Back to Login</a>
  </div>
</body>
</html>
