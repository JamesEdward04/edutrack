<?php
session_start();
include __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

// Only allow logged-in admins
if (!isset($_SESSION['adminID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$admin_id = (int)$_SESSION['adminID'];

if ($admin_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid admin ID']);
    exit;
}

/*  Fetch admin-scoped counts */

// Total students managed (from admin_students linking table)
$total_students = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM admin_students WHERE admin_id = ?")) {
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $total_students = (int)$r['cnt'];
    }
    $stmt->close();
}

// Total grades given by this admin
$total_grades = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM grades WHERE admin_id = ?")) {
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $total_grades = (int)$r['cnt'];
    }
    $stmt->close();
}

// Total attendance records by this admin
$total_attendance = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM attendance WHERE admin_id = ?")) {
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $total_attendance = (int)$r['cnt'];
    }
    $stmt->close();
}

// Send JSON response
echo json_encode([
    'students'   => $total_students,
    'grades'     => $total_grades,
    'attendance' => $total_attendance
]);