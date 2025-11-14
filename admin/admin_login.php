<?php
session_start();

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

// Get form inputs
$adminIdentifier = $_POST['adminID'];  
$password = $_POST['password'];

// Fetch admin by adminID (the login field)
$sql = "SELECT * FROM admins WHERE adminID = '$adminIdentifier' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $admin['password'])) {

        //  Store the numeric key for relationships
        $_SESSION['adminID'] = $admin['id']; // numeric id used in foreign key
        $_SESSION['adminName'] = $admin['fullName'];
        $_SESSION['adminIdentifier'] = $admin['adminID']; 

        // Redirect to dashboard
        header("Location: admin-dashboard.php");
        exit();

    } else {
        echo "<script>alert('Invalid password!'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Admin ID not found!'); window.history.back();</script>";
}

$conn->close();
?>
