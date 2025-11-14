<?php

session_start();
include __DIR__ . '/../db_connect.php';

// Required POST fields
$required = ['fullName','studentNumber','email','phoneNumber','city','province','password','confirmPassword','gender'];
foreach ($required as $k) {
    if (!isset($_POST[$k]) || trim($_POST[$k]) === '') {
        echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
        exit();
    }
}

// Collect and sanitize
$fullName = trim($_POST['fullName']);
$studentNumber = trim($_POST['studentNumber']);
$email = trim($_POST['email']);
$phoneNumber = trim($_POST['phoneNumber']);
$city = trim($_POST['city']);
$province = trim($_POST['province']);
$password = $_POST['password'];
$confirmPassword = $_POST['confirmPassword'];
$gender = trim($_POST['gender']);

// Basic validations
if ($password !== $confirmPassword) {
    echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Invalid email address'); window.history.back();</script>";
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert student (admin_id is omitted so it can be NULL)
$insertSql = "INSERT INTO students (fullName, studentNumber, email, phoneNumber, city, province, password, gender)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertSql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo "<script>alert('Server error. Try again later.'); window.history.back();</script>";
    exit();
}
$stmt->bind_param('ssssssss', $fullName, $studentNumber, $email, $phoneNumber, $city, $province, $hashedPassword, $gender);
$ok = $stmt->execute();
if (!$ok) {
    // common reasons: duplicate studentNumber or email (unique constraints)
    $err = $stmt->error;
    error_log("Student insert error: " . $err);
    echo "<script>alert('Registration failed: " . htmlspecialchars($err) . "'); window.history.back();</script>";
    $stmt->close();
    exit();
}
$new_student_id = $conn->insert_id;
$stmt->close();

if (isset($_SESSION['adminID']) && is_numeric($_SESSION['adminID'])) {
    $admin_id = (int)$_SESSION['adminID'];
    // Avoid duplicate mapping if it exists
    $check = $conn->prepare("SELECT 1 FROM admin_students WHERE admin_id = ? AND student_id = ? LIMIT 1");
    if ($check) {
        $check->bind_param('ii', $admin_id, $new_student_id);
        $check->execute();
        $res = $check->get_result();
        if (!$res->fetch_assoc()) {
            $ins = $conn->prepare("INSERT INTO admin_students (admin_id, student_id, assigned_at) VALUES (?, ?, NOW())");
            if ($ins) {
                $ins->bind_param('ii', $admin_id, $new_student_id);
                $ins->execute();
                $ins->close();
            }
        }
        $check->close();
    }
}

// Success
echo "<script>alert('Student Registration successful! You can now log in.'); window.location.href = 'student-login.html';</script>";
exit();
?>
