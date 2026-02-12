<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('admin');

$db = new Database();
$conn = $db->getConnection();

// Clear all log activities
$stmt = $conn->prepare("DELETE FROM log_aktivitas");
$stmt->execute();
$stmt->close();

log_activity($_SESSION['user_id'], "Menghapus semua log aktivitas");

header("Location: log_activity.php");
exit();
?>
