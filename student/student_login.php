<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "user_system"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form inputs
$studentNumber = $_POST['studentID'];
$password = $_POST['password'];

// Fetch student
$sql = "SELECT * FROM students WHERE studentNumber = '$studentNumber' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();

    if (password_verify($password, $student['password'])) {
        $_SESSION['studentNumber'] = $student['studentNumber'];
        $_SESSION['studentName'] = $student['fullName'];

        echo "<script>
                alert('Login successful! Welcome {$student['fullName']}');
                window.location.href = 'student-dashboard.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('Invalid password!'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Student not found!'); window.history.back();</script>";
}

$conn->close();
?>
