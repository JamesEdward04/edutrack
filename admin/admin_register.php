<?php

// Database connection
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "user_system"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from form
$fullName = $_POST['fullName'];
$adminID = $_POST['adminID'];
$email = $_POST['email'];
$phoneNumber = $_POST['phoneNumber'];
$department = $_POST['department'];
$password = $_POST['password'];
$confirmPassword = $_POST['confirmPassword'];
$gender = $_POST['gender'];

// Check password match
if ($password !== $confirmPassword) {
    echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into DB
$sql = "INSERT INTO admins (fullName, adminID, email, phoneNumber, department, password, gender) 
        VALUES ('$fullName', '$adminID', '$email', '$phoneNumber', '$department', '$hashedPassword', '$gender')";

if ($conn->query($sql) === TRUE) {
    //  Use JavaScript redirect (works even if headers fail)
    echo "<script>
            alert('Admin registered successfully!');
            window.location.href = 'admin-login.html';
          </script>";
    exit();
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
